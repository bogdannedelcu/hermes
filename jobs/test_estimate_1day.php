<?php
/**
 * Mirror of /var/www/ebs/jobs/test_estimate_1day.php — same test on ebsv2 to
 * compare wall-clock now that v2/v3/v4 auto-run is gated off by default.
 *
 * Usage: php8.2 /var/www/ebsv2/jobs/test_estimate_1day.php [customer_id]
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

$customer = isset($argv[1]) ? (int)$argv[1] : 592;   // Tribunal Tulcea default
$eStart = '2026-04-01';
$eEnd   = '2026-04-01';

echo "=== ebsv2 estimate test (v1-only by default) ===\n";
echo "Customer: $customer\n";
echo "Period:   $eStart .. $eEnd  (1 zi)\n\n";

$t0 = microtime(true);
$model = new \App\Models\Prognoza\ForecastModel();
// IMPORTANT: do NOT enable extra algorithms — match production behaviour
$result = $model->estimate($eStart, $eEnd, [$customer], '');
$dur = round(microtime(true) - $t0, 2);

echo "\nTOTAL elapsed: {$dur}s\n";
echo "Return: " . strip_tags(substr($result, 0, 500)) . "\n";
