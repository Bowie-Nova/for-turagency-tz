<?php
require 'laravel/vendor/autoload.php';
$app = require_once 'laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing parseHtmlFile ===\n\n";

$filePath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'puppeteer' . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'abk_table_res.html';
echo "1. File path: $filePath\n";
echo "2. File exists: " . (file_exists($filePath) ? 'YES' : 'NO') . "\n";
echo "3. File size: " . (file_exists($filePath) ? filesize($filePath) : 0) . " bytes\n\n";

$html = file_get_contents($filePath);
echo "4. HTML loaded: " . strlen($html) . " characters\n";

// Check tr count
$trCount = substr_count($html, '<tr');
echo "5. TR elements found: $trCount\n\n";

// Test DOM parsing
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$loaded = @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
echo "6. DOM loaded: " . ($loaded ? 'YES' : 'NO') . "\n";

if ($loaded) {
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table[contains(@class,"res")]/tbody/tr');
    echo "7. XPath query found: " . $rows->length . " rows\n\n";
    
    if ($rows->length > 0) {
        echo "Sample row data (first 3):\n";
        for ($i = 0; $i < min(3, $rows->length); $i++) {
            $row = $rows->item($i);
            
            // Check attributes
            $nights = $row->getAttribute('data-nights');
            $checkin = $row->getAttribute('data-checkin');
            
            echo "\nRow $i:\n";
            echo "  data-nights: '$nights'\n";
            echo "  data-checkin: '$checkin'\n";
            
            // Try to find hotel name
            $hotelNodes = $xpath->query('.//td[contains(@class,"link-hotel")]', $row);
            if ($hotelNodes->length > 0) {
                echo "  hotel: " . trim($hotelNodes->item(0)->textContent) . "\n";
            }
            
            // Try to find price
            $priceNodes = $xpath->query('.//span[contains(@class,"price")]', $row);
            if ($priceNodes->length > 0) {
                echo "  price text: " . trim($priceNodes->item(0)->textContent) . "\n";
            }
        }
    }
} else {
    echo "DOM load failed\n";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        echo "  " . trim($error->message) . "\n";
    }
}

// Now test the actual service
echo "\n\n=== Testing PuppeteerParserService::parseHtmlFile ===\n";
$service = app(\App\Services\PuppeteerParserService::class);
$result = $service->parseHtmlFile($filePath);

if (isset($result['error'])) {
    echo "✗ Error: " . $result['error'] . "\n";
} else {
    echo "✓ Returned " . count($result) . " tours\n";
    foreach (array_slice($result, 0, 3) as $idx => $tour) {
        echo "  [$idx] " . json_encode($tour) . "\n";
    }
}
