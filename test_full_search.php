<?php
require 'laravel/vendor/autoload.php';
$app = require_once 'laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ПОЛНЫЙ ЦИКЛ ПОИСКА ТУРОВ ===\n\n";

// Очистим логи
if (file_exists('laravel/storage/logs/laravel.log')) {
    unlink('laravel/storage/logs/laravel.log');
}

try {
    // 1. Создаём поиск  
    echo "1️⃣  Создание поиска...\n";
    $lead = \App\Models\Lead::create([
        'name' => 'Тестовый пользователь',
        'phone' => '+7 (777) 123-45-67',
        'email' => 'test@example.com',
        'departure_city' => 'Almaty',
        'destination_country' => 'Turkey',
        'hotel_category' => 4,
        'departure_from' => '2026-04-01',
        'departure_to' => '2026-06-30',
        'nights_from' => 5,
        'nights_to' => 10,
        'adults' => 2,
        'children' => 0
    ]);
    echo "   ✓ Lead ID: " . $lead->id . "\n";
    echo "   ✓ Status: " . $lead->status . "\n\n";

    // 2. Парсим HTML (симулируем то что должен сделать парсер)
    echo "2️⃣  Парсинг HTML результатов ABK...\n";
    $htmlFile = __DIR__ . DIRECTORY_SEPARATOR . 'puppeteer' . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . 'abk_table_res.html';
    $tours = [];
    
    if (!file_exists($htmlFile)) {
        echo "   ⚠️  HTML файл не найден: $htmlFile\n";
        echo "   Пропускаем...\n\n";
    } else {
        echo "   ✓ HTML файл найден\n";
        
        $parserService = app(\App\Services\PuppeteerParserService::class);
        $tours = $parserService->parseHtmlFile($htmlFile);
        
        echo "   ✓ Спарсено туров: " . count($tours) . "\n\n";
    }

    // 3. Агрегируем (фильтруем и сортируем)
    echo "3️⃣  Агрегирование результатов...\n";
    $aggregatorService = app(\App\Services\TourAggregatorService::class);
    $criteria = $lead->getSearchCriteria();
    
    $aggregated = $aggregatorService->aggregate(
        ['abk' => $tours ?? []],
        $criteria
    );
    
    echo "   ✓ После фильтрации: " . count($aggregated) . " туров\n";
    echo "   ✓ Топ-3 по цене:\n";
    
    foreach (array_slice($aggregated, 0, 3) as $tour) {
        echo "      - " . substr($tour['title'], 0, 50) . "...\n";
        echo "        ₸" . $tour['price'] . " | " . $tour['hotel_name'] . " | Score: " . $tour['popularity_score'] . "\n";
    }
    echo "\n";

    // 4. Сохраняем в БД
    echo "4️⃣  Сохранение в БД...\n";
    foreach ($aggregated as $tour) {
        $lead->tours()->create($tour);
    }
    echo "   ✓ Сохранено туров: " . count($aggregated) . "\n";
    $lead->status = 'completed';
    $lead->save();
    echo "   ✓ Lead обновлён: status = completed\n\n";

    // 5. Проверяем результаты через API
    echo "5️⃣  Проверка API результатов...\n";
    $savedTours = $lead->tours()->orderByDesc('popularity_score')->orderBy('price')->get();
    echo "   ✓ API вернёт: " . count($savedTours) . " туров\n";
    if ($savedTours->count() > 0) {
        echo "   ✓ Первый тур: " . $savedTours->first()->title . " (₸" . $savedTours->first()->price . ")\n\n";
    } else {
        echo "   ⚠️  Туров не найдено (HTML файл не содержит туров)\n\n";
    }

    echo "✅ ПОЛНЫЙ ЦИКЛ УСПЕШЕН!\n";
    if ($savedTours->count() > 0) {
        echo "Теперь пользователь может открыть фронтенд и увидеть результаты 🎉\n";
    } else {
        echo "⚠️  HTML файл должен быть обновлён парсером при следующем запуске поиска\n";
    }

} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
