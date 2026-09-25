<?php
/**
 * One-shot CLI: re-run estimate() for a fixed set of customers + month, then
 * print accuracy_log diffs (v1/v2/v3/v4) for comparison.
 *
 * Usage:  php8.2 /var/www/ebsv2/jobs/run_estimate_ablation.php
 */

define('FCPATH', __DIR__ . '/../public/');
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

$app = Config\Services::codeigniter();
$app->initialize();
$app->setContext('php-cli');

// ForecastModel reads supplier from session — fake one.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $_SESSION = $_SESSION ?? [];
}
$_SESSION['select-supplier'] = 1;

$eStart      = '2026-04-01';
$eEnd        = '2026-04-30';
$customers   = [388, 390, 392, 397, 399, 403, 407, 424, 592];
$customerType = ''; // unused for these explicit IDs

echo "=== Ablation run ===\n";
echo "Period:     $eStart .. $eEnd\n";
echo "Customers:  ".implode(',', $customers)."\n";
echo "Supplier:   1\n\n";

$t0 = microtime(true);
$model = new \App\Models\Prognoza\ForecastModel();
$model->setRunExtraAlgorithms(true);   // ablation script — exercise v1+v2+v3+v4
$result = $model->estimate($eStart, $eEnd, $customers, $customerType);
$dur = round((microtime(true) - $t0), 1);

echo "Elapsed:    {$dur}s\n";
echo "Returned:   ".strip_tags(substr($result, 0, 2000))."\n\n";

// ── Compare accuracy across algorithms ───────────────────────────────────────
$db = \Config\Database::connect();
$ids = implode(',', array_map('intval', $customers));

$sql = "SELECT customer_id, algorithm, ROUND(mape,2) AS mape, ROUND(bias_pct,2) AS bias, n_intervals
        FROM forecast_accuracy_log
        WHERE supplier_id=1 AND customer_id IN ($ids) AND stat_month='2026-04-01'
        ORDER BY customer_id, algorithm";

echo "=== forecast_accuracy_log — April 2026 ===\n";
$rows = $db->query($sql)->getResultArray();

// Pivot to customer × algorithm matrix
$grid = [];
foreach ($rows as $r) {
    $grid[$r['customer_id']][$r['algorithm']] = $r;
}

printf("%-6s | %-8s %-8s | %-8s %-8s | %-8s %-8s | %-8s %-8s\n",
    'CUST', 'v1-MAPE', 'v1-bias', 'v2-MAPE', 'v2-bias', 'v3-MAPE', 'v3-bias', 'v4-MAPE', 'v4-bias');
echo str_repeat('-', 84)."\n";
foreach ($customers as $c) {
    $line = sprintf("%-6d |", $c);
    foreach (['v1','v2','v3','v4'] as $alg) {
        if (isset($grid[$c][$alg])) {
            $line .= sprintf(" %7.2f%% %7.2f%% |", $grid[$c][$alg]['mape'], $grid[$c][$alg]['bias']);
        } else {
            $line .= sprintf(" %-8s %-8s |", '—', '—');
        }
    }
    echo "$line\n";
}

// ── Aggregate, by algorithm ─────────────────────────────────────────────────
echo "\n=== Aggregate per algorithm ===\n";
$sql = "SELECT algorithm,
               COUNT(*) AS n_cust,
               ROUND(AVG(mape), 2) AS avg_mape,
               ROUND(AVG(ABS(bias_pct)), 2) AS avg_abs_bias,
               ROUND(SUM(ABS(total_realized_kwh - total_estimated_kwh)), 4) AS total_abs_gap_kwh,
               ROUND(SUM(total_realized_kwh), 4) AS total_realized
        FROM forecast_accuracy_log
        WHERE supplier_id=1 AND customer_id IN ($ids) AND stat_month='2026-04-01'
        GROUP BY algorithm
        ORDER BY algorithm";
foreach ($db->query($sql)->getResultArray() as $r) {
    printf("%-4s  n=%d  avgMAPE=%6.2f%%  avg|bias|=%5.2f%%  sumAbsGap=%.4f kWh  realized=%.4f\n",
        $r['algorithm'], $r['n_cust'], $r['avg_mape'], $r['avg_abs_bias'],
        $r['total_abs_gap_kwh'], $r['total_realized']);
}
echo "\nDone.\n";
