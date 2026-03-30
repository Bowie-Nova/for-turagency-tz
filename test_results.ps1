param(
    [string]$leadId = "45"
)

$url = "http://localhost:8000/api/tours/$leadId/results"

$response = Invoke-WebRequest -Uri $url -Method GET 

Write-Host "Status Code: $($response.StatusCode)"
Write-Host "Content:`n"

$json = $response.Content | ConvertFrom-Json
Write-Host "Status: $($json.status)"
Write-Host "Tours count: $($json.tours.Count)"

if ($json.tours.Count -gt 0) {
    Write-Host "`nFirst 3 tours:"
    $json.tours | Select-Object -First 3 | ConvertTo-Json -Depth 5
}
