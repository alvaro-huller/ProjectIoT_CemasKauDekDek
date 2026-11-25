# ==============================================================================
# Smart Laundry Basket System - API Automated Smoke Test (PowerShell)
# File: tests/test_api.ps1
# Arsitektur: Thin Client – Server-Side Centralized Logic
# ==============================================================================

param (
    [string]$BaseUrl = "http://localhost/smart-laundry/public"
)

$ErrorActionPreference = "Continue"

Write-Host "`n=======================================================" -ForegroundColor Cyan
Write-Host " SMART LAUNDRY BASKET - AUTOMATED API SMOKE TESTING" -ForegroundColor Cyan
Write-Host " Target URL: $BaseUrl" -ForegroundColor Cyan
Write-Host " Architecture: Thin Client - Server-Side Centralized Logic" -ForegroundColor Cyan
Write-Host " Status Bau: <=2000 (Tidak Bau), 2000-2500 (Normal), >=2500 (Bau)" -ForegroundColor Cyan
Write-Host "=======================================================`n" -ForegroundColor Cyan

$ApiKeyValid = "SMART_LAUNDRY_SECRET_KEY_2026"
$ApiKeyInvalid = "WRONG_SECRET_KEY_9999"

$TotalTests = 0
$PassedTests = 0
$FailedTests = 0

function Report-Result {
    param (
        [string]$TestName,
        [bool]$Success,
        [string]$Detail
    )
    $script:TotalTests++
    if ($Success) {
        $script:PassedTests++
        Write-Host " [PASS] $TestName" -ForegroundColor Green
        if ($Detail) { Write-Host "        $Detail" -ForegroundColor DarkGray }
    } else {
        $script:FailedTests++
        Write-Host " [FAIL] $TestName" -ForegroundColor Red
        if ($Detail) { Write-Host "        Error: $Detail" -ForegroundColor Yellow }
    }
}

# ------------------------------------------------------------------------------
# Test Case 1: Kategori Bau (gas_adc >= 2500) & Penuh -> Expect status_bau = "Bau" & alarm = True
# ------------------------------------------------------------------------------
try {
    $Test1Payload = @{
        api_key  = $ApiKeyValid
        jarak_cm = 8.75
        gas_adc  = 2700
    } | ConvertTo-Json

    $Uri1 = "$BaseUrl/api/data.php"
    $response1 = Invoke-WebRequest -Uri $Uri1 -Method Post -Body $Test1Payload -ContentType "application/json" -TimeoutSec 10 -UseBasicParsing

    $body1 = $response1.Content | ConvertFrom-Json
    $alarmActive = ($body1.actuator.alarm_active -eq $true)
    $isPenuh = ($body1.status_penuh -eq 1)
    $isBau = ($body1.status_bau -eq "Bau")

    if ($response1.StatusCode -eq 200 -and $body1.status -eq "success" -and $alarmActive -and $isPenuh -and $isBau) {
        Report-Result -TestName "Test 1: Status Bau (>= 2500 ADC) & Penuh" -Success $true -Detail "HTTP 200 | Penuh: 1, Bau: '$($body1.status_bau)', Alarm: True | ID: $($body1.data_id)"
    } else {
        Report-Result -TestName "Test 1: Status Bau (>= 2500 ADC) & Penuh" -Success $false -Detail "Respons: $($response1.Content)"
    }
} catch {
    $ex = $_.Exception
    Report-Result -TestName "Test 1: Status Bau (>= 2500 ADC) & Penuh" -Success $false -Detail $ex.Message
}

# ------------------------------------------------------------------------------
# Test Case 2: Kategori Normal (2000 - 2500 ADC) -> Expect status_bau = "Normal" & alarm = False
# ------------------------------------------------------------------------------
try {
    $Test2Payload = @{
        api_key  = $ApiKeyValid
        jarak_cm = 28.5
        gas_adc  = 2250
    } | ConvertTo-Json

    $Uri2 = "$BaseUrl/api/data.php"
    $response2 = Invoke-WebRequest -Uri $Uri2 -Method Post -Body $Test2Payload -ContentType "application/json" -TimeoutSec 10 -UseBasicParsing

    $body2 = $response2.Content | ConvertFrom-Json
    $alarmStandby = ($body2.actuator.alarm_active -eq $false)
    $notPenuh = ($body2.status_penuh -eq 0)
    $isNormalOdor = ($body2.status_bau -eq "Normal")

    if ($response2.StatusCode -eq 200 -and $body2.status -eq "success" -and $alarmStandby -and $notPenuh -and $isNormalOdor) {
        Report-Result -TestName "Test 2: Status Normal (2000 - 2500 ADC)" -Success $true -Detail "HTTP 200 | Penuh: 0, Bau: '$($body2.status_bau)', Alarm: False | ID: $($body2.data_id)"
    } else {
        Report-Result -TestName "Test 2: Status Normal (2000 - 2500 ADC)" -Success $false -Detail "Respons: $($response2.Content)"
    }
} catch {
    $ex = $_.Exception
    Report-Result -TestName "Test 2: Status Normal (2000 - 2500 ADC)" -Success $false -Detail $ex.Message
}

