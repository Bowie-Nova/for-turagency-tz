<?php
// Test what structure comes back from puppeteer

chdir(__DIR__ . '/laravel');
require 'vendor/autoload.php';

use App\Services\PuppeteerParserService;

$parser = new PuppeteerParserService();

$filters = [
    'departure_city' => 'Almaty',
    'destination_country' => 'Turkey',
    'hotel_category' => 4,
    'departure_from' => '2026-05-01',
    'departure_to' => '2026-05-07',
    'nights_from' => 6,
    'nights_to' => 13,
    'adults' => 2,
    'children' => 0,
    'preferences' => []
];

echo "Testing PuppeteerParserService::parseAllOperators()\n";
echo "================================================\n\n";

$result = $parser->parseAllOperators($filters);

echo "Result structure:\n";
var_dump($result);

echo "\n\nKeys in result:\n";
print_r(array_keys($result));

echo "\n\nTypes of result values:\n";
foreach ($result as $key => $value) {
    echo "  {$key}: " . (is_array($value) ? "array(" . count($value) . ")" : gettype($value)) . "\n";
    if (is_array($value) && count($value) > 0 && isset($value[0])) {
        echo "    First element type: " . (is_array($value[0]) ? "array" : gettype($value[0])) . "\n";
    }
}
