<?php
/**
 * Smart Laundry Basket System
 * File: public/logout.php
 * Pembersihan Sesi Autentikasi Pengguna
 */

require_once __DIR__ . '/config.php';

startSecureSession();

// Hapus seluruh variabel sesi
$_SESSION = [];

// Hapus cookie sesi dari browser jika tersedia
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan sesi di server
session_destroy();

// Alihkan pengguna ke formulir masuk
header('Location: login.php');
exit;