# ------------------------------------------------------------------------------
# Test Case 3: Kategori Tidak Bau (<= 2000 ADC) -> Expect status_bau = "Tidak Bau" & alarm = False
# ------------------------------------------------------------------------------
try {
    $Test3Payload = @{
        api_key  = $ApiKeyValid
        jarak_cm = 32.0
        gas_adc  = 1450
    } | ConvertTo-Json

    $Uri3 = "$BaseUrl/api/data.php"
    $response3 = Invoke-WebRequest -Uri $Uri3 -Method Post -Body $Test3Payload -ContentType "application/json" -TimeoutSec 10 -UseBasicParsing

    $body3 = $response3.Content | ConvertFrom-Json
    $alarmStandby = ($body3.actuator.alarm_active -eq $false)
    $notPenuh = ($body3.status_penuh -eq 0)
    $isTidakBau = ($body3.status_bau -eq "Tidak Bau")

    if ($response3.StatusCode -eq 200 -and $body3.status -eq "success" -and $alarmStandby -and $notPenuh -and $isTidakBau) {
        Report-Result -TestName "Test 3: Status Tidak Bau (<= 2000 ADC)" -Success $true -Detail "HTTP 200 | Penuh: 0, Bau: '$($body3.status_bau)', Alarm: False | ID: $($body3.data_id)"
    } else {
        Report-Result -TestName "Test 3: Status Tidak Bau (<= 2000 ADC)" -Success $false -Detail "Respons: $($response3.Content)"
    }
} catch {
    $ex = $_.Exception
    Report-Result -TestName "Test 3: Status Tidak Bau (<= 2000 ADC)" -Success $false -Detail $ex.Message
}

# ------------------------------------------------------------------------------
# Test Case 4: Autentikasi Gagal / API Key Salah -> Expect 401 Unauthorized
# ------------------------------------------------------------------------------
try {
    $Test4Payload = @{
        api_key  = $ApiKeyInvalid
        jarak_cm = 25.0
        gas_adc  = 600
    } | ConvertTo-Json

    $Uri4 = "$BaseUrl/api/data.php"
    $response4 = Invoke-WebRequest -Uri $Uri4 -Method Post -Body $Test4Payload -ContentType "application/json" -TimeoutSec 10 -UseBasicParsing

    if ($response4.StatusCode -eq 401) {
        Report-Result -TestName "Test 4: Invalid API Key Rejection" -Success $true -Detail "HTTP 401 Unauthorized correctly enforced"
    } else {
        Report-Result -TestName "Test 4: Invalid API Key Rejection" -Success $false -Detail "Expected HTTP 401, got $($response4.StatusCode)"
    }
} catch {
    $ex = $_.Exception
    if ($ex.Response -and [int]$ex.Response.StatusCode -eq 401) {
        Report-Result -TestName "Test 4: Invalid API Key Rejection" -Success $true -Detail "HTTP 401 Unauthorized correctly enforced"
    } else {
        Report-Result -TestName "Test 4: Invalid API Key Rejection" -Success $false -Detail ($ex.Message)
    }
}

# ------------------------------------------------------------------------------
# Test Case 5: Payload Cacat / Hilang Atribut Wajib -> Expect 400 Bad Request
# ------------------------------------------------------------------------------
try {
    $Test5Payload = @{
        api_key  = $ApiKeyValid
        gas_adc  = "BUKAN_ANGKA"
    } | ConvertTo-Json

    $Uri5 = "$BaseUrl/api/data.php"
    $response5 = Invoke-WebRequest -Uri $Uri5 -Method Post -Body $Test5Payload -ContentType "application/json" -TimeoutSec 10 -UseBasicParsing

    if ($response5.StatusCode -eq 400) {
        Report-Result -TestName "Test 5: Malformed Payload Rejection" -Success $true -Detail "HTTP 400 Bad Request correctly triggered"
    } else {
        Report-Result -TestName "Test 5: Malformed Payload Rejection" -Success $false -Detail "Expected HTTP 400, got $($response5.StatusCode)"
    }
} catch {
    $ex = $_.Exception
    if ($ex.Response -and [int]$ex.Response.StatusCode -eq 400) {
        Report-Result -TestName "Test 5: Malformed Payload Rejection" -Success $true -Detail "HTTP 400 Bad Request correctly triggered"
    } else {
        Report-Result -TestName "Test 5: Malformed Payload Rejection" -Success $false -Detail ($ex.Message)
    }
}

