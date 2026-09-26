<?php
/**
 * VoltApp sync — shared library.
 * Change detection via content-hash stored in `sync_state` (no updated_at on app tables).
 */

function cfg() { static $c; return $c ?: ($c = require __DIR__ . '/config.php'); }

function db() {
    static $m;
    if ($m) return $m;
    $c = cfg()['db'];
    $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['name']);
    if ($m->connect_errno) { fwrite(STDERR, "DB connect failed: {$m->connect_error}\n"); exit(2); }
    $m->set_charset('utf8mb4');
    return $m;
}

function logline($msg) {
    $line = date('Y-m-d H:i:s') . ' | ' . $msg . "\n";
    echo $line;
    if (!empty($GLOBALS['LOGFILE'])) @file_put_contents($GLOBALS['LOGFILE'], $line, FILE_APPEND | LOCK_EX);
    if (isset($GLOBALS['JOB_BUF'])) {
        $GLOBALS['JOB_BUF'][] = $line;
        if (count($GLOBALS['JOB_BUF']) > 300) array_shift($GLOBALS['JOB_BUF']);
    }
}

/** Acoperire zilnica: ce POD s-a citit prin API in ziua X (pt chart). */
function ensure_pod_reads_table() {
    db()->query("CREATE TABLE IF NOT EXISTS api_pod_reads (
        read_date   DATE        NOT NULL,
        pod         VARCHAR(40) NOT NULL,
        source      VARCHAR(20) NOT NULL,
        captured_at DATETIME    NOT NULL,
        PRIMARY KEY (read_date, pod, source), KEY k_date (read_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Marcheaza PODurile citite intr-o zi printr-o sursa API. Fail-safe. */
function api_pod_reads_record($source, $date, array $pods) {
    if (!$pods) return;
    try {
        ensure_pod_reads_table();
        $s = db()->real_escape_string($source);
        $d = db()->real_escape_string(substr($date, 0, 10));
        $vals = [];
        foreach (array_unique($pods) as $p) {
            if ($p === '' || $p === null) continue;
            $vals[] = "('$d','" . db()->real_escape_string((string)$p) . "','$s',NOW())";
        }
        foreach (array_chunk($vals, 1000) as $ch)
            db()->query("INSERT IGNORE INTO api_pod_reads (read_date,pod,source,captured_at) VALUES " . implode(',', $ch));
    } catch (\Throwable $e) { /* nu strica jobul */ }
}

/** Jurnal joburi (idempotent). */
function ensure_job_table() {
    db()->query("CREATE TABLE IF NOT EXISTS sync_job_runs (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        job         VARCHAR(40)  NOT NULL,
        started_at  DATETIME     NOT NULL,
        finished_at DATETIME     DEFAULT NULL,
        status      ENUM('running','ok','fail') NOT NULL DEFAULT 'running',
        summary     VARCHAR(255) DEFAULT NULL,
        details     MEDIUMTEXT,
        PRIMARY KEY (id), KEY k_job (job), KEY k_started (started_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Deschide un rand de jurnal pentru rularea curenta. Instrumentare minima: 1 linie in job.
 * Inchiderea e automata la finalul procesului (shutdown), cu status ok/fail si summary
 * extras din ultima linie `result:`. Nu arunca niciodata (nu poate strica sincronizarea).
 */
function job_begin($job) {
    try {
        ensure_job_table();
        $GLOBALS['JOB_BUF'] = [];
        $GLOBALS['JOB_DONE'] = false;
        $j = db()->real_escape_string($job);
        db()->query("INSERT INTO sync_job_runs (job, started_at, status) VALUES ('$j', NOW(), 'running')");
        $GLOBALS['JOB_ID'] = db()->insert_id;
        register_shutdown_function(function () {
            if (empty($GLOBALS['JOB_DONE'])) {
                $e = error_get_last();
                $fatal = $e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
                job_end($fatal ? 'fail' : 'ok');
            }
        });
        return $GLOBALS['JOB_ID'];
    } catch (\Throwable $e) { return null; }
}

/** Inchide randul de jurnal (o singura data). $status implicit 'ok'; downgrade la 'fail' daca logul are FAIL. */
function job_end($status = 'ok', $summary = '') {
    try {
        if (!empty($GLOBALS['JOB_DONE'])) return;
        $GLOBALS['JOB_DONE'] = true;
        $id = $GLOBALS['JOB_ID'] ?? 0;
        if (!$id) return;
        $buf = $GLOBALS['JOB_BUF'] ?? [];
        if ($summary === '') {
            foreach (array_reverse($buf) as $ln) {
                if (stripos($ln, 'result:') !== false || stripos($ln, 'created=') !== false) {
                    $summary = trim(preg_replace('/^\S+ \S+ \| /', '', rtrim($ln))); break;
                }
            }
        }
        if ($summary === '') {   // fallback: ultima linie relevanta (ex. "nothing to sync.")
            foreach (array_reverse($buf) as $ln) {
                $t = trim(preg_replace('/^\S+ \S+ \| /', '', rtrim($ln)));
                if ($t !== '' && strpos($t, '===') === false) { $summary = $t; break; }
            }
        }
        foreach ($buf as $ln) { if (preg_match('/\bFAIL\b|fail=[1-9]/', $ln)) { if ($status === 'ok') $status = 'fail'; break; } }
        $details = implode('', array_slice($buf, -60));
        $st = db()->prepare("UPDATE sync_job_runs SET finished_at=NOW(), status=?, summary=?, details=? WHERE id=?");
        $st->bind_param('sssi', $status, $summary, $details, $id);
        $st->execute(); $st->close();
    } catch (\Throwable $e) { /* jurnalul nu trebuie sa strice jobul */ }
}

/** Ensure the sync_state table exists (idempotent). */
function ensure_state_table() {
    db()->query("CREATE TABLE IF NOT EXISTS sync_state (
        entity         VARCHAR(20)  NOT NULL,
        record_key     VARCHAR(64)  NOT NULL,
        synced_hash    CHAR(32)     NOT NULL,
        synced_version INT          DEFAULT NULL,
        synced_at      DATETIME     NOT NULL,
        last_batch     VARCHAR(80)  DEFAULT NULL,
        PRIMARY KEY (entity, record_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Load synced hashes for an entity => [record_key => synced_hash]. */
function state_load($entity) {
    $out = [];
    $e = db()->real_escape_string($entity);
    $r = db()->query("SELECT record_key, synced_hash FROM sync_state WHERE entity='$e'");
    while ($row = $r->fetch_assoc()) $out[$row['record_key']] = $row['synced_hash'];
    return $out;
}

/** Persist a synced record (upsert into sync_state). */
function state_save($entity, $key, $hash, $version, $batch) {
    $st = db()->prepare("INSERT INTO sync_state (entity, record_key, synced_hash, synced_version, synced_at, last_batch)
        VALUES (?,?,?,?,NOW(),?)
        ON DUPLICATE KEY UPDATE synced_hash=VALUES(synced_hash), synced_version=VALUES(synced_version),
                                synced_at=NOW(), last_batch=VALUES(last_batch)");
    $st->bind_param('sssis', $entity, $key, $hash, $version, $batch);
    $st->execute();
    $st->close();
}

/** Stable content hash of a VoltApp row (order-independent). */
function row_hash(array $row) {
    ksort($row);
    return md5(json_encode($row, JSON_UNESCAPED_UNICODE));
}

/** CUI (normalized, no RO/spaces) => customer_id, for enabled customers. */
function cui_to_customer() {
    $map = [];
    $r = db()->query("SELECT customer_id, customer_vat_code FROM customers WHERE customer_vat_code IS NOT NULL AND customer_vat_code<>''");
    while ($row = $r->fetch_assoc()) {
        $cui = strtoupper(preg_replace('/[^0-9A-Z]/', '', $row['customer_vat_code']));
        if (strpos($cui, 'RO') === 0) $cui = substr($cui, 2);
        $map[$cui] = (int)$row['customer_id'];
    }
    return $map;
}

/**
 * Fetch ONE month window from apih.php. The `from` param returns invoices from that
 * date to the END of its month, so one call = one month. => [normalized_cui => [invoice,...]].
 */
function apih_fetch_month($fromDate) {
    $c  = cfg()['apih'];
    $ch = curl_init($c['url'] . '?from=' . urlencode($fromDate));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) { fwrite(STDERR, "apih $fromDate HTTP $code\n"); return []; }
    $d = json_decode($body, true) ?: [];
    $feed = [];
    foreach ($d as $k => $rows) {
        $cui = strtoupper(preg_replace('/[^0-9A-Z]/', '', $k));
        if (strpos($cui, 'RO') === 0) $cui = substr($cui, 2);
        $feed[$cui] = array_merge($feed[$cui] ?? [], $rows);
    }
    return $feed;
}

/**
 * Fetch ALL months from `$from`'s month up to the CURRENT month, merged.
 * apih's `from` is a single-month window, so we iterate month by month to cover history.
 * => [normalized_cui => [invoice, ...]].
 */
function apih_fetch($from = null) {
    $from = $from ?: cfg()['apih']['from'];
    $cur  = new DateTime(substr($from, 0, 7) . '-01');
    $end  = new DateTime(date('Y-m') . '-01');
    $feed = [];
    while ($cur <= $end) {
        foreach (apih_fetch_month($cur->format('Y-m-01')) as $cui => $rows)
            $feed[$cui] = array_merge($feed[$cui] ?? [], $rows);
        $cur->modify('+1 month');
    }
    return $feed;
}

/** Fetch an invoice document (pdf/xml) from apih by pdfid. Returns temp file path or false. */
function apih_pdf($fid, $tip = 'pdf') {
    $c   = cfg()['apih'];
    $url = $c['url'] . '?pdfid=' . urlencode($fid);
    $tmp = sys_get_temp_dir() . '/apih_' . preg_replace('/\W/', '', $fid) . '.' . $tip;
    $fp  = fopen($tmp, 'w');
    $ch  = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => true]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch); fclose($fp);
    if ($code !== 200 || @filesize($tmp) < 100) { @unlink($tmp); return false; }
    $head = file_get_contents($tmp, false, null, 0, 5);
    if ($tip === 'pdf' && strpos($head, '%PDF') !== 0) { @unlink($tmp); return false; }
    return $tmp;
}

/** Request an invoice-file upload ticket. Returns ticket array (with _http) or null. */
function volt_invoice_ticket($numar, $tip = 'pdf') {
    $c  = cfg()['voltapp'];
    $ch = curl_init($c['base'] . '/v1/invoices/files');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => ['x-api-key: ' . $c['api_key'], 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['numarFactura' => $numar, 'tip' => $tip]),
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $r = json_decode($body, true);
    if ($r === null) return null;
    $r['_http'] = $code;
    return $r;
}

/** PUT a file to the signed upload URL with the verbatim headers from the ticket. Returns http code. */
function volt_put_file($url, $file, array $headers) {
    $h = ['Expect:'];
    foreach ($headers as $k => $v) $h[] = "$k: $v";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => file_get_contents($file), CURLOPT_HTTPHEADER => $h, CURLOPT_TIMEOUT => 180,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

/**
 * Send changed rows in chunks: dry-run then commit, saving sync_state ONLY for
 * rows the server accepted in a 2xx response. Rejected rows (by index) are skipped
 * so they retry next run. $changed: [key => ['row'=>array,'hash'=>string]] (order = send order).
 */
function sync_commit($entity, array $changed, $batchBase, $dryOnly = false, $chunk = 500) {
    $keys = array_keys($changed);
    $t = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'rejected' => 0, 'saved' => 0, 'failed' => 0];
    foreach (array_chunk($keys, $chunk) as $ci => $gkeys) {
        $send  = array_map(fn($k) => $changed[$k]['row'], $gkeys);
        $batch = $batchBase . '-' . ($ci + 1);

        $dr = volt_portfolio($entity, $send, $batch, true);
        if (!$dr || ($dr['_http'] ?? 0) !== 200) {
            logline("chunk " . ($ci + 1) . ": DRY http=" . ($dr['_http'] ?? '?') . " -> skip, retry next run");
            $t['failed'] += count($gkeys); continue;
        }
        if (($dr['rejected'] ?? 0) > 0)
            logline("chunk " . ($ci + 1) . " dry rejected {$dr['rejected']}: " . json_encode(array_slice($dr['rejectedRows'], 0, 5), JSON_UNESCAPED_UNICODE));
        if ($dryOnly) { $t['created'] += $dr['created'] ?? 0; $t['unchanged'] += $dr['unchanged'] ?? 0; $t['rejected'] += $dr['rejected'] ?? 0; continue; }

        $rs = volt_portfolio($entity, $send, $batch, false);
        if (!$rs || !in_array($rs['_http'] ?? 0, [200, 202], true)) {
            logline("chunk " . ($ci + 1) . ": COMMIT http=" . ($rs['_http'] ?? '?') . " -> NOT saved, retry next run");
            $t['failed'] += count($gkeys); continue;
        }
        $t['created'] += $rs['created'] ?? 0; $t['updated'] += $rs['updated'] ?? 0;
        $t['unchanged'] += $rs['unchanged'] ?? 0; $t['rejected'] += $rs['rejected'] ?? 0;

        $rej = [];
        foreach (($rs['rejectedRows'] ?? []) as $rr) if (isset($rr['index'])) $rej[$rr['index']] = $rr['reason'] ?? '';
        foreach ($gkeys as $idx => $k) {
            if (isset($rej[$idx])) { logline("  rejected [$k]: " . $rej[$idx]); continue; }
            state_save($entity, $k, $changed[$k]['hash'], null, $batch);
            $t['saved']++;
        }
    }
    return $t;
}

/** Romania DST offset (hours) for a given local DateTime: +3 EEST (summer), +2 EET (winter). */
function ro_offset(DateTime $dt) {
    $y = (int)$dt->format('Y');
    $ds = new DateTime("$y-03-31"); while ((int)$ds->format('w') !== 0) $ds->modify('-1 day'); $ds->setTime(3, 0);
    $de = new DateTime("$y-10-31"); while ((int)$de->format('w') !== 0) $de->modify('-1 day'); $de->setTime(4, 0);
    return ($dt >= $ds && $dt < $de) ? 3 : 2;
}

/** Local RO 'Y-m-d H:i:s' -> UTC 'Y-m-dTH:i:SZ'. */
function ro_to_utc_z($localStr) {
    $dt = new DateTime($localStr);
    $dt->modify('-' . ro_offset($dt) . ' hours');
    return $dt->format('Y-m-d\TH:i:s\Z');
}

/** POST one readings batch to VoltApp (/v1/readings). Returns decoded response (with _http) or null. */
function volt_readings($batchId, array $rows, $dryRun = false) {
    $c = cfg()['voltapp'];
    $payload = ['batchId' => $batchId, 'readings' => array_values($rows)];
    if ($dryRun) $payload['dryRun'] = true;
    $ch = curl_init($c['base'] . '/v1/readings');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTPHEADER => ['x-api-key: ' . $c['api_key'], 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $r = json_decode($body, true);
    if ($r === null) { fwrite(STDERR, "volt_readings HTTP $code body=$body\n"); return null; }
    $r['_http'] = $code;
    return $r;
}

/**
 * GET from the distributor load-profile API. Returns [http_code, body_string].
 * TLS cert chain is incomplete on their side -> peer/host verification disabled (read-only GET).
 */
function distrib_get($path) {
    $c  = cfg()['distributie'];
    $ch = curl_init($c['base'] . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 360,     // streaming responses can be slow (read timeout >= 5 min)
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => ['X-Api-Key: ' . $c['api_key']],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

/**
 * Fetch the FULL previous-day load profile, paginated by cursor (afterMeter/nextCursor).
 * Safety per the distributor spec:
 *   - STOP immediately on 401 (avoid the 15-min auth block) and on 429.
 *   - 503 => outside the 10:15-23:00 window; caller should reschedule.
 *   - streaming may truncate with HTTP 200 => require final fields; retry the SAME page if missing.
 * Returns ['ok'=>true,'date'=>'Y-m-d','meters'=>[...]] or ['ok'=>false,'reason'=>'...'].
 */
function distrib_loadprofile() {
    $c     = cfg()['distributie'];
    $take  = (int)($c['take'] ?? 2000);
    $after = 0; $date = null; $all = []; $pages = 0;

    while (true) {
        if (++$pages > 500) return ['ok' => false, 'reason' => 'too many pages (cursor loop?)'];

        $d = null;
        for ($try = 1; $try <= 4; $try++) {
            [$code, $body] = distrib_get("/api/loadprofile?take=$take&afterMeter=$after");
            if ($code === 401) return ['ok' => false, 'reason' => '401 Unauthorized -> STOP (avoid 15-min block)'];
            if ($code === 429) return ['ok' => false, 'reason' => '429 rate-limit/lock -> stop'];
            if ($code === 503) return ['ok' => false, 'reason' => '503 in afara ferestrei 10:15-23:00'];
            if ($code !== 200) { logline("  loadprofile HTTP $code (try $try) -> backoff"); sleep(5 * $try); continue; }

            $d = json_decode($body, true);
            // truncation guard: the final fields must all be present
            if (!is_array($d) || !array_key_exists('hasMore', $d)
                || !array_key_exists('nextCursor', $d) || !array_key_exists('meterCount', $d)) {
                logline("  JSON incomplet (try $try) -> reiau aceeasi pagina"); $d = null; sleep(5 * $try); continue;
            }
            break;
        }
        if ($d === null) return ['ok' => false, 'reason' => 'raspuns incomplet dupa reincercari'];

        $date = $date ?? ($d['date'] ?? null);
        foreach (($d['meters'] ?? []) as $m) $all[] = $m;

        if (empty($d['hasMore'])) break;
        $after = $d['nextCursor'];
        usleep(300000);   // throttle usor intre pagini (limita 120 req/min)
    }
    return ['ok' => true, 'date' => $date, 'meters' => $all];
}

/** POST one portfolio batch to VoltApp. Returns decoded response or null. */
function volt_portfolio($entity, array $rows, $batchId, $dryRun = false) {
    $c = cfg()['voltapp'];
    $payload = json_encode(['entity' => $entity, 'batchId' => $batchId, 'dryRun' => $dryRun, 'rows' => array_values($rows)], JSON_UNESCAPED_UNICODE);
    $ch = curl_init($c['base'] . '/v1/portfolio');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => ['x-api-key: ' . $c['api_key'], 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $resp = json_decode($body, true);
    if ($resp === null) { fwrite(STDERR, "volt_portfolio HTTP $code body=$body\n"); return null; }
    $resp['_http'] = $code;
    return $resp;
}
