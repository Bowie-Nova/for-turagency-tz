<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PuppeteerParserService;

$service = new PuppeteerParserService();
$filters = [
    'departure_city' => 'Almaty',
    'destination_country' => 'Turkey',
    'departure_from' => '2026-05-01',
    'departure_to' => '2026-05-05',
    'nights_from' => 6,
    'nights_to' => 13,
    'adults' => 2,
    'children' => 0
];

echo "Calling parseAllOperators with filters:\n";
print_r($filters);

$result = $service->parseAllOperators($filters);

// if debug HTML was returned (base64-encoded), decode and save for inspection
if (isset($result['debug_tables_by_operator']) && is_array($result['debug_tables_by_operator'])) {
    foreach ($result['debug_tables_by_operator'] as $op => $b64) {
        try {
            $html = base64_decode($b64, true);
            if ($html !== false) {
                // write outside laravel folder in the workspace puppeteer/debug
            $puppeteerRoot = dirname(base_path()) . DIRECTORY_SEPARATOR . 'puppeteer';
            if (!is_dir($puppeteerRoot . DIRECTORY_SEPARATOR . 'debug')) {
                mkdir($puppeteerRoot . DIRECTORY_SEPARATOR . 'debug', 0777, true);
            }
            $path = $puppeteerRoot . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . $op . '_table_res.html';
            file_put_contents($path, $html);
            echo "\nWrote decoded debug HTML for operator $op to $path\n";
            } else {
                echo "\nFailed to base64 decode debug HTML for $op\n";
            }
        } catch (\Throwable $e) {
            echo "\nException decoding debug HTML for $op: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n\nResult:\n";
print_r($result);

echo "\n\nFinded rows count:\n";
echo count($result) . "\n";

echo "\n\nJSON encoded result:\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
