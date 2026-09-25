<?php
/**
 * Trigger estimate() for Tribunal Tulcea (#592), April 2026, ALL 4 phases (v1/v2/v3/v4-Chronos).
 * Replicates what the UI does when you press "Estimeaza" with cust=592 and date range April.
 *
 * Usage: php8.2 /var/www/ebsv2/jobs/run_tribunal_april.php
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

$_SESSION = $_SESSION ?? [];
$_SESSION['select-supplier'] = 1;

$eStart    = '2026-04-01';
$eEnd      = '2026-04-30';
$customers = [592];

echo "=== Estimating Tribunal Tulcea (#592) for {$eStart} → {$eEnd} ===\n";
$t0 = microtime(true);
$model = new \App\Models\Prognoza\ForecastModel();
$model->setRunExtraAlgorithms(true);   // ablation script — exercise v1+v2+v3+v4
$result = $model->estimate($eStart, $eEnd, $customers, '');
$dur = round((microtime(true) - $t0), 1);
echo "Elapsed: {$dur}s\n";
echo "Return: " . strip_tags(substr($result, 0, 2000)) . "\n\n";

// Verify all 4 algorithms wrote rows
$db = \Config\Database::connect();
echo "=== forecast_estimates row counts ===\n";
$sql = "SELECT algorithm, COUNT(*) AS n, ROUND(SUM(forecast_ea),3) AS sum_ea
        FROM forecast_estimates
        WHERE customer_id=592 AND supplier_id=1
          AND forecast_datetime >= '$eStart' AND forecast_datetime < DATE_ADD('$eEnd', INTERVAL 1 DAY)
        GROUP BY algorithm ORDER BY algorithm";
foreach ($db->query($sql)->getResultArray() as $r) {
    printf("  %-4s  n=%5d  sum=%9.3f kWh\n", $r['algorithm'], $r['n'], $r['sum_ea']);
}

echo "\n=== forecast_pod_estimates row counts per POD ===\n";
$sql = "SELECT pod, algorithm, COUNT(*) AS n
        FROM forecast_pod_estimates
        WHERE customer_id=592 AND supplier_id=1
          AND forecast_datetime >= '$eStart' AND forecast_datetime < DATE_ADD('$eEnd', INTERVAL 1 DAY)
        GROUP BY pod, algorithm
        ORDER BY pod, algorithm";
foreach ($db->query($sql)->getResultArray() as $r) {
    printf("  %-24s  %-4s  n=%4d\n", $r['pod'], $r['algorithm'], $r['n']);
}

echo "\n=== accuracy log for April 2026 ===\n";
$sql = "SELECT algorithm, ROUND(mape,2) AS mape, ROUND(bias_pct,2) AS bias, n_intervals
        FROM forecast_accuracy_log
        WHERE customer_id=592 AND supplier_id=1 AND stat_month='2026-04-01'
        ORDER BY algorithm";
foreach ($db->query($sql)->getResultArray() as $r) {
    printf("  %-4s  MAPE=%6.2f%%  bias=%+6.2f%%  n=%d\n",
        $r['algorithm'], $r['mape'], $r['bias'], $r['n_intervals']);
}
echo "\nDone.\n";
