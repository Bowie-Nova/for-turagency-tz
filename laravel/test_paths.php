<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'base_path: ' . base_path() . PHP_EOL;
echo 'parent: ' . dirname(base_path()) . PHP_EOL;
echo 'puppeteer: ' . dirname(base_path()) . DIRECTORY_SEPARATOR . 'puppeteer' . DIRECTORY_SEPARATOR . 'index.js' . PHP_EOL;

$path1 = dirname(base_path()) . DIRECTORY_SEPARATOR . 'puppeteer' . DIRECTORY_SEPARATOR . 'index.js';
echo 'Path 1 exists: ' . (file_exists($path1) ? 'YES' : 'NO') . PHP_EOL;

$path2 = base_path('../puppeteer/index.js');
echo 'Path 2: ' . $path2 . PHP_EOL;
echo 'Path 2 exists: ' . (file_exists($path2) ? 'YES' : 'NO') . PHP_EOL;
?>
