<?php
require 'laravel/vendor/autoload.php';
$app = require_once 'laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing TourController::search ===\n\n";

use Illuminate\Http\Request;
use App\Http\Controllers\TourController;

try {
    // Create a mock request
    $requestData = [
        'name' => 'Ivan Ivanov',
        'phone' => '+7 (777) 123-45-67',
        'email' => 'ivan@example.com',
        'departure_city' => 'Almaty',
        'destination_country' => 'Turkey',
        'hotel_category' => '4',
        'departure_from' => '2026-04-01',
        'departure_to' => '2026-04-08',
        'nights_from' => '7',
        'nights_to' => '7',
        'adults' => '2',
        'children' => '0',
        'use_debug_html' => true
    ];

    $request = Request::create(
        '/api/tours/search',
        'POST',
        $requestData,
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode($requestData)
    );

    echo "1. Testing search with debug HTML...\n";
    $controller = new TourController();
    $response = $controller->search($request);
    
    $responseData = json_decode($response->getContent(), true);
    echo "✓ Response status: " . $response->status() . "\n";
    echo "✓ Response data:\n";
    print_r($responseData);
    
    // Check if we got a lead_id
    if (isset($responseData['lead_id'])) {
        $leadId = $responseData['lead_id'];
        echo "\n2. Getting results for lead $leadId...\n";
        
        $lead = \App\Models\Lead::find($leadId);
        if ($lead) {
            echo "✓ Lead found: " . $lead->name . "\n";
            echo "✓ Lead status: " . $lead->status . "\n";
            
            $tours = $lead->tours()->get();
            echo "✓ Tours: " . count($tours) . "\n";
            foreach ($tours as $tour) {
                echo "  - " . $tour->title . ": ₸" . $tour->price . "\n";
            }
        }
    }

    echo "\n✓ Controller test PASSED!\n";

} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
