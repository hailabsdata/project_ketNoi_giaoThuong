# Test Login API
# Chạy: .\test-login.ps1

Write-Host "Testing Login API..." -ForegroundColor Cyan

try {
    $response = Invoke-WebRequest `
        -Uri "http://127.0.0.1:8000/api/auth/login" `
        -Method POST `
        -Headers @{
            "Content-Type" = "application/json"
            "Accept" = "application/json"
        } `
        -Body '{"email":"admin@tradehub.com","password":"admin123"}' `
        -ErrorAction Stop

    Write-Host "`n✅ SUCCESS - Status Code: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "`nResponse:" -ForegroundColor Yellow
    $response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 5
    
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "`n❌ FAILED - Status Code: $statusCode" -ForegroundColor Red
    
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "`nError Response:" -ForegroundColor Yellow
        Write-Host $responseBody
    } else {
        Write-Host "`nError: $($_.Exception.Message)" -ForegroundColor Red
    }
}
