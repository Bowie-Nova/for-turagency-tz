<?php
require 'laravel/vendor/autoload.php';
$app = require_once 'laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Exact Controller Flow ===\n\n";

// Create exactly what the controller creates
$validated = [
    'name' => 'Ivan Ivanov',
    'phone' => '+7 (777) 123-45-67',
    'email' => 'ivan@example.com',
    'departure_city' => 'Almaty',
    'destination_country' => 'Turkey',
    'hotel_category' => 4,
    'departure_from' => '2026-04-01',
    'departure_to' => '2026-04-08',
    'nights_from' => 7,
    'nights_to' => 7,
    'adults' => 2,
    'children' => 0
];

echo "1. Creating lead with validated data...\n";
$lead = \App\Models\Lead::create($validated);
echo "✓ Lead ID: " . $lead->id . "\n\n";

// Parse HTML
echo "2. Parsing HTML...\n";
$path = dirname(base_path()) . DIRECTORY_SEPARATOR . 'puppeteer' . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'abk_table_res.html';
$parserService = app(\App\Services\PuppeteerParserService::class);
$raw = $parserService->parseHtmlFile($path);
echo "✓ Parsed " . count($raw) . " tours\n\n";

// Aggregate
echo "3. Aggregating with validated array (not Lead::getSearchCriteria)...\n";
$aggregatorService = app(\App\Services\TourAggregatorService::class);
$aggregated = $aggregatorService->aggregate(
    ['debug' => $raw],
    $validated  // Pass the same data the controller passes
);
echo "✓ Aggregated " . count($aggregated) . " tours\n\n";

// Save
echo "4. Saving to database...\n";
foreach ($aggregated as $tour) {
    echo "  Saving: " . substr($tour['title'], 0, 40) . "...\n";
    $lead->tours()->create($tour);
}

// Verify
echo "\n5. Verifying saved tours...\n";
$savedTours = $lead->tours()->get();
echo "✓ Found " . count($savedTours) . " tours in database\n";
foreach (array_slice($savedTours->toArray(), 0, 3) as $tour) {
    echo "  - " . $tour['title'] . ": ₸" . $tour['price'] . "\n";
}
