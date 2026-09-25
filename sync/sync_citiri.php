<?php
/**
 * Readings sync: ebs.actual_readings (measured curves) -> VoltApp POST /v1/readings.
 * ONE series per POD (tip=INTERVAL, registru 1.8.0), sourced ONLY from actual_readings
 * (the hourly-import master), for ACTIVE clients' measured curves (curve_type='masurata',
 * curve_name = POD). batchId = ebs-real-<pod>-<year>-<month> (matches the 630 backfill;
 * re-sending replaces that batch's contribution).
 *
 * Resumable: sync_state entity='citiri', key=batchId, hash=row-count. A POD-month whose
 * count is unchanged is skipped.
 *
 * Daily log: sync/logs/citiri-YYYY-MM-DD.log
 * Usage: php8.2 sync_citiri.php [--years=2026]  (default: 2026; e.g. --years=2023,2024,2025,2026)
 */

require __DIR__ . '/lib.php';

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/citiri-' . date('Y-m-d') . '.log';
$opts  = getopt('', ['years::', 'dry']);
$years = isset($opts['years']) ? array_map('intval', explode(',', $opts['years'])) : [2026];
$dryOnly = isset($opts['dry']);

ensure_state_table();
logline('=== sync citiri start | years=' . implode(',', $years) . ' ===');

// measured curves of ACTIVE clients: curve_id, pod, customer_id
$curves = [];
$res = db()->query("SELECT ac.curve_id, ac.curve_name AS pod, p.customer_id
    FROM actual_curves ac
    JOIN pods p ON p.pod_no = ac.curve_name
    JOIN customers c ON c.customer_id = p.customer_id AND c.customer_status='Activ'
    WHERE ac.curve_type='masurata'
    GROUP BY ac.curve_id, ac.curve_name, p.customer_id");
while ($r = $res->fetch_assoc()) $curves[] = $r;
logline('curbe masurate (clienti activi): ' . count($curves));

$state = state_load('citiri');
$totSent = 0; $totBatches = 0; $skipped = 0; $failed = 0;

foreach ($curves as $cv) {
    $pod = $cv['pod']; $cid = (int)$cv['curve_id'];
    foreach ($years as $year) {
        for ($m = 1; $m <= 12; $m++) {
            $tbl = 'actual_readings_' . $m;
            $q = "SELECT reading_datetime, actual_ea FROM $tbl
                  WHERE curve_id=$cid AND year=$year ORDER BY reading_datetime";
            $rs = db()->query($q);
            if (!$rs || $rs->num_rows === 0) continue;

            $rows = [];
            while ($x = $rs->fetch_assoc()) {
                $rows[] = [
                    'pod'       => $pod,
                    'timestamp' => ro_to_utc_z($x['reading_datetime']),
                    'valoare'   => round(max(0.0, (float)$x['actual_ea']) * 1000, 3),   // MWh->kWh; consum activ >=0 (negativ=injectie, ignorat)
                    'tip'       => 'INTERVAL',
                    'registru'  => '1.8.0',
                ];
            }
            $batchId = "ebs-real-$pod-$year-" . sprintf('%02d', $m);
            $sig = (string)count($rows);
            if (($state[$batchId] ?? null) === $sig) { $skipped++; continue; }   // deja trimis, neschimbat

            if ($dryOnly) { logline("  DRY $batchId ({$sig} rows)"); continue; }

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
logline("result: loturi=$totBatches citiri=$totSent | skip=$skipped fail=$failed");
logline('=== done ===');
