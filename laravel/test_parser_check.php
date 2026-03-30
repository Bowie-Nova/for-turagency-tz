<?php
require __DIR__.'/../../laravel/bootstrap/app.php';
require __DIR__.'/../../laravel/vendor/autoload.php';

$app = require_once __DIR__.'/../../laravel/bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
);

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

echo "Testing PuppeteerParserService::parseAllOperators()\n===========================\n\n";

$result = $parser->parseAllOperators($filters);

echo "Result keys: ";
print_r(array_keys($result));

echo "\nResult structure:\n";
foreach ($result as $key => $value) {
    if (isset($value['error'])) {
        echo "  [$key] ERROR: " . $value['error'] . "\n";
    } elseif (is_array($value)) {
        echo "  [$key] array with " . count($value) . " items\n";
        if (count($value) > 0) {
            $first = $value[0];
            if (is_array($first)) {
                echo "       First item type: array with keys: " . implode(", ", array_keys($first)) . "\n";
            } else {
                echo "       First item type: " . gettype($first) . " = " . var_export($first, true) . "\n";
            }
        }
    } else {
        echo "  [$key] " . gettype($value) . "\n";
    }
}
