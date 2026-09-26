<?php
/**
 * Daily consumption-point sync: ebs.pods -> VoltApp /v1/portfolio (entity=locuri).
 * Change detection: content-hash in sync_state. New/changed PODs only.
 * Scope from config['enabled_customers'] (empty = all). New PODs auto-picked-up.
 *
 * Daily log: sync/logs/locuri-YYYY-MM-DD.log
 * Usage: php8.2 sync_locuri.php [--dry]
 */

require __DIR__ . '/lib.php';
job_begin('locuri');

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/locuri-' . date('Y-m-d') . '.log';
$dryOnly = in_array('--dry', $argv, true);
$batch   = 'ebs-locuri-sync-' . date('Ymd-His');

ensure_state_table();
logline("=== sync locuri start (batch=$batch) ===");

$enabled = cfg()['enabled_customers'];
$where   = 'p.pod_status=\'Activ\'';
if ($enabled) $where .= ' AND p.customer_id IN (' . implode(',', array_map('intval', $enabled)) . ')';

// latest non-empty meter serial per POD (from consumptions)
$rows = [];
$res = db()->query("SELECT p.pod_no, p.customer_id, p.pod_status, p.county, p.city, p.address,
        cu.customer_anre_band AS anre_band,
        (SELECT c.device_serial_number FROM consumptions c
          WHERE c.pod=p.pod_no AND c.device_serial_number<>'' ORDER BY c.consumption_date DESC LIMIT 1) AS serial
     FROM pods p JOIN customers cu ON cu.customer_id=p.customer_id WHERE $where ORDER BY p.pod_no");
while ($p = $res->fetch_assoc()) {
    $key = $p['pod_no'];
    if (preg_replace('/[^A-Za-z0-9]/', '', (string)$key) === '') continue; // POD invalid (ex. ???)
    $row = [
        'pod'         => $key,
        'cod_client'  => (string)$p['customer_id'],
        'stare'       => ($p['pod_status'] === 'Activ') ? 'CONECTAT' : 'INACTIV',
        'tip_energie' => 'EE',
    ];
    if ($p['county'])  $row['judet']       = $p['county'];
    if ($p['city'])    $row['localitate']  = $p['city'];
    if ($p['address']) $row['strada']      = $p['address'];
    if ($p['serial'])  $row['serie_contor']= $p['serial'];
    // banda ANRE (doar EE): IA..IF trec direct, "Altii" -> IG, "Auto"/gol -> omit
    $band = strtoupper(trim((string)$p['anre_band']));
    if ($band === 'ALTII') $band = 'IG';
    if (in_array($band, ['IA','IB','IC','ID','IE','IF','IG'], true)) $row['banda_anre'] = $band;
    $rows[$key] = $row;
}
logline('locuri in scope: ' . count($rows));

$state = state_load('locuri');
$changed = [];
foreach ($rows as $key => $row) {
    $h = row_hash($row);
    if (($state[$key] ?? null) !== $h) $changed[$key] = ['row' => $row, 'hash' => $h];
}
logline('changed (new/modified): ' . count($changed));
if (!$changed) { logline('nothing to sync.'); logline('=== done ==='); exit(0); }

$t = sync_commit('locuri', $changed, $batch, $dryOnly);
logline("result: created={$t['created']} updated={$t['updated']} unchanged={$t['unchanged']} rejected={$t['rejected']} saved={$t['saved']} failed={$t['failed']}");
logline('=== done ===');
