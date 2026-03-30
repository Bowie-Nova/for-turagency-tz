<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class PuppeteerParserService
{
    protected string $puppeteerPath;

    public function __construct()
    {
        $this->puppeteerPath = base_path('puppeteer');
    }

    public function parseAllOperators(array $filters): array
    {
        try {
            Log::info('Запуск Puppeteer парсера с фильтрами', $filters);

            // Correct path to puppeteer index.js - one level up from laravel folder
            $puppeteerIndex = dirname(base_path()) . DIRECTORY_SEPARATOR . 'puppeteer' . DIRECTORY_SEPARATOR . 'index.js';

            $result = Process::input(json_encode($filters))
                ->timeout(120)  // Increased to 120 seconds to accommodate slow load times
                ->run('node ' . $puppeteerIndex);

            // Log both output and error output for debugging
            if ($result->output()) {
                Log::info('Puppeteer stdout', ['output' => substr($result->output(), 0, 2000)]);
            }
            
            if ($result->errorOutput()) {
                Log::info('Puppeteer stderr', ['output' => substr($result->errorOutput(), 0, 5000)]);
            }

            if (!$result->successful()) {
                Log::error('Puppeteer error: ' . $result->errorOutput());
                return ['error' => 'Ошибка при запуске парсера: ' . $result->errorOutput()];
            }

            $output = json_decode($result->output(), true);
            if (!is_array($output)) {
                Log::error('Puppeteer output is not a valid JSON array');
                return ['error' => 'Некорректный ответ от парсера'];
            }

            // Extract operators and their tours, excluding metadata fields
            $operatorResults = [];
            $excludeKeys = ['success', 'debug_tables_by_operator', 'timestamp'];
            
            foreach ($output as $key => $value) {
                if (in_array($key, $excludeKeys)) {
                    continue;
                }
                // Each key should be an operator name with an array of tours
                if (is_array($value)) {
                    $operatorResults[$key] = $value;
                }
            }

            Log::info('Puppeteer парсер завершен', ['operators' => count($operatorResults), 'output_keys' => array_keys($output)]);

            return $operatorResults;
        } catch (\Exception $e) {
            Log::error('Puppeteer exception: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Parse local HTML file (often debug output) and return tour data array.
     *
     * @param string $filePath absolute path to HTML file
     * @return array list of tours or ['error'=>string]
     */
    public function parseHtmlFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['error' => 'Файл не найден: ' . $filePath];
        }

        $html = file_get_contents($filePath);
        if ($html === false) {
            return ['error' => 'Не удалось прочитать файл'];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        // prepend utf-8 declaration to avoid charset issues
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        $xpath = new \DOMXPath($dom);

        // File contains just tbody, so look for tr directly
        $rows = $xpath->query('//tbody/tr');
        if ($rows->length === 0) {
            // Fallback: look for tr in any table
            $rows = $xpath->query('//table//tr');
        }
        if ($rows->length === 0) {
            // Last fallback: look for tr anywhere
            $rows = $xpath->query('//tr');
        }

        $results = [];

        foreach ($rows as $row) {
            // skip header rows if any
            if ($xpath->query('.//th', $row)->length > 0) {
                continue;
            }

            // title
            $titleNode = $xpath->query('.//td[contains(@class,"tour")]', $row);
            $title = $titleNode->length ? trim($titleNode->item(0)->textContent) : '';

            // hotel
            $hotelNode = $xpath->query('.//td[contains(@class,"link-hotel")]', $row);
            $hotel = $hotelNode->length ? trim($hotelNode->item(0)->textContent) : '';

            // price - look for span with class containing "price"
            $price = 0;
            $priceNodes = $xpath->query('.//span[contains(@class,"price")]', $row);
            if ($priceNodes->length > 0) {
                // Extract only digits (price may contain currency like "879 EUR")
                $priceText = $priceNodes->item(0)->textContent;
                preg_match('/\d+/', $priceText, $matches);
                $price = isset($matches[0]) ? intval($matches[0]) : 0;
            }

            // nights
            $nights = intval($row->getAttribute('data-nights') ?: 0);

            // departure date from data-checkin attribute (format: YYYYMMDD)
            $departure = '';
            $checkin = $row->getAttribute('data-checkin');
            if (strlen($checkin) === 8) {
                $departure = substr($checkin,0,4) . '-' . substr($checkin,4,2) . '-' . substr($checkin,6,2);
            }

            // Only add if we have title and at least a partial price
            if (!empty($title) && $price > 0) {
                $results[] = [
                    'title' => $title,
                    'hotel_name' => $hotel,
                    'price' => $price,
                    'nights' => $nights,
                    'departure_date' => $departure,
                    'url' => '',
                ];
            }
        }

        return $results;
    }
}
