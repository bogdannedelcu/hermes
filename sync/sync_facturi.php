<?php
/**
 * Daily invoice sync: apih.php feed -> VoltApp /v1/portfolio (entity=facturi).
 * Source of truth = real issued invoices from apih.php (seria_nr = fiscal number).
 * Only customers in config['enabled_customers'] are synced.
 *
 * Change detection: content-hash in sync_state. Only new/changed invoices are sent.
 * Usage:  php sync_facturi.php [--from=YYYY-MM-DD] [--dry]
 */

require __DIR__ . '/lib.php';
job_begin('facturi');

$opts    = getopt('', ['from::', 'dry']);
$from    = $opts['from'] ?? cfg()['apih']['from'];
$dryOnly = isset($opts['dry']);
$batch   = 'ebs-facturi-sync-' . date('Ymd-His');

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/facturi-' . date('Y-m-d') . '.log';
ensure_state_table();

$enabled = array_flip(cfg()['enabled_customers']);   // customer_id => idx
$cuiMap  = cui_to_customer();                          // normalized CUI => customer_id
$feed    = apih_fetch($from);                          // CUI => [invoice, ...]

// zile de scadenta per client (pt. calcul scadenta = data facturii + zile)
$dueDays = [];
$rd = db()->query("SELECT customer_id, customer_invoice_due_days FROM customers");
while ($x = $rd->fetch_assoc()) $dueDays[(int)$x['customer_id']] = (int)$x['customer_invoice_due_days'];

// Build VoltApp rows for enabled customers only
$rows = [];
foreach ($feed as $cui => $invoices) {
    if (!isset($cuiMap[$cui])) continue;               // client necunoscut local
    $cid = $cuiMap[$cui];
    if ($enabled && !isset($enabled[$cid])) continue;  // in afara scope-ului pilot
    foreach ($invoices as $f) {
        $key  = $f['seria_nr'];
        $data = $f['data'];                                         // data facturii (YYYY-MM-DD)
        $due  = $dueDays[$cid] ?? 0;
        $row  = [
            'numar_factura'  => $key,
            'cod_client'     => (string)$cid,
            'perioada_start' => substr($data, 0, 7) . '-01',        // inceputul lunii facturate (derivat)
            'perioada_final' => $data,                              // feed n-are perioada -> data facturii
            'total'          => $f['total_factura'],                // cu TVA
            'tip_citire'     => 'REALA',
        ];
        if ($due > 0) $row['scadenta'] = date('Y-m-d', strtotime("$data +$due days"));  // scadenta = data + zile client
        $rows[$key] = $row;
    }
}
logline("feed from=$from | facturi in scope: " . count($rows));

// Diff against sync_state (send only new/changed)
$state = state_load('facturi');
$changed = [];
foreach ($rows as $key => $row) {
    $h = row_hash($row);
    if (!isset($state[$key]) || $state[$key] !== $h) $changed[$key] = ['row' => $row, 'hash' => $h];
}
logline('changed (new/modified): ' . count($changed));
if (!$changed) { logline('nothing to sync.'); exit(0); }

$t = sync_commit('facturi', $changed, $batch, $dryOnly);
logline("result: created={$t['created']} updated={$t['updated']} unchanged={$t['unchanged']} rejected={$t['rejected']} saved={$t['saved']} failed={$t['failed']}");
logline('=== done ===');
