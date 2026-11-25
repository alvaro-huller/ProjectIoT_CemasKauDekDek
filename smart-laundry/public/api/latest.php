<?php
/**
 * Smart Laundry Basket System
 * File: public/api/latest.php
 * Endpoint JSON Polling untuk Antarmuka Dashboard Real-time
 */

require_once __DIR__ . '/../config.php';

// Atur header response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Mendukung metode GET dan POST (untuk fleksibilitas pengujian / polling)
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Hanya metode GET atau POST yang diizinkan.'
    ], 405);
}

try {
    $pdo = getDbConnection();

    // 1. Ambil 5 riwayat log sensor paling mutakhir
    $stmtHistory = $pdo->prepare("
        SELECT 
            id, 
            jarak_cm, 
            gas_adc, 
            status_penuh, 
            status_bau, 
            status_tutup, 
            dibuat_pada 
        FROM sensor_log 
        ORDER BY id DESC 
        LIMIT 5
    ");
    $stmtHistory->execute();
    $rawHistory = $stmtHistory->fetchAll();

    // Transformasi tipe data agar konsisten di JSON
    $history = [];
    foreach ($rawHistory as $row) {
        $gasAdc = (int)$row['gas_adc'];
        $rawStatusBau = (string)$row['status_bau'];

        // Normalisasi status bau jika ada nilai legasi (0/1) atau string
        if ($rawStatusBau === '0') {
            $statusBau = ($gasAdc <= GAS_ODOR_TIDAK_BAU_MAX) ? 'Tidak Bau' : 'Normal';
        } elseif ($rawStatusBau === '1') {
            $statusBau = ($gasAdc >= GAS_ODOR_BAU_MIN) ? 'Bau' : 'Normal';
        } elseif (in_array($rawStatusBau, ['Tidak Bau', 'Normal', 'Bau'], true)) {
            $statusBau = $rawStatusBau;
        } else {
            if ($gasAdc <= GAS_ODOR_TIDAK_BAU_MAX) {
                $statusBau = 'Tidak Bau';
            } elseif ($gasAdc < GAS_ODOR_BAU_MIN) {
                $statusBau = 'Normal';
            } else {
                $statusBau = 'Bau';
            }
        }

        $statusCode = ($statusBau === 'Tidak Bau') ? 0 : (($statusBau === 'Normal') ? 1 : 2);

        $history[] = [
            'id'              => (int)$row['id'],
            'jarak_cm'        => round((float)$row['jarak_cm'], 2),
            'gas_adc'         => $gasAdc,
            'status_penuh'    => (int)$row['status_penuh'],
            'status_bau'      => $statusBau,
            'status_bau_code' => $statusCode,
            'status_tutup'    => isset($row['status_tutup']) ? (int)$row['status_tutup'] : 0,
            'dibuat_pada'     => $row['dibuat_pada']
        ];
    }

    // Ambil data terbaru (elemen pertama dari history jika ada)
    $latest = !empty($history) ? $history[0] : null;

    sendJsonResponse([
        'status'    => 'success',
        'server_time' => date('Y-m-d H:i:s'),
        'has_data'  => ($latest !== null),
        'latest'    => $latest,
        'history'   => $history
    ], 200);

} catch (PDOException $e) {
    error_log("Database Error in api/latest.php: " . $e->getMessage());
    sendJsonResponse([
        'status'  => 'error',
        'message' => 'Gagal membaca riwayat telemetri dari basis data.'
    ], 500);
}
