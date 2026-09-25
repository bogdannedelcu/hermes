<?php
/**
 * Daily contract sync: ebs -> VoltApp /v1/portfolio (entity=contracte).
 * VoltApp needs ONE contract row PER POD (no client-level price fallback), so we emit
 * one row per active POD carrying that POD's active EA price.
 *
 * Price resolution per POD (service_rates, service_code=EA):
 *   1) POD-specific override (pod_id set)  -> takes precedence
 *   2) client-level rate (pod_id null)
 *   preferring a currently-active contract (contract_stop >= today), then latest start_date.
 * numar_contract = "<real contract number>-<pod>"  (real number kept, POD makes it unique).
 *
 * Daily log: sync/logs/contracte-YYYY-MM-DD.log
 * Usage: php8.2 sync_contracte.php [--dry]
 */

require __DIR__ . '/lib.php';

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/contracte-' . date('Y-m-d') . '.log';
$dryOnly = in_array('--dry', $argv, true);
$batch   = 'ebs-contracte-sync-' . date('Ymd-His');
$today    = date('Y-m-d');

ensure_state_table();
logline("=== sync contracte start (batch=$batch) ===");

$enabled = cfg()['enabled_customers'];

// Preload EA rates + their contract; build best rate per (pod_id) and per (customer_id, client-level)
$podRate = []; $cliRate = [];
$rk = function($stop, $start) use ($today) {           // higher = better
    return ($stop >= $today ? 2_00000000 : 0) + (int)str_replace('-', '', substr($start, 0, 10) ?: '0');
};
$q = "SELECT sr.customer_id, sr.pod_id, sr.service_value, sr.start_date,
             c.contract_number, c.contract_date, c.contract_stop
      FROM service_rates sr
      JOIN services s ON s.service_id=sr.service_id
      JOIN contracts c ON c.contract_id=sr.contract_id
      WHERE s.service_code='EA'";
$res = db()->query($q);
while ($r = $res->fetch_assoc()) {
    $score = $rk($r['contract_stop'] ?? '', $r['start_date'] ?? ($r['contract_date'] ?? ''));
    if (!empty($r['pod_id'])) {
        $pid = (int)$r['pod_id'];
        if (!isset($podRate[$pid]) || $score > $podRate[$pid]['_s']) { $r['_s'] = $score; $podRate[$pid] = $r; }
    } else {
        $cid = (int)$r['customer_id'];
        if (!isset($cliRate[$cid]) || $score > $cliRate[$cid]['_s']) { $r['_s'] = $score; $cliRate[$cid] = $r; }
    }
}

// Client names
$names = [];
$rn = db()->query("SELECT customer_id, customer_name FROM customers");
while ($x = $rn->fetch_assoc()) $names[(int)$x['customer_id']] = $x['customer_name'];

// Active PODs in scope
$where = "p.pod_status='Activ'";
if ($enabled) $where .= ' AND p.customer_id IN (' . implode(',', array_map('intval', $enabled)) . ')';
$rows = []; $noPrice = 0;
$res = db()->query("SELECT p.pod_id, p.pod_no, p.customer_id FROM pods p WHERE $where ORDER BY p.pod_no");
while ($p = $res->fetch_assoc()) {
    if (preg_replace('/[^A-Za-z0-9]/', '', (string)$p['pod_no']) === '') continue; // POD invalid (ex. ???)
    $pid = (int)$p['pod_id']; $cid = (int)$p['customer_id'];
    $rate = $podRate[$pid] ?? $cliRate[$cid] ?? null;
    if (!$rate) { $noPrice++; continue; }              // POD without any EA price -> skip
    $num = trim((string)$rate['contract_number']);
    if ($num === '') $num = 'C';
    $rows[$p['pod_no']] = [
        'numar_contract' => $num . '-' . $p['pod_no'],
        'cod_client'     => (string)$cid,
        'data_start'     => substr($rate['contract_date'], 0, 10),
        'data_final'     => substr((string)$rate['contract_stop'], 0, 10),
        'pret_kwh'       => rtrim(rtrim(sprintf('%.5f', $rate['service_value'] / 1000), '0'), '.'),
        'tip_pret'       => 'FIX',
        'stare'          => 'ACTIV',
        'pod'            => $p['pod_no'],
        'nume_client'    => $names[$cid] ?? null,
    ];
}
logline('contracte in scope: ' . count($rows) . ' | PODuri fara pret (sarite): ' . $noPrice);

$state = state_load('contracte');
$changed = [];
foreach ($rows as $key => $row) {
    $h = row_hash($row);
    if (($state[$key] ?? null) !== $h) $changed[$key] = ['row' => $row, 'hash' => $h];
}
logline('changed (new/modified): ' . count($changed));
if (!$changed) { logline('nothing to sync.'); logline('=== done ==='); exit(0); }

// contracte e mai greu la server -> chunk mai mic
$t = sync_commit('contracte', $changed, $batch, $dryOnly, 400);
logline("result: created={$t['created']} updated={$t['updated']} unchanged={$t['unchanged']} rejected={$t['rejected']} saved={$t['saved']} failed={$t['failed']}");
logline('=== done ===');
