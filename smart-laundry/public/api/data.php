<?php
/**
 * Smart Laundry Basket System
 * File: public/api/data.php
 * Ingestion Endpoint & Server-Side Centralized Logic untuk Mikrokontroler ESP32 (Thin Client)
 */

require_once __DIR__ . '/../config.php';

// Atur header response default
header('Content-Type: application/json; charset=utf-8');

// 1. Verifikasi HTTP Method (Hanya POST yang diizinkan)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Metode HTTP tidak didukung. Hanya metode POST yang diizinkan.'
    ], 405);
}

// 2. Baca Raw JSON Body
$rawInput = file_get_contents('php://input');
if ($rawInput === false || trim($rawInput) === '') {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Payload kosong atau tidak terbaca.'
    ], 400);
}

$payload = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Format JSON tidak valid: ' . json_last_error_msg()
    ], 400);
}

// 3. Ekstraksi dan Validasi API Key
// Mendukung API Key melalui properti JSON (api_key) maupun Header (X-API-KEY / Authorization Bearer)
$providedApiKey = null;

if (!empty($payload['api_key'])) {
    $providedApiKey = trim((string)$payload['api_key']);
} elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $providedApiKey = trim($_SERVER['HTTP_X_API_KEY']);
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $providedApiKey = trim($matches[1]);
    }
}

if ($providedApiKey === null || !hash_equals(SECRET_API_KEY, $providedApiKey)) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Otentikasi gagal: Kunci API (api_key) tidak valid atau tidak disertakan.'
    ], 401);
}

// 4. Validasi Keberadaan Parameter Data Mentah
$requiredFields = ['jarak_cm', 'gas_adc'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!array_key_exists($field, $payload)) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Atribut payload tidak lengkap. Kurang: ' . implode(', ', $missingFields)
    ], 400);
}

// 5. Validasi Tipe Data dan Rentang Nilai Numerik
if (!is_numeric($payload['jarak_cm'])) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Nilai jarak_cm harus berupa angka valid.'
    ], 400);
}

if (!is_numeric($payload['gas_adc'])) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Nilai gas_adc harus berupa angka integer valid.'
    ], 400);
}

$jarakCm = (float)$payload['jarak_cm'];
$gasAdc = (int)$payload['gas_adc'];

if ($jarakCm < 0) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Nilai jarak_cm tidak boleh bernilai negatif.'
    ], 400);
}

if ($gasAdc < 0 || $gasAdc > 4095) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Nilai gas_adc di luar rentang pembacaan ADC ESP32 (0 - 4095).'
    ], 400);
}

// 6. Logika Terpusat Sisi Server (Centralized Server-Side Business Logic)
// Evaluasi ambang batas kepenuhan (jarak pantul <= 10.0 cm)
$statusPenuh = ($jarakCm <= DISTANCE_FULL_THRESHOLD_CM) ? 1 : 0;

// Evaluasi ambang batas kebauan gas (MQ-135 ADC 12-bit)
// <= 2000      : Tidak Bau (status code 0)
// 2000 - 2500 : Normal (status code 1)
// >= 2500     : Bau (status code 2, memicu alarm)
if ($gasAdc <= GAS_ODOR_TIDAK_BAU_MAX) {
    $statusBau = 'Tidak Bau';
    $statusBauCode = 0;
} elseif ($gasAdc < GAS_ODOR_BAU_MIN) {
    $statusBau = 'Normal';
    $statusBauCode = 1;
} else {
    $statusBau = 'Bau';
    $statusBauCode = 2;
}

// Keputusan aktuasi alarm (LED & Buzzer aktif jika penuh atau kondisi bau terdeteksi)
$alarmAktif = ($statusPenuh === 1 || $statusBau === 'Bau');

// Status Buka/Tutup Keranjang Pintar (Lid Automation) dari payload ESP32 (default 0 / tertutup)
$lidOpenRaw = $payload['lid_open'] ?? 0;
$statusTutup = (!empty($lidOpenRaw) && ((int)$lidOpenRaw === 1 || $lidOpenRaw === true || $lidOpenRaw === '1')) ? 1 : 0;

// 7. Simpan Data dan Hasil Analisis ke Basis Data MySQL
try {
    $pdo = getDbConnection();
    $sql = "INSERT INTO sensor_log (jarak_cm, gas_adc, status_penuh, status_bau, status_tutup, dibuat_pada)
            VALUES (:jarak_cm, :gas_adc, :status_penuh, :status_bau, :status_tutup, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':jarak_cm', $jarakCm);
    $stmt->bindValue(':gas_adc', $gasAdc, PDO::PARAM_INT);
    $stmt->bindValue(':status_penuh', $statusPenuh, PDO::PARAM_INT);
    $stmt->bindValue(':status_bau', $statusBau, PDO::PARAM_STR);
    $stmt->bindValue(':status_tutup', $statusTutup, PDO::PARAM_INT);
    $stmt->execute();

    $insertedId = (int)$pdo->lastInsertId();

    // 8. Kirim Respons Balik ke ESP32 Beserta Instruksi Kendali Aktuator
    sendJsonResponse([
        'status'          => 'success',
        'message'         => 'Data berhasil dianalisis dan dicatat',
        'data_id'         => $insertedId,
        'status_penuh'    => $statusPenuh,
        'status_bau'      => $statusBau,
        'status_bau_code' => $statusBauCode,
        'status_tutup'    => $statusTutup,
        'actuator'        => [
            'alarm_active' => $alarmAktif,
            'lid_open'     => ($statusTutup === 1)
        ]
    ], 200);

} catch (PDOException $e) {
    error_log("Database Error in api/data.php: " . $e->getMessage());
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan sistem internal saat menyimpan rekaman sensor.'
    ], 500);
}
