<?php
/**
 * Readings sync: ebs.actual_readings (measured curves) -> VoltApp POST /v1/readings.
 * ONE series per POD (tip=INTERVAL, registru 1.8.0).
 *
 * Maparea curba->POD se face prin `actual_curves_variance` (nu prin curve_name):
 *   - curba 1:1 (un singur POD)        -> seria direct sub POD (coef=1, orice luna)
 *   - curba AGREGAT (mai multe PODuri)  -> impartita pe fiecare POD dupa cota lunara
 *       coef = consum_lunar_POD / consum_lunar_total_grup   (ca in getRaportOrarEnel)
 *
 * ACUMULARE per (POD, an, luna): un POD poate fi servit de MAI MULTE curbe in aceeasi luna
 * (37 cazuri). Contributiile TUTUROR curbelor lui se INSUMEAZA pe interval inainte de a trimite
 * UN SINGUR lot (batchId = ebs-real-<pod>-<an>-<luna>), altfel un lot il suprascrie pe celalalt.
 *
 * Resumable via sync_state (entity='citiri'); semnatura = md5(valori) -> retrimite doar la schimbare.
 *
 * Daily log: sync/logs/citiri-YYYY-MM-DD.log
 * Usage: php8.2 sync_citiri.php [--years=2026] [--dry]
 */

require __DIR__ . '/lib.php';

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/citiri-' . date('Y-m-d') . '.log';
$opts    = getopt('', ['years::', 'dry']);
$years   = isset($opts['years']) ? array_map('intval', explode(',', $opts['years'])) : [2026];
$dryOnly = isset($opts['dry']);
$yList   = implode(',', array_map('intval', $years));

ensure_state_table();
logline('=== sync citiri start | years=' . implode(',', $years) . ($dryOnly ? ' (DRY)' : '') . ' ===');