# ------------------------------------------------------------------------------
# Test Case 6: Verifikasi Pembacaan Terakhir (GET /api/latest.php) -> Expect 200 OK
# ------------------------------------------------------------------------------
try {
    $Uri6 = "$BaseUrl/api/latest.php"
    $response6 = Invoke-WebRequest -Uri $Uri6 -Method Get -TimeoutSec 10 -UseBasicParsing

    $body6 = $response6.Content | ConvertFrom-Json
    $hasLatest = ($body6.latest -ne $null)
    $hasOdorLabel = ($body6.latest.status_bau -ne $null)
    $hasLidStatus = ($body6.latest.PSObject.Properties['status_tutup'] -ne $null)
    $historyCount = $body6.history.Count

    if ($response6.StatusCode -eq 200 -and $body6.status -eq "success" -and $hasLatest -and $hasOdorLabel -and $hasLidStatus) {
        Report-Result -TestName "Test 6: Fetch Latest Data (GET /api/latest.php)" -Success $true -Detail "HTTP 200 OK | Latest ID: $($body6.latest.id) | Status Tutup: $($body6.latest.status_tutup) | History count: $historyCount"
    } else {
        Report-Result -TestName "Test 6: Fetch Latest Data (GET /api/latest.php)" -Success $false -Detail "Data mismatch in latest.php response"
    }
} catch {
    $ex = $_.Exception
    Report-Result -TestName "Test 6: Fetch Latest Data (GET /api/latest.php)" -Success $false -Detail $ex.Message
}

# ------------------------------------------------------------------------------
# Test Case 7: Smart Lid Automation Ingestion (lid_open = 1) -> Expect status_tutup = 1
# ------------------------------------------------------------------------------
try {
    $Test7Payload = @{
        api_key  = $ApiKeyValid
        jarak_cm = 18.2
        gas_adc  = 1600
        lid_open = 1
    } | ConvertTo-Json

    $Uri7 = "$BaseUrl/api/data.php"
    $response7 = Invoke-WebRequest -Uri $Uri7 -Method Post -Body $Test7Payload -ContentType "application/json" -TimeoutSec 10 -UseBasicParsing

    $body7 = $response7.Content | ConvertFrom-Json
    $isLidOpenSuccess = ($body7.status_tutup -eq 1 -and $body7.actuator.lid_open -eq $true)

    if ($response7.StatusCode -eq 200 -and $body7.status -eq "success" -and $isLidOpenSuccess) {
        Report-Result -TestName "Test 7: Smart Lid Automation Telemetry (lid_open = 1)" -Success $true -Detail "HTTP 200 | status_tutup: $($body7.status_tutup) | actuator.lid_open: True | ID: $($body7.data_id)"
    } else {
        Report-Result -TestName "Test 7: Smart Lid Automation Telemetry (lid_open = 1)" -Success $false -Detail "Respons: $($response7.Content)"
    }
} catch {
    $ex = $_.Exception
    Report-Result -TestName "Test 7: Smart Lid Automation Telemetry (lid_open = 1)" -Success $false -Detail $ex.Message
}

# ------------------------------------------------------------------------------
# Ringkasan Pengujian
# ------------------------------------------------------------------------------
Write-Host "`n-------------------------------------------------------" -ForegroundColor Cyan
Write-Host " RINGKASAN HASIL PENGUJIAN SMOKE TEST:" -ForegroundColor Cyan
Write-Host " Total Pengujian : $TotalTests" -ForegroundColor White
Write-Host " Lulus (Passed)  : $PassedTests" -ForegroundColor Green
Write-Host " Gagal (Failed)  : $FailedTests" -ForegroundColor $(if ($FailedTests -gt 0) { "Red" } else { "Green" })
Write-Host "-------------------------------------------------------`n" -ForegroundColor Cyan

if ($FailedTests -gt 0) {
    exit 1
} else {
    exit 0
}
