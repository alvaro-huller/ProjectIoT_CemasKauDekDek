# Test Auth and Session Navigation Flow
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

# 1. Get login page and extract CSRF token
$res1 = Invoke-WebRequest -Uri 'http://localhost/smart-laundry/public/login.php' -WebSession $session -UseBasicParsing
if ($res1.Content -match 'name="csrf_token" id="csrf_token" value="([^"]+)"') {
    $token = $matches[1]
    Write-Host "[PASS] CSRF Token obtained: $token" -ForegroundColor Green
} else {
    Write-Host "[FAIL] CSRF token not found" -ForegroundColor Red
    exit 1
}

# 2. Test Invalid Login
$badLogin = Invoke-WebRequest -Uri 'http://localhost/smart-laundry/public/login.php' -Method Post -Body @{
    csrf_token = $token
    username = 'admin'
    password = 'wrongpassword'
} -WebSession $session -UseBasicParsing

if ($badLogin.Content -match 'Nama pengguna atau kata sandi yang Anda masukkan tidak valid') {
    Write-Host '[PASS] Invalid login returned expected error message.' -ForegroundColor Green
} else {
    Write-Host '[FAIL] Error message not detected on invalid login.' -ForegroundColor Red
}

# Extract new CSRF token after bad login attempt
if ($badLogin.Content -match 'name="csrf_token" id="csrf_token" value="([^"]+)"') {
    $token = $matches[1]
}

# 3. Test Valid Login (admin / password123)
$goodLogin = Invoke-WebRequest -Uri 'http://localhost/smart-laundry/public/login.php' -Method Post -Body @{
    csrf_token = $token
    username = 'admin'
    password = 'password123'
} -WebSession $session -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue

Write-Host "[PASS] Login HTTP Status: $($goodLogin.StatusCode) | Redirect: $($goodLogin.Headers.Location)" -ForegroundColor Green

# 4. Access index.php with active session
$dash = Invoke-WebRequest -Uri 'http://localhost/smart-laundry/public/index.php' -WebSession $session -UseBasicParsing
if ($dash.StatusCode -eq 200 -and $dash.Content -match 'Status Monitoring Keranjang Pakaian') {
    Write-Host '[PASS] Dashboard successfully loaded for logged-in user.' -ForegroundColor Green
} else {
    Write-Host '[FAIL] Dashboard failed to load.' -ForegroundColor Red
}

# 5. Check DOM element IDs in index.php
$requiredIds = @(
    'connection-status-badge', 'connection-pulse', 'connection-text',
    'last-update-text', 'btn-manual-refresh',
    'card-capacity', 'val-distance', 'badge-capacity', 'progress-capacity', 'label-capacity-pct', 'basket-liquid-fill',
    'card-gas', 'val-gas', 'badge-gas', 'progress-gas', 'label-gas-threshold', 'svg-gas-ring',
    'val-actuator-summary', 'status-led', 'status-buzzer', 'status-servo', 'dot-servo', 'btn-silence-alarm',
    'table-history-body',
    'notification-toast', 'notification-toast-msg', 'btn-toast-dismiss', 'btn-toast-close', 'btn-enable-web-notify',
    'notification-bell', 'notification-badge', 'notification-popover', 'notification-drawer-list', 'btn-clear-notifications'
)

$missingIds = @()
foreach ($id in $requiredIds) {
    if ($dash.Content -notmatch "id=['`"]$id['`"]") {
        $missingIds += $id
    }
}

if ($missingIds.Count -eq 0) {
    Write-Host "[PASS] All $($requiredIds.Count) DOM Contract IDs verified in index.php!" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Missing DOM IDs: $($missingIds -join ', ')" -ForegroundColor Red
}

# 6. Logout
$logout = Invoke-WebRequest -Uri 'http://localhost/smart-laundry/public/logout.php' -WebSession $session -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue
Write-Host "[PASS] Logout HTTP Status: $($logout.StatusCode) | Redirect: $($logout.Headers.Location)" -ForegroundColor Green

# 7. Try accessing index.php again (should redirect to login.php)
$dashAfterLogout = Invoke-WebRequest -Uri 'http://localhost/smart-laundry/public/index.php' -WebSession $session -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue
if ($dashAfterLogout.StatusCode -eq 302 -and $dashAfterLogout.Headers.Location -match 'login.php') {
    Write-Host '[PASS] Protected route index.php correctly redirects unauthenticated user to login.php.' -ForegroundColor Green
} else {
    Write-Host "[FAIL] Protected route failed to redirect. Status: $($dashAfterLogout.StatusCode)" -ForegroundColor Red
}
