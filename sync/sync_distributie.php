<?php
/**
 * Distributor load-profile -> ebs.actual_readings (measured curves).
 *
 * Pulls the PREVIOUS DAY 15-min curve for ALL meters allocated to us (paginated),
 * SUMS devloc per POD, converts kWh -> MWh (/1000), and upserts into
 * actual_readings_<month> — the same target as "Realizat -> Import Date Orare".
 * Read-only against the distributor; the only writes are into ebs.actual_readings.
 *
 * Mapping (validated against the 24-Sep test, matched to the source curves):
 *   reading_datetime = timeStamp local RO (direct, NO tz conversion; actual_readings stores local)
 *   actual_ea (MWh)  = SUM_devloc( wi_1_8_0 ) / 1000        (wi_1_8_0 = active import, kWh)
 *   curve_id/customer_id from POD (curve_type='masurata', curve_name = POD), supplier_id = 1
 *
 * Window: the API answers ONLY 10:15-23:00 EEST -> schedule cron inside it.
 * Safety: stops immediately on 401 (avoids the 15-min auth block).
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE actual_ea -> re-runs & corrections converge.
 *
 * Daily log: sync/logs/distributie-YYYY-MM-DD.log
 * Usage: php8.2 sync_distributie.php [--dry]
 */

require __DIR__ . '/lib.php';
job_begin('distributie');

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/distributie-' . date('Y-m-d') . '.log';
$dryOnly = in_array('--dry', $argv, true);

logline('=== sync distributie start' . ($dryOnly ? ' (DRY)' : '') . ' ===');

// 0) health (no auth) — if the service itself is down, don't hammer auth
[$hc, $hb] = distrib_get('/health');
logline("health HTTP $hc " . trim((string)$hb));

// 1) POD -> curve_id + customer_id (measured curves only; curve_name = POD)
$map = [];
$r = db()->query("SELECT ac.curve_id, ac.curve_name AS pod, p.customer_id
                  FROM actual_curves ac
                  JOIN pods p ON p.pod_no = ac.curve_name
                  WHERE ac.curve_type='masurata'");
while ($x = $r->fetch_assoc())
    $map[$x['pod']] = ['curve_id' => (int)$x['curve_id'], 'customer_id' => (int)$x['customer_id']];
logline('curbe masurate (POD->curve): ' . count($map));

// 2) fetch previous-day load profile (paginated, 401-safe)
$lp = distrib_loadprofile();
if (!$lp['ok']) { logline('FETCH FAIL: ' . $lp['reason']); logline('=== aborted ==='); exit(1); }
$date   = $lp['date'];
$meters = $lp['meters'];
logline("loadprofile date=$date | contoare=" . count($meters));

// 3) aggregate: SUM wi_1_8_0 across devloc per (POD, timestamp)
$agg = [];                       // pod => [ 'Y-m-d H:i:s' => kWh_sum ]
$podsSeen = []; $skipped = [];
foreach ($meters as $m) {
    $pod = $m['pod'] ?? '';
    if (!isset($map[$pod])) { $skipped[$pod] = true; continue; }   // POD nealocat local -> skip
    $podsSeen[$pod] = true;
    foreach (($m['readings'] ?? []) as $rd) {
        if (empty($rd['timeStamp'])) continue;
        $ts = str_replace('T', ' ', substr($rd['timeStamp'], 0, 19));
        $agg[$pod][$ts] = ($agg[$pod][$ts] ?? 0.0) + (float)($rd['wi_1_8_0'] ?? 0);
    }
}
logline('POD alocate cu date: ' . count($podsSeen) . ' | POD nealocate (skip): ' . count($skipped));
if ($skipped) logline('  nealocate: ' . implode(',', array_slice(array_keys($skipped), 0, 20)) . (count($skipped) > 20 ? ' ...' : ''));

// 4) build rows grouped by month-partition
$SUP = 1;
$byMonth = [];                   // month(int) => ["(...)","(...)", ...]
$rowsTotal = 0;
foreach ($agg as $pod => $series) {
    $cv = $map[$pod];
    foreach ($series as $ts => $kwh) {
        $mwh   = sprintf('%.8f', max(0.0, $kwh) / 1000.0);   // kWh -> MWh, consum activ >= 0
        $month = (int)substr($ts, 5, 2);
        // NB: `year` e coloana generata (STORED) din reading_datetime -> NU o inseram.
        $byMonth[$month][] = sprintf("(%d,'%s',%d,%d,%s)", $SUP, $ts, $cv['customer_id'], $cv['curve_id'], $mwh);
        $rowsTotal++;
    }
}
logline("randuri de scris: $rowsTotal (in " . count($byMonth) . ' partitii lunare)');

if ($dryOnly) {
    foreach ($byMonth as $mo => $vals) logline("  DRY actual_readings_$mo: " . count($vals) . ' randuri');
    logline('=== dry done ==='); exit(0);
}

// 5) upsert into actual_readings_<month>, chunked
$written = 0;
foreach ($byMonth as $mo => $vals) {
    $tbl = 'actual_readings_' . $mo;
    foreach (array_chunk($vals, 2000) as $chunk) {
        $sql = "INSERT INTO $tbl (supplier_id,reading_datetime,customer_id,curve_id,actual_ea) VALUES "
             . implode(',', $chunk)
             . ' ON DUPLICATE KEY UPDATE actual_ea=VALUES(actual_ea)';
        if (!db()->query($sql)) { logline("  ERR $tbl: " . db()->error); continue; }
        $written += count($chunk);
    }
    logline("  $tbl: $written/" . $rowsTotal . ' scrise (cumulat)');
}
logline("result: date=$date poduri=" . count($podsSeen) . " randuri_scrise=$written");
logline('=== done ===');
