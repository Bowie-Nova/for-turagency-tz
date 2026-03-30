<?php
require __DIR__ . '/../vendor/autoload.php';

// bootstrap the framework so Eloquent works
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Lead;

$lead = Lead::find($argv[1]);
echo $lead?->status ?: 'not found';
