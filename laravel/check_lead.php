<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lead = App\Models\Lead::first();
if ($lead) {
    echo "Lead #" . $lead->id . " found:\n";
    echo json_encode($lead->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n\nSearch Criteria:\n";
    echo json_encode($lead->getSearchCriteria(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo "No Lead found";
}
?>