// --- plan 1:1: curve_id -> [pod, customer_id] ---
$plan11 = [];
$res = db()->query(
    "SELECT t.curve_id, t.pod, p.customer_id
       FROM (SELECT curve_id, MIN(pod) pod FROM actual_curves_variance
              GROUP BY curve_id HAVING COUNT(DISTINCT pod)=1) t
       JOIN actual_curves ac ON ac.curve_id=t.curve_id AND ac.curve_type='masurata'
       JOIN pods p ON p.pod_no=t.pod
       JOIN customers c ON c.customer_id=p.customer_id AND c.customer_status='Activ'");
while ($r = $res->fetch_assoc()) $plan11[(int)$r['curve_id']] = ['pod' => $r['pod'], 'cust' => (int)$r['customer_id']];
logline('curbe 1:1 (client activ): ' . count($plan11));

// --- plan agregat: curve_id -> [ "Y-mm" => [ [pod,coef,cust], ... ] ] ---
$planAgg = [];
$res = db()->query(
    "SELECT acv.curve_id, acv.pod, p.customer_id,
            YEAR(acv.consumption_date) y, MONTH(acv.consumption_date) mo,
            acv.consumption_ea/cc.total AS coef
       FROM actual_curves_variance acv
       JOIN (SELECT curve_id FROM actual_curves_variance GROUP BY curve_id HAVING COUNT(DISTINCT pod)>1) multi
         ON multi.curve_id=acv.curve_id
       JOIN actual_curves ac ON ac.curve_id=acv.curve_id AND ac.curve_type='masurata'
       JOIN (SELECT curve_id, consumption_date, SUM(consumption_ea) total
               FROM actual_curves_variance GROUP BY curve_id, consumption_date) cc
         ON cc.curve_id=acv.curve_id AND cc.consumption_date=acv.consumption_date AND cc.total>0
       JOIN pods p ON p.pod_no=acv.pod
       JOIN customers c ON c.customer_id=p.customer_id AND c.customer_status='Activ'
      WHERE YEAR(acv.consumption_date) IN ($yList)");
$aggCurves = [];
while ($r = $res->fetch_assoc()) {
    $cid = (int)$r['curve_id'];
    $ym  = $r['y'] . '-' . sprintf('%02d', $r['mo']);
    $planAgg[$cid][$ym][] = ['pod' => $r['pod'], 'coef' => (float)$r['coef'], 'cust' => (int)$r['customer_id']];
    $aggCurves[$cid] = true;
}
logline('curbe agregate (cu coeficienti in anii ceruti): ' . count($aggCurves));

$allCurves = array_unique(array_merge(array_keys($plan11), array_keys($aggCurves)));
$state = state_load('citiri');
$totSent = 0; $totBatches = 0; $skipped = 0; $failed = 0; $multiCurvePods = 0;

foreach ($years as $year) {
    for ($m = 1; $m <= 12; $m++) {
        $mm = sprintf('%02d', $m);
        // acumulator: pod => ['cust'=>, 'ncurves'=>, 'vals'=>[ts => kWh]]
        $acc = [];
        foreach ($allCurves as $cid) {
            $cid = (int)$cid;
            if (isset($plan11[$cid])) {
                $members = [['pod' => $plan11[$cid]['pod'], 'coef' => 1.0, 'cust' => $plan11[$cid]['cust']]];
            } else {
                $members = $planAgg[$cid]["$year-$mm"] ?? null;
                if (!$members) continue;
            }
            $rs = db()->query("SELECT reading_datetime, actual_ea FROM actual_readings_$m
                               WHERE curve_id=$cid AND year=$year ORDER BY reading_datetime");
            if (!$rs || $rs->num_rows === 0) continue;
            $curve = [];
            while ($x = $rs->fetch_assoc()) $curve[] = [$x['reading_datetime'], (float)$x['actual_ea']];
            foreach ($members as $mb) {
                $pod = $mb['pod']; $coef = (float)$mb['coef'];
                if (!isset($acc[$pod])) $acc[$pod] = ['cust' => $mb['cust'], 'ncurves' => 0, 'vals' => []];
                $acc[$pod]['ncurves']++;
                foreach ($curve as $c) {
                    $kwh = max(0.0, $c[1] * $coef) * 1000.0;   // MWh*coef -> kWh, consum >=0
                    $acc[$pod]['vals'][$c[0]] = ($acc[$pod]['vals'][$c[0]] ?? 0.0) + $kwh;
                }
            }
        }

        // trimite un lot per POD (valori insumate din toate curbele lui pe luna)
        foreach ($acc as $pod => $A) {
            ksort($A['vals']);
            $rows = [];
            foreach ($A['vals'] as $ts => $kwh) {
                $rows[] = ['pod' => $pod, 'timestamp' => ro_to_utc_z($ts),
                           'valoare' => round($kwh, 3), 'tip' => 'INTERVAL', 'registru' => '1.8.0'];
            }
            if ($A['ncurves'] > 1) $multiCurvePods++;
            $batchId = "ebs-real-$pod-$year-$mm";
            $sig = md5(json_encode(array_map(fn($r) => [$r['timestamp'], $r['valoare']], $rows)));
            if (($state[$batchId] ?? null) === $sig) { $skipped++; continue; }
            if ($dryOnly) { logline("  DRY $batchId (" . count($rows) . " rows, {$A['ncurves']} curbe)"); continue; }

            $resp = volt_readings($batchId, $rows, false);
            if (!$resp || !in_array($resp['_http'] ?? 0, [200, 202], true) || ($resp['rejected'] ?? 0) > 0) {
                logline("  FAIL $batchId http=" . ($resp['_http'] ?? '?') . " rejected=" . ($resp['rejected'] ?? '?'));
                $failed++; continue;
            }
            state_save('citiri', $batchId, $sig, null, $batchId);
            $totSent += (int)($resp['accepted'] ?? 0); $totBatches++;
            if ($totBatches % 100 === 0) logline("  ... $totBatches loturi, $totSent citiri");
        }
    }
}
logline("result: loturi=$totBatches citiri=$totSent | PODuri cu >1 curba/luna=$multiCurvePods | skip=$skipped fail=$failed");
logline('=== done ===');
