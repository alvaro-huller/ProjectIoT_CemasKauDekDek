<?php
/**
 * Smart Laundry Basket System
 * File: public/config.php
 * Konfigurasi Basis Data, Keamanan, dan Konstanta Global
 */

// Hindari pelaporan warning sensitif secara langsung ke output di produksi
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// -----------------------------------------------------------------------------
// 1. Kredensial Basis Data & Pengaturan Koneksi
// -----------------------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'smart_laundry');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------------------
// 2. Kunci Keamanan API (Secret API Key)
// Digunakan untuk otentikasi transmisi data HTTP POST dari mikrokontroler ESP32
// -----------------------------------------------------------------------------
define('SECRET_API_KEY', getenv('SECRET_API_KEY') ?: 'SMART_LAUNDRY_SECRET_KEY_2026');

// -----------------------------------------------------------------------------
// 3. Ambang Batas Logika Terpusat (Server-Side Centralized Thresholds)
// Seluruh evaluasi kondisi fisik & kualitas udara dihitung secara terpusat di server
// -----------------------------------------------------------------------------
define('DISTANCE_FULL_THRESHOLD_CM', 10.0); // Jarak bibir ke pakaian <= 10.0 cm = PENUH

// Parameter Kategori Status Bau Gas MQ-135 (ADC 12-bit: 0 - 4095)
// <= 2000      : Tidak Bau
// 2000 - 2500 : Normal
// >= 2500     : Bau (Pemicu Alarm Aktuator)
define('GAS_ODOR_TIDAK_BAU_MAX', 2000); // Batas atas untuk kategori "Tidak Bau"
define('GAS_ODOR_NORMAL_MAX', 2500);    // Batas atas untuk kategori "Normal"
define('GAS_ODOR_BAU_MIN', 2500);       // Batas bawah untuk kategori "Bau" (Trigger Alarm)
define('GAS_ODOR_THRESHOLD_ADC', 2500); // Alias kompatibilitas mundur

// -----------------------------------------------------------------------------
// 4. Fungsi Penghubung Basis Data (PDO Instance Singleton)
// -----------------------------------------------------------------------------
function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            // Berikan respons error sesuai tipe request (JSON untuk API atau teks untuk Web)
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Gagal terhubung ke basis data'
                ]);
                exit;
            }
            die("Kesalahan koneksi basis data: Layanan database tidak dapat dijangkau.");
        }
    }

    return $pdo;
}

// -----------------------------------------------------------------------------
// 5. Manajemen Sesi Pengguna
// -----------------------------------------------------------------------------
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Konfigurasi cookie sesi aman
        session_set_cookie_params([
            'lifetime' => 86400, // 24 jam
            'path'     => '/',
            'httponly' => true,  // Mencegah akses via skrip JavaScript (Anti-XSS)
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// -----------------------------------------------------------------------------
// 6. Perlindungan CSRF (Cross-Site Request Forgery)
// -----------------------------------------------------------------------------
function getCsrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    startSecureSession();
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// -----------------------------------------------------------------------------
// 7. Utilitas Respons JSON
// -----------------------------------------------------------------------------
function sendJsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
