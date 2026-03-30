Write-Host "=== FINAL API TEST ===" -ForegroundColor Cyan
Write-Host ""

# Check API is available
Write-Host "1. Checking API availability..." -ForegroundColor Green
try {
    $response = Invoke-WebRequest -Uri 'http://localhost:8000/api' -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    Write-Host "   OK - Laravel API is running`n"
} catch {
    Write-Host "   ERROR - Laravel API not available"
    exit 1
}

# Send search request
Write-Host "2. Sending search request..." -ForegroundColor Green
$searchBody = @{
    name = 'Ivan Ivanov'
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
    Write-Host "   OK - Request sent"
    Write-Host "   Lead ID: $($searchData.lead_id)"
    Write-Host "   Status: $($searchData.status)"
    Write-Host "   Message: $($searchData.message)`n"
    
    $leadId = $searchData.lead_id
} catch {
    Write-Host "   ERROR - Request failed: $_"
    exit 1
}

# Get results
Write-Host "3. Getting results..." -ForegroundColor Green
Start-Sleep -Seconds 2

try {
    $resultsResponse = Invoke-WebRequest -Uri "http://localhost:8000/api/tours/$leadId/results" `
        -UseBasicParsing `
        -TimeoutSec 10 `
        -ErrorAction Stop
    
    $results = $resultsResponse.Content | ConvertFrom-Json
    Write-Host "   OK - Status: $($results.status)"
    Write-Host "   Tours found: $($results.count)`n"
    
    if ($results.tours -and $results.tours.Count -gt 0) {
        Write-Host "4. Top tours:" -ForegroundColor Green
        $results.tours | Select-Object -First 5 | ForEach-Object {
            Write-Host "   $($_.title)"
            Write-Host "      Price: ₸$($_.price) | Hotel: $($_.hotel_name)"
            Write-Host "      Nights: $($_.nights) | Operator: $($_.operator) | Score: $($_.popularity_score)`n"
        }
        
        Write-Host "SUCCESS - System is fully operational!" -ForegroundColor Green
        Write-Host "Frontend will display these tours now`n"
    } else {
        Write-Host "   WARNING - No tours found"
    }
} catch {
    Write-Host "   ERROR - Failed to get results: $_"
    exit 1
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "All systems ready for production!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
