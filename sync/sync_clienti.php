<?php
/**
 * Daily client sync: ebs.customers -> VoltApp /v1/portfolio (entity=clienti).
 * Change detection: content-hash in sync_state (customers has no updated_at).
 * Only customers in config['enabled_customers'] are synced (empty = all).
 *
 * Daily log: sync/logs/clienti-YYYY-MM-DD.log
 * Usage:  php8.2 sync_clienti.php [--dry]
 */

require __DIR__ . '/lib.php';

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/clienti-' . date('Y-m-d') . '.log';
$dryOnly = in_array('--dry', $argv, true);
$batch   = 'ebs-clienti-sync-' . date('Ymd-His');

ensure_state_table();
logline("=== sync clienti start (batch=$batch) ===");

// Scope
$enabled = cfg()['enabled_customers'];
$where   = $enabled ? 'WHERE customer_id IN (' . implode(',', array_map('intval', $enabled)) . ')' : '';

// Build VoltApp rows from DB
$rows = [];
$res = db()->query("SELECT customer_id, customer_name, customer_vat_code, customer_status FROM customers $where ORDER BY customer_id");
while ($c = $res->fetch_assoc()) {
    $key = (string)$c['customer_id'];
    $row = [
        'cod_client' => $key,
        'nume'       => $c['customer_name'],
        'tip'        => 'PJ',
        'activ'      => ($c['customer_status'] === 'Activ') ? 'da' : 'nu',
    ];
    $cui = strtoupper(preg_replace('/[^0-9A-Z]/', '', (string)$c['customer_vat_code']));
    if (strpos($cui, 'RO') === 0) $cui = substr($cui, 2);
    if ($cui !== '' && $cui !== '-') $row['cui'] = $cui;
    $rows[$key] = $row;
}
logline('clienti in scope: ' . count($rows));

// Diff against sync_state
$state = state_load('clienti');
$changed = [];
foreach ($rows as $key => $row) {
    $h = row_hash($row);
    if (($state[$key] ?? null) !== $h) $changed[$key] = ['row' => $row, 'hash' => $h];
}
logline('changed (new/modified): ' . count($changed));
if (!$changed) { logline('nothing to sync.'); logline('=== done ==='); exit(0); }

$t = sync_commit('clienti', $changed, $batch, $dryOnly);
logline("result: created={$t['created']} updated={$t['updated']} unchanged={$t['unchanged']} rejected={$t['rejected']} saved={$t['saved']} failed={$t['failed']}");
logline('=== done ===');
