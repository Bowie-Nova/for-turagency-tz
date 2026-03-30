<?php
echo "=== Date Range Check ===\n";

$tourDate = strtotime("2026-05-01");
$dateFrom = strtotime("2026-04-01");
$dateTo = strtotime("2026-04-08");

echo "Tour date:    " . date("Y-m-d", $tourDate) . " (".  $tourDate . ")\n";
echo "Date from:    " . date("Y-m-d", $dateFrom) . " (" . $dateFrom . ")\n";
echo "Date to:      " . date("Y-m-d", $dateTo) . " (" . $dateTo . ")\n";

echo "\nComparisons:\n";
echo "  tourDate >= dateFrom? " . ($tourDate >= $dateFrom ? "YES" : "NO") . "\n";
echo "  tourDate <= dateTo? " . ($tourDate <= $dateTo ? "YES" : "NO") . "\n";
echo "  In range? " . (($tourDate >= $dateFrom && $tourDate <= $dateTo) ? "YES" : "NO") . "\n";

echo "\n=== The Problem ===\n";
echo "Tours have departure_date of 2026-05-01 (May)\n";
echo "But search criteria is 2026-04-01 to 2026-04-08 (April)\n";
echo "So ALL tours are rejected by the filter!\n";

echo "\n=== Solution ===\n";
echo "The test should use a date range that includes the tour dates.\n";
echo "Tours found in HTML are from May, so criteria should be 2026-05-01 or later.\n";
