<?php
/**
 * Daily invoice-PDF sync: apih.php (pdfid) -> VoltApp POST /v1/invoices/files (tip=pdf).
 * Attaches the real invoice PDF to each invoice already imported into VoltApp.
 *
 * Only invoices present in sync_state 'facturi' are eligible (VoltApp 404s otherwise).
 * Idempotency: sync_state 'documente', key=numar_factura, hash=pdfid. A changed pdfid re-uploads.
 *
 * Daily log: sync/logs/documente-YYYY-MM-DD.log
 * Usage: php8.2 sync_documente.php [--dry] [--from=YYYY-MM-DD]
 */

require __DIR__ . '/lib.php';
job_begin('documente');

$GLOBALS['LOGFILE'] = __DIR__ . '/logs/documente-' . date('Y-m-d') . '.log';
$opts    = getopt('', ['from::', 'dry']);
$from    = $opts['from'] ?? cfg()['apih']['from'];
$dryOnly = isset($opts['dry']);
$batch   = 'ebs-doc-sync-' . date('Ymd-His');

ensure_state_table();
logline("=== sync documente start (batch=$batch) ===");

$enabled  = array_flip(cfg()['enabled_customers']);   // empty = all
$cuiMap   = cui_to_customer();
$feed     = apih_fetch($from);
$inVolt   = state_load('facturi');                     // invoices we imported => eligible for a PDF

// seria_nr => pdfid, only for invoices already in VoltApp and in scope
$want = [];
foreach ($feed as $cui => $invoices) {
    if (!isset($cuiMap[$cui])) continue;
    $cid = $cuiMap[$cui];
    if ($enabled && !isset($enabled[$cid])) continue;
    foreach ($invoices as $f) {
        $seria = $f['seria_nr'];
        if (!isset($inVolt[$seria]) || empty($f['fid'])) continue;
        $want[$seria] = (string)$f['fid'];
    }
}
logline('facturi in VoltApp cu pdfid disponibil: ' . count($want));

$state = state_load('documente');
$todo  = [];
foreach ($want as $seria => $fid) {
    if (($state[$seria] ?? null) !== $fid) $todo[$seria] = $fid;
}
logline('de urcat (noi/modificate): ' . count($todo));
if (!$todo) { logline('nothing to do.'); logline('=== done ==='); exit(0); }

if ($dryOnly) {
    foreach (array_slice($todo, 0, 20, true) as $s => $f) logline("  would upload: $s (pdfid=$f)");
    logline('--dry set, stop.'); logline('=== done ==='); exit(0);
}

$ok = 0; $fail = 0;
foreach ($todo as $seria => $fid) {
    $pdf = apih_pdf($fid, 'pdf');
    if (!$pdf) { logline("  $seria: PDF indisponibil (pdfid=$fid)"); $fail++; continue; }

    $tk = volt_invoice_ticket($seria, 'pdf');
    if (!$tk || ($tk['_http'] ?? 0) !== 200 || empty($tk['uploadUrl'])) {
        logline("  $seria: ticket http=" . ($tk['_http'] ?? '?') . ' ' . json_encode($tk['error'] ?? '', JSON_UNESCAPED_UNICODE));
        @unlink($pdf); $fail++; continue;
    }
    $hdr  = [
        'content-type'    => $tk['headers']['content-type'] ?? 'application/pdf',
        'x-goog-meta-rid' => $tk['headers']['x-goog-meta-rid'] ?? '',
    ];
    $code = volt_put_file($tk['uploadUrl'], $pdf, $hdr);
    $size = filesize($pdf);
    @unlink($pdf);

    if ($code === 200) {
        state_save('documente', $seria, $fid, null, $batch);
        $ok++;
        logline("  $seria: uploaded pdf (${size}B, pdfid=$fid)");
    } else {
        logline("  $seria: PUT http=$code (retry next run)");
        $fail++;
    }
}
logline("result: uploaded=$ok failed=$fail");
logline('=== done ===');
