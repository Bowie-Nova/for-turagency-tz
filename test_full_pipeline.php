<?php
require 'laravel/vendor/autoload.php';
$app = require_once 'laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Full Search Pipeline Test ===\n\n";

try {
    // Create a lead
    echo "1. Creating lead...\n";
    $lead = App\Models\Lead::create([
        'name' => 'Test User',
        'phone' => '+79998887766',
        'email' => 'test@example.com',
        'departure_city' => 'Almaty',
        'destination_country' => 'Turkey',
        'hotel_category' => 4,
        'departure_from' => '2026-04-01',
        'departure_to' => '2026-04-08',
        'nights_from' => 7,
        'nights_to' => 7,
        'adults' => 2,
        'children' => 0
    ]);
    echo "✓ Lead ID: " . $lead->id . "\n\n";

    // Simulate parser output
    echo "2. Simulating parser output...\n";
    $mockTours = [
        [
            'title' => 'Турция, Анталия - 7 ночей',
            'hotel_name' => 'Titanic Beach Resort',
            'price' => 450000,
            'nights' => 7,
            'departure_date' => '2026-04-01',
            'hotel_category' => 5,
            'hotel_rating' => 4.8,
            'available_seats' => 5,
            'url' => 'https://example.com/tour/1'
        ],
        [
            'title' => 'Turkey Antalya All-Inclusive',
            'hotel_name' => 'Lara Beach Hotel',
            'price' => 380000,
            'nights' => 7,
            'departure_date' => '2026-04-01',
            'hotel_category' => 4,
            'hotel_rating' => 4.5,
            'available_seats' => 8,
            'url' => 'https://example.com/tour/2'
        ]
    ];
    echo "✓ Mock tours prepared: " . count($mockTours) . "\n\n";

    // Test aggregator
    echo "3. Running aggregator...\n";
    $aggregator = app(\App\Services\TourAggregatorService::class);
    $aggregated = $aggregator->aggregate(
        ['mock' => $mockTours],
        $lead->getSearchCriteria()
    );
    echo "✓ Aggregated tours: " . count($aggregated) . "\n";
    foreach ($aggregated as $idx => $tour) {
        echo "  [$idx] " . $tour['title'] . " - ₸" . $tour['price'] . " (operator: " . $tour['operator'] . ")\n";
    }
    echo "\n";

    // Save to database
    echo "4. Saving tours to database...\n";
    foreach ($aggregated as $tour) {
        App\Models\Tour::create([
            'lead_id' => $lead->id,
            'operator' => $tour['operator'],
            'title' => $tour['title'],
            'hotel_name' => $tour['hotel_name'],
            'hotel_category' => $tour['hotel_category'] ?? null,
            'price' => $tour['price'],
            'days' => $tour['days'],
            'departure_date' => $tour['departure_date'],
            'available_seats' => $tour['available_seats'] ?? null,
            'hotel_rating' => $tour['hotel_rating'] ?? null,
            'url' => $tour['url'] ?? null,
            'popularity_score' => $tour['popularity_score'] ?? 0
        ]);
    }
    echo "✓ Saved to database\n\n";

    // Retrieve and display
    echo "5. Retrieving results via API...\n";
    $results = $lead->tours()->orderByDesc('popularity_score')->orderBy('price')->get();
    echo "✓ Retrieved " . count($results) . " tours\n";
    foreach ($results as $tour) {
        echo "  - " . $tour->title . ": ₸" . $tour->price . "\n";
    }

    echo "\n✓ Full pipeline test PASSED!\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
