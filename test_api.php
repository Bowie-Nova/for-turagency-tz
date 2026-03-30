<?php
require 'laravel/vendor/autoload.php';
$app = require_once 'laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Database ===\n";
try {
    $lead = App\Models\Lead::create([
        'name' => 'Test',
        'phone' => '+79998887766',
        'departure_city' => 'Almaty',
        'destination_country' => 'Turkey',
        'departure_from' => '2026-04-01',
        'departure_to' => '2026-04-08',
        'nights_from' => 7,
        'nights_to' => 7,
        'adults' => 1
    ]);
    echo "✓ Lead created with ID: " . $lead->id . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Parser Service ===\n";
try {
    $service = app(\App\Services\PuppeteerParserService::class);
    $filters = [
        'departure_city' => 'Almaty',
        'destination_country' => 'Turkey',
        'check_in_from' => '2026-04-01',
        'check_in_to' => '2026-04-08',
        'adults' => 1
    ];
    $result = $service->parseHtmlFile('puppeteer/debug/abk_table_res.html');
    echo "✓ Parser returned " . count($result) . " results\n";
    if (isset($result[0])) {
        echo "  Sample: " . json_encode(array_slice($result[0], 0, 3)) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Aggregator ===\n";
try {
    $aggregator = app(\App\Services\TourAggregatorService::class);
    $result = $aggregator->aggregate(['debug' => $result ?? []], [
        'departure_city' => 'Almaty',
        'destination_country' => 'Turkey',
        'departure_from' => '2026-04-01',
        'departure_to' => '2026-04-08',
        'nights_from' => 7,
        'nights_to' => 7
    ]);
    echo "✓ Aggregator returned " . count($result) . " tours\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
