<?php
require 'laravel/vendor/autoload.php';
$app = require_once 'laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Aggregator with Parsed Tours ===\n\n";

$filePath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'puppeteer' . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'abk_table_res.html';
$service = app(\App\Services\PuppeteerParserService::class);
$parsed = $service->parseHtmlFile($filePath);

echo "1. Parsed tours: " . count($parsed) . "\n\n";

// Create a lead with specific criteria
$criteria = [
    'departure_city' => 'Almaty',
    'destination_country' => 'Turkey',
    'hotel_category' => 4,
    'departure_from' => '2026-04-01',
    'departure_to' => '2026-05-30',
    'nights_from' => 5,
    'nights_to' => 10,
    'adults' => 2,
    'children' => 0
];

echo "2. Criteria:\n";
echo "   - departure_from: " . $criteria['departure_from'] . "\n";
echo "   - departure_to: " . $criteria['departure_to'] . "\n";
echo "   - nights_from: " . $criteria['nights_from'] . " to " . $criteria['nights_to'] . "\n";
echo "   - hotel_category: " . $criteria['hotel_category'] . "\n\n";

// Show first few tours
echo "3. Sample parsed tours:\n";
foreach (array_slice($parsed, 0, 3) as $idx => $tour) {
    echo "\n  Tour $idx:\n";
    echo "    title: " . substr($tour['title'], 0, 50) . "...\n";
    echo "    hotel: " . $tour['hotel_name'] . "\n";
    echo "    price: " . $tour['price'] . "\n";
    echo "    nights: " . $tour['nights'] . "\n";
    echo "    departure_date: " . $tour['departure_date'] . "\n";
}

// Test aggregator
echo "\n\n4. Running aggregator...\n";
$aggregator = app(\App\Services\TourAggregatorService::class);
$aggregated = $aggregator->aggregate(['debug' => $parsed], $criteria);
echo "✓ Aggregated tours: " . count($aggregated) . "\n";

if (count($aggregated) > 0) {
    echo "\n  Sample results:\n";
    foreach (array_slice($aggregated, 0, 3) as $tour) {
        echo "  - " . $tour['title'] . ": ₸" . $tour['price'] . " (score: " . $tour['popularity_score'] . ")\n";
    }
} else {
    echo "  WARNING: No tours passed filtering!\n\n";
    echo "5. Debug: Testing criteria matching on first 5 tours...\n";
    
    // Use reflection to access protected method
    $reflection = new ReflectionClass($aggregator);
    $method = $reflection->getMethod('meetsCriteria');
    $method->setAccessible(true);
    
    foreach (array_slice($parsed, 0, 5) as $idx => $tour) {
        $matches = $method->invoke($aggregator, $tour, $criteria);
        echo "\n  Tour $idx:\n";
        echo "    title: " . substr($tour['title'], 0, 40) . "\n";
        echo "    passes filters: " . ($matches ? 'YES' : 'NO') . "\n";
        
        // Check each filter manually
        echo "    - has title: " . (empty($tour['title']) ? 'NO' : 'YES') . "\n";
        echo "    - has hotel: " . (empty($tour['hotel_name']) ? 'NO' : 'YES') . "\n";
        echo "    - has price: " . (empty($tour['price']) ? 'NO' : 'YES (' . $tour['price'] . ')') . "\n";
        echo "    - nights (" . $tour['nights'] . ") in range [" . $criteria['nights_from'] . "-" . $criteria['nights_to'] . "]: " . 
            ($tour['nights'] >= $criteria['nights_from'] && $tour['nights'] <= $criteria['nights_to'] ? 'YES' : 'NO') . "\n";
        
        if (isset($tour['departure_date']) && $tour['departure_date']) {
            $tourDate = strtotime($tour['departure_date']);
            $dateFrom = strtotime($criteria['departure_from']);
            $dateTo = strtotime($criteria['departure_to']);
            echo "    - date check: " . ($tourDate >= $dateFrom && $tourDate <= $dateTo ? 'YES' : 'NO') . "\n";
        } else {
            echo "    - date check: NO (no date)\n";
        }
    }
}
