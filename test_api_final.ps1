#!/usr/bin/env pwsh

Write-Host "=== ФИНАЛЬНЫЙ ТЕСТ API ===" -ForegroundColor Cyan
Write-Host ""

# Проверяем, что серверы работают
Write-Host "1️⃣  Проверка доступности API..." -ForegroundColor Green
try {
    $response = Invoke-WebRequest -Uri 'http://localhost:8000/api' -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    Write-Host "   ✓ Laravel API доступен (status: " $response.StatusCode ")`n"
} catch {
    Write-Host "   ❌ Laravel API недоступен"
    exit 1
}

# Отправляем поиск
Write-Host "2️⃣  Отправка поискового запроса..." -ForegroundColor Green
$searchBody = @{
    name = 'Иван Иванов'
    phone = '+7 (777) 123-45-67'
    email = 'ivan@example.com'
    departure_city = 'Almaty'
    destination_country = 'Turkey'
    hotel_category = 4
    departure_from = '2026-04-01'
    departure_to = '2026-06-30'
    nights_from = 5
    nights_to = 10
    adults = 2
    children = 0
} | ConvertTo-Json

try {
    $searchResponse = Invoke-WebRequest -Uri 'http://localhost:8000/api/tours/search' `
        -Method POST `
        -ContentType 'application/json' `
        -Body $searchBody `
        -UseBasicParsing `
        -TimeoutSec 30 `
        -ErrorAction Stop
    
    $searchData = $searchResponse.Content | ConvertFrom-Json
    Write-Host "   ✓ Запрос отправлен"
    Write-Host "   ✓ Lead ID: " $searchData.lead_id
    Write-Host "   ✓ Статус: " $searchData.status
    Write-Host "   ✓ Сообщение: " $searchData.message "`n"
    
    $leadId = $searchData.lead_id
} catch {
    Write-Host "   ❌ Ошибка при отправке запроса: $_"
    exit 1
}

# Ждем и получаем результаты
Write-Host "3️⃣  Получение результатов..." -ForegroundColor Green
Start-Sleep -Seconds 2

try {
    $resultsResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/tours/$leadId/results" `
        -UseBasicParsing `
        -TimeoutSec 10 `
        -ErrorAction Stop
    
    $results = $resultsResponse.Content | ConvertFrom-Json
    Write-Host "   ✓ Статус: " $results.status
    Write-Host "   ✓ Найдено туров: " $results.count "`n"
    
    if ($results.tours -and $results.tours.Count -gt 0) {
        Write-Host "4️⃣  Первые 5 туров:" -ForegroundColor Green
        $results.tours | Select-Object -First 5 | ForEach-Object {
            Write-Host "   " $_.title
            Write-Host "      ₸" $_.price " | " $_.hotel_name
            Write-Host "      Ночей: " $_.nights " | Оператор: " $_.operator " | Score: " $_.popularity_score "`n"
        }
        
        Write-Host "✅ СИСТЕМА ПОЛНОСТЬЮ РАБОТАЕТ!" -ForegroundColor Green
        Write-Host "Фронтенд получит и отобразит эти результаты 🎉`n"
    } else {
        Write-Host "   ⚠️  Туров не найдено"
    }
} catch {
    Write-Host "   ❌ Ошибка при получении результатов: $_"
    exit 1
}

Write-Host "═══════════════════════════════" -ForegroundColor Cyan
Write-Host "🚀 Готово к боевому использованию!" -ForegroundColor Green
Write-Host "═══════════════════════════════" -ForegroundColor Cyan
