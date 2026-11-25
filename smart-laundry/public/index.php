<?php
/**
 * Smart Laundry Basket System
 * File: public/index.php
 * Antarmuka Web Dashboard Pemantauan Real-time IoT
 * Desain: Xiaomi Mijia Smart Domestic IoT (Google Stitch Reference)
 */

require_once __DIR__ . '/config.php';

startSecureSession();

// Proteksi Halaman: Wajib Terotentikasi
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dashboard - Smart Laundry Basket IoT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin=""/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <style>
        @layer base {
            html, body { margin: 0; padding: 0; }
            body { overscroll-behavior: none; }
            main > :first-child { margin-top: 0 !important; }
            main > :last-child { margin-bottom: 0 !important; }
        }
        ::-webkit-scrollbar { display: none; }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#006a61",
                        "on-primary": "#ffffff",
                        "primary-container": "#00c4b4",
                        "on-primary-container": "#004b44",
                        "secondary": "#005daa",
                        "on-secondary": "#ffffff",
                        "secondary-container": "#0075d5",
                        "secondary-fixed": "#d4e3ff",
                        "secondary-fixed-dim": "#a5c8ff",
                        "on-secondary-fixed": "#001c3a",
                        "on-secondary-fixed-variant": "#004785",
                        "tertiary": "#006c46",
                        "on-tertiary": "#ffffff",
                        "tertiary-container": "#2dc687",
                        "on-tertiary-container": "#004c30",
                        "tertiary-fixed": "#6ffcb8",
                        "tertiary-fixed-dim": "#4edf9e",
                        "on-tertiary-fixed": "#002112",
                        "error": "#ba1a1a",
                        "on-error": "#ffffff",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                        "background": "#f7f9ff",
                        "on-background": "#131c26",
                        "surface": "#f7f9ff",
                        "on-surface": "#131c26",
                        "surface-variant": "#d9e3f1",
                        "on-surface-variant": "#3c4a47",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#edf4ff",
                        "surface-container": "#e5effc",
                        "surface-container-high": "#dfe9f6",
                        "surface-container-highest": "#d9e3f1",
                        "outline": "#6b7a77",
                        "outline-variant": "#bacac6"
                    },
                    spacing: {
                        "space-xs": "0.25rem",
                        "space-sm": "0.5rem",
                        "space-md": "1rem",
                        "space-lg": "1.25rem",
                        "space-xl": "1.5rem",
                        "margin": "1rem",
                        "margin-tablet": "1.5rem",
                        "margin-desktop": "2.5rem"
                    },
                    fontFamily: {
                        sans: ["Plus Jakarta Sans", "system-ui", "sans-serif"]
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-background font-sans text-on-surface antialiased min-h-screen flex flex-col justify-between">

<!-- Top Fixed Navbar -->
<header class="fixed top-0 left-0 w-full z-40 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
    <div class="h-16 w-full max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop flex items-center justify-between gap-space-md">
        
        <!-- Brand section -->
        <div class="flex items-center gap-space-lg">
            <div class="flex items-center gap-space-sm">
                <div class="w-8 h-8 rounded-xl bg-primary-container flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined text-on-primary-container text-[20px]">local_laundry_service</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-base text-on-surface tracking-tight font-bold leading-none">Smart Laundry Basket</span>
                    <span class="text-[11px] text-on-surface-variant leading-tight">IoT Telemetry Dashboard</span>
                </div>
            </div>
            <div class="hidden xl:flex items-center gap-space-xs bg-surface-container-low px-space-sm py-1 rounded-full">
                <span class="w-2 h-2 rounded-full bg-tertiary-container animate-pulse"></span>
                <span class="text-xs text-on-surface-variant font-medium">Node ESP32 Aktif &bull; Centralized Server</span>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="hidden lg:flex items-center gap-space-xs">
            <a aria-current="page" class="px-space-md py-1.5 bg-surface-container-high text-on-surface font-semibold rounded-full text-xs transition-colors" href="#">Overview</a>
            <a class="px-space-md py-1.5 text-xs text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors rounded-full font-medium" href="#card-capacity">Kapasitas</a>
            <a class="px-space-md py-1.5 text-xs text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors rounded-full font-medium" href="#card-gas">Indeks Bau</a>
            <a class="px-space-md py-1.5 text-xs text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors rounded-full font-medium" href="#table-history-section">Log Riwayat</a>
        </nav>

        <!-- Right User Actions -->
        <div class="flex items-center gap-space-sm">
            <!-- 3s Polling badge -->
            <div class="hidden sm:flex items-center gap-space-xs bg-surface-container-low px-space-sm py-1 rounded-full">
                <span class="material-symbols-outlined text-primary text-[14px]">sync</span>
                <span class="text-xs text-on-surface-variant">Live Sync 3s</span>
            </div>

            <!-- Manual Refresh Button -->
            <button class="w-9 h-9 rounded-full bg-surface-container-low hover:bg-surface-container text-on-surface flex items-center justify-center transition-all cursor-pointer" id="btn-manual-refresh" title="Perbarui Data Manual" type="button">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
            </button>

            <!-- Notifications Drawer Bell with Badge -->
            <div class="relative">
                <button class="relative w-9 h-9 rounded-full bg-surface-container-low hover:bg-surface-container text-on-surface flex items-center justify-center transition-colors cursor-pointer" id="notification-bell" title="Pusat Notifikasi Cerdas" type="button">
                    <span class="material-symbols-outlined text-[18px]">notifications</span>
                    <span class="hidden absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-error text-white text-[10px] flex items-center justify-center font-bold ring-2 ring-surface-container-lowest animate-pulse" id="notification-badge">0</span>
                </button>

                <!-- Popover Drawer -->
                <div class="hidden absolute right-0 mt-3 w-80 sm:w-96 bg-surface-container-lowest rounded-3xl p-space-md shadow-2xl z-50 ring-1 ring-on-surface/5 flex flex-col gap-space-sm border border-surface-container" id="notification-popover">
                    <div class="flex items-center justify-between pb-space-xs border-b border-surface-container">
                        <div class="flex items-center gap-space-xs">
                            <span class="text-sm text-on-surface font-semibold">Riwayat Notifikasi Cerdas</span>
                            <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-primary text-[11px] font-bold" id="notification-drawer-count">0 Notifikasi</span>
                        </div>
                        <button class="text-xs text-primary hover:underline cursor-pointer" id="btn-clear-notifications" type="button">Hapus</button>
                    </div>
                    <div class="flex flex-col gap-space-xs max-h-72 overflow-y-auto pr-1" id="notification-drawer-list">
                        <div class="p-space-sm text-center text-xs text-on-surface-variant">Belum ada notifikasi baru.</div>
                    </div>
                </div>
            </div>

            <div class="h-6 w-[1px] bg-outline-variant mx-space-xs hidden sm:block"></div>

            <!-- User Profile Avatar & Name -->
            <div class="flex items-center gap-space-sm">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
                </div>
                <div class="hidden md:flex flex-col">
                    <span class="text-xs text-on-surface font-semibold leading-none user-name"><?= htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-[10px] text-on-surface-variant leading-tight">Operator</span>
                </div>
            </div>

            <!-- Logout Button -->
            <a href="logout.php" class="btn-logout w-9 h-9 rounded-full bg-surface-container-low hover:bg-error-container hover:text-on-error-container text-on-surface-variant flex items-center justify-center transition-colors ml-space-xs" title="Keluar dari sesi">
                <span class="material-symbols-outlined text-[18px]">logout</span>
            </a>
        </div>
    </div>
</header>

<!-- Main Dashboard Container -->
<main class="w-full pt-16 bg-background flex-1">
    <div class="w-full max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop py-space-xl">
        <div class="flex flex-col w-full relative">

            <!-- Top Floating Toast Notification System (Contract: id="notification-toast") -->
            <div class="hidden fixed top-20 right-4 sm:right-8 z-50 max-w-md w-full transition-all duration-500 ease-out transform translate-y-[-20px] opacity-0 pointer-events-none rounded-3xl bg-surface-container-lowest/95 backdrop-blur-xl p-space-md ring-1 ring-error/20 flex flex-col gap-space-sm shadow-[0_16px_36px_-4px_rgba(186,26,26,0.2)]" id="notification-toast">
                <div class="flex items-start justify-between gap-space-sm">
                    <div class="flex items-center gap-space-sm">
                        <div class="w-10 h-10 rounded-2xl bg-error-container flex items-center justify-center text-error shrink-0">
                            <span class="material-symbols-outlined text-[22px]">warning</span>
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-space-xs">
                                <span class="text-[10px] text-error uppercase font-bold tracking-wider">Peringatan Sensor IoT</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-error animate-ping"></span>
                            </div>
                            <span class="text-sm text-on-surface font-semibold" id="notification-toast-title">Kapasitas &amp; Sanitasi</span>
                        </div>
                    </div>
                    <button class="w-7 h-7 rounded-full bg-surface-container hover:bg-surface-container-high text-on-surface-variant flex items-center justify-center transition-colors cursor-pointer" id="btn-toast-dismiss" type="button">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
                <p class="text-xs text-on-surface-variant pl-12 pr-2" id="notification-toast-msg">
                    Keranjang pakaian penuh atau terdeteksi aroma tidak sedap.
                </p>
                <div class="flex items-center justify-end gap-space-sm pt-space-xs pl-12">
                    <button class="px-space-md py-1.5 rounded-full bg-primary text-on-primary text-xs hover:bg-on-primary-container transition-all flex items-center gap-space-xs shadow-sm cursor-pointer" id="btn-enable-web-notify" type="button">
                        <span class="material-symbols-outlined text-[15px]">notifications_active</span>
                        <span>Aktifkan Web Push</span>
                    </button>
                    <button class="px-space-md py-1.5 rounded-full bg-surface-container text-on-surface text-xs hover:bg-surface-container-high transition-colors cursor-pointer" id="btn-toast-close" type="button">
                        Tutup
                    </button>
                </div>
            </div>

            <!-- Top Header & Connectivity Status Strip -->
            <div class="w-full flex flex-col md:flex-row md:items-center justify-between gap-space-md pb-space-lg">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-space-sm">
                        <span class="text-xs uppercase tracking-wider text-primary font-bold">Node Telemetri ESP32</span>
                        <span class="text-xs text-on-surface-variant">• Thin Client Model</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-on-surface tracking-tight">Status Monitoring Keranjang Pakaian</h1>
                    <p class="text-sm text-on-surface-variant">Logika terpusat di server PHP dengan evaluasi ambang batas otomatis.</p>
                </div>

                <!-- Connectivity Status Badge & Last Update -->
                <div class="flex flex-wrap items-center gap-space-sm">
                    <!-- Live Connection Badge -->
                    <div class="flex items-center gap-space-xs bg-surface-container-lowest px-space-md py-2 rounded-full shadow-sm text-primary border border-surface-container" id="connection-status-badge">
                        <span class="w-2.5 h-2.5 rounded-full bg-tertiary-container animate-pulse" id="connection-pulse"></span>
                        <span class="text-xs font-semibold" id="connection-text">Live Sync (3s)</span>
                    </div>

                    <!-- Last Update Timestamp -->
                    <div class="flex items-center gap-space-xs bg-surface-container-lowest px-space-md py-2 rounded-full shadow-sm border border-surface-container">
                        <span class="material-symbols-outlined text-[16px] text-on-surface-variant">schedule</span>
                        <span class="text-xs text-on-surface-variant">Update: <strong id="last-update-text">Memuat...</strong></span>
                    </div>
                </div>
            </div>

            <!-- Metric Cards Grid (Contract: id="card-capacity", id="card-gas") -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg w-full">

                <!-- Card 1: Kapasitas Keranjang Pintar (HC-SR04) -->
                <div class="lg:col-span-6 bg-surface-container-lowest rounded-3xl p-space-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col justify-between relative overflow-hidden transition-all duration-300 border border-surface-container-high/40" id="card-capacity">
                    <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-primary-container/10 filter blur-3xl pointer-events-none"></div>
                    <div class="flex items-center justify-between w-full relative z-10">
                        <div class="flex items-center gap-space-sm">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-[22px]">shopping_basket</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase font-semibold">Sensor Jarak HC-SR04</span>
                                <h2 class="text-base text-on-surface font-bold">Kapasitas Keranjang Pakaian</h2>
                            </div>
                        </div>
                        <span class="px-space-md py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed text-xs font-bold tracking-tight metric-badge" id="badge-capacity">
                            KAPASITAS AMAN
                        </span>
                    </div>

                    <!-- Core Visual: Basket Simulation Tank + Readout -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-space-lg items-center py-space-xl relative z-10">
                        <!-- Tank visual -->
                        <div class="sm:col-span-5 flex flex-col items-center justify-center">
                            <div class="relative w-36 h-48 rounded-3xl bg-surface-container-low p-2 shadow-inner flex flex-col justify-end overflow-hidden border border-surface-container">
                                <!-- Top Rim Ring -->
                                <div class="absolute top-2 left-1/2 -translate-x-1/2 w-28 h-4 rounded-full bg-surface-container-high border-2 border-surface-container-lowest shadow-sm z-20"></div>
                                <!-- Perforation Dots -->
                                <div class="absolute inset-x-4 top-10 flex flex-col gap-2.5 opacity-20 z-10 pointer-events-none">
                                    <div class="flex justify-between"><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span></div>
                                    <div class="flex justify-between"><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span></div>
                                    <div class="flex justify-between"><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span></div>
                                    <div class="flex justify-between"><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span><span class="w-1.5 h-1.5 rounded-full bg-on-surface"></span></div>
                                </div>
                                <!-- Dynamic Liquid/Fill Tank -->
                                <div class="w-full rounded-2xl bg-gradient-to-t from-primary to-primary-container transition-all duration-700 ease-out flex items-center justify-center relative overflow-hidden" id="basket-liquid-fill" style="height: 0%;">
                                    <div class="absolute inset-0 bg-white/10 animate-pulse"></div>
                                    <span class="material-symbols-outlined text-white text-[28px] opacity-90">laundry</span>
                                </div>
                            </div>
                            <span class="text-[11px] text-on-surface-variant mt-space-xs font-medium">Batas Sensor: ~40.0 cm</span>
                        </div>

                        <!-- Distance Metric Readout -->
                        <div class="sm:col-span-7 flex flex-col justify-center gap-space-xs">
                            <span class="text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">Jarak Bibir ke Cucian</span>
                            <div class="flex items-baseline gap-space-xs">
                                <span class="text-4xl sm:text-5xl font-bold text-on-surface tracking-tight leading-none" id="val-distance">--</span>
                                <span class="text-xl text-on-surface-variant font-medium">cm</span>
                            </div>
                            <div class="mt-space-sm flex flex-col gap-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-primary" id="label-capacity-pct">0% Terisi</span>
                                    <span class="text-[11px] text-on-surface-variant">Ambang Penuh: &le; 10 cm</span>
                                </div>
                                <!-- Progress Bar -->
                                <div class="w-full h-3 rounded-full bg-surface-container-high overflow-hidden">
                                    <div class="h-full bg-primary-container rounded-full transition-all duration-500 ease-out" id="progress-capacity" style="width: 0%;"></div>
                                </div>
                            </div>
                            <div class="mt-space-sm p-space-sm rounded-2xl bg-surface-container-low flex items-center gap-space-xs">
                                <span class="material-symbols-outlined text-[18px] text-primary shrink-0">tips_and_updates</span>
                                <span class="text-xs text-on-surface-variant">Pisahkan bahan katun &amp; pakaian berwarna sebelum siklus pencucian.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Capacity Card Sub-Strip -->
                    <div class="grid grid-cols-3 gap-space-sm pt-space-md border-t border-surface-container">
                        <div class="flex flex-col">
                            <span class="text-[11px] text-on-surface-variant">Tinggi Kosong</span>
                            <span class="text-xs text-on-surface font-semibold">~40.0 cm</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] text-on-surface-variant">Batas Penuh</span>
                            <span class="text-xs text-error font-semibold">&le; 10.0 cm</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] text-on-surface-variant">Filter Sinyal</span>
                            <span class="text-xs text-primary font-semibold">5-Median ESP32</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Indeks Aroma & Bau Gas (MQ-135) -->
                <div class="lg:col-span-6 bg-surface-container-lowest rounded-3xl p-space-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col justify-between relative overflow-hidden transition-all duration-300 border border-surface-container-high/40" id="card-gas">
                    <div class="absolute -top-16 -left-16 w-56 h-56 rounded-full bg-secondary-container/10 filter blur-3xl pointer-events-none"></div>
                    <div class="flex items-center justify-between w-full relative z-10">
                        <div class="flex items-center gap-space-sm">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container flex items-center justify-center text-secondary">
                                <span class="material-symbols-outlined text-[22px]">air</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase font-semibold">Sensor Gas Analog MQ-135</span>
                                <h2 class="text-base text-on-surface font-bold">Indikator Bau &amp; Kualitas Udara</h2>
                            </div>
                        </div>
                        <span class="px-space-md py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed text-xs font-bold tracking-tight metric-badge" id="badge-gas">
                            TIDAK BAU
                        </span>
                    </div>

                    <!-- Core Visual: Radial Gauge Ring + Readout -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-space-lg items-center py-space-xl relative z-10">
                        <!-- SVG Radial Gauge -->
                        <div class="sm:col-span-5 flex flex-col items-center justify-center">
                            <div class="relative w-40 h-40 flex items-center justify-center">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 120 120">
                                    <circle class="text-surface-container-high" cx="60" cy="60" fill="transparent" r="50" stroke="currentColor" stroke-width="10"></circle>
                                    <circle class="text-primary transition-all duration-700 ease-out" cx="60" cy="60" fill="transparent" id="svg-gas-ring" r="50" stroke="currentColor" stroke-dasharray="314.16" stroke-dashoffset="314.16" stroke-linecap="round" stroke-width="10"></circle>
                                </svg>
                                <div class="absolute flex flex-col items-center justify-center text-center">
                                    <span class="material-symbols-outlined text-primary text-[24px]" id="icon-gas-status">cloud</span>
                                    <span class="text-[10px] text-on-surface-variant font-medium">Status</span>
                                    <span class="text-xs text-primary font-bold" id="label-gas-state">Normal</span>
                                </div>
                            </div>
                            <span class="text-[11px] text-on-surface-variant mt-space-xs font-medium">Skala 12-bit ADC (0 - 4095)</span>
                        </div>

                        <!-- Gas Readout -->
                        <div class="sm:col-span-7 flex flex-col justify-center gap-space-xs">
                            <span class="text-[11px] text-on-surface-variant uppercase tracking-wider font-semibold">Konsentrasi Gas Volatil</span>
                            <div class="flex items-baseline gap-space-xs">
                                <span class="text-4xl sm:text-5xl font-bold text-on-surface tracking-tight leading-none" id="val-gas">--</span>
                                <span class="text-xl text-on-surface-variant font-medium">ADC</span>
                            </div>
                            <div class="mt-space-sm flex flex-col gap-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-on-surface font-semibold">Parameter Ambang Bau</span>
                                    <span class="text-[11px] text-on-surface-variant font-medium" id="label-gas-threshold">&le;2000 (Tidak Bau) | &ge;2500 (Bau)</span>
                                </div>
                                <!-- Progress Bar -->
                                <div class="w-full h-3 rounded-full bg-surface-container-high overflow-hidden">
                                    <div class="h-full bg-primary-container rounded-full transition-all duration-500 ease-out" id="progress-gas" style="width: 0%;"></div>
                                </div>
                            </div>
                            <div class="mt-space-sm p-space-sm rounded-2xl bg-surface-container-low flex items-center justify-between">
                                <div class="flex items-center gap-space-xs">
                                    <span class="material-symbols-outlined text-[18px] text-secondary">mode_fan</span>
                                    <span class="text-xs text-on-surface-variant">Ambang Bau Aktuator:</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface font-bold text-[11px]">&ge; 2500 ADC</span>
                            </div>
                        </div>
                    </div>

                    <!-- Gas Sub-Strip -->
                    <div class="grid grid-cols-3 gap-space-sm pt-space-md border-t border-surface-container">
                        <div class="flex flex-col">
                            <span class="text-[11px] text-on-surface-variant">Tidak Bau</span>
                            <span class="text-xs text-tertiary font-semibold">&le; 2000 ADC</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] text-on-surface-variant">Toleransi Normal</span>
                            <span class="text-xs text-secondary font-semibold">2000 - 2500</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] text-on-surface-variant">Bau Terdeteksi</span>
                            <span class="text-xs text-error font-semibold">&ge; 2500 ADC</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Smart Actuators & Ecosystem Automation Hub -->
            <div class="mt-space-lg grid grid-cols-1 md:grid-cols-12 gap-space-lg w-full">

                <!-- Actuator Summary Card (Contract: id="val-actuator-summary", id="status-led", id="status-buzzer") -->
                <div class="md:col-span-12 lg:col-span-5 bg-surface-container-lowest rounded-3xl p-space-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col justify-between border border-surface-container-high/40">
                    <div class="flex items-center justify-between pb-space-sm">
                        <div class="flex items-center gap-space-sm">
                            <div class="w-10 h-10 rounded-2xl bg-surface-container flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-[22px]">tune</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase font-semibold">Relay &amp; Aktuator Fisik</span>
                                <h3 class="text-base text-on-surface font-bold">Status Aktuator ESP32</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Alarm Summary Block -->
                    <div class="p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 my-space-sm border border-surface-container" id="box-actuator-summary">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-on-surface-variant">Keputusan Logika Server</span>
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-tertiary" id="val-actuator-summary">STANDBY</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-tertiary" id="indicator-alarm-dot"></span>
                        </div>
                        <p class="text-xs text-on-surface-variant" id="desc-actuator-summary">Kondisi keranjang aman dan kualitas udara normal.</p>
                    </div>

                    <!-- Physical Pins Status Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-space-sm pt-space-xs">
                        <!-- LED Tile -->
                        <div class="p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 border border-surface-container">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-on-surface-variant font-medium">Pin GPIO 25</span>
                                <span class="w-2 h-2 rounded-full bg-outline-variant" id="dot-led"></span>
                            </div>
                            <span class="text-xs text-on-surface font-semibold">LED Merah</span>
                            <span class="text-sm font-bold text-on-surface-variant tag-pill pill-green" id="status-led">PADAM</span>
                        </div>

                        <!-- Buzzer Tile -->
                        <div class="p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 border border-surface-container">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-on-surface-variant font-medium">Pin GPIO 26</span>
                                <span class="w-2 h-2 rounded-full bg-outline-variant" id="dot-buzzer"></span>
                            </div>
                            <span class="text-xs text-on-surface font-semibold">Active Buzzer 5V</span>
                            <span class="text-sm font-bold text-on-surface-variant tag-pill pill-green" id="status-buzzer">HENING</span>
                        </div>

                        <!-- Servo Lid Tile -->
                        <div class="p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 border border-surface-container">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-on-surface-variant font-medium">Pin GPIO 13</span>
                                <span class="w-2 h-2 rounded-full bg-outline-variant" id="dot-servo"></span>
                            </div>
                            <span class="text-xs text-on-surface font-semibold">Motor Servo Tutup</span>
                            <span class="text-sm font-bold text-on-surface-variant tag-pill" id="status-servo">Tutup Tertutup</span>
                        </div>
                    </div>

                    <!-- Quick Action Manual Silence -->
                    <button class="w-full mt-space-md h-11 rounded-full bg-surface-container text-on-surface text-xs font-semibold hover:bg-surface-container-high transition-colors flex items-center justify-center gap-space-xs cursor-pointer" id="btn-silence-alarm" type="button">
                        <span class="material-symbols-outlined text-[18px]">volume_off</span>
                        <span>Senyapkan Alarm Sementara (10 mnt)</span>
                    </button>
                </div>

                <!-- Ecosystem Automation Context Hub -->
                <div class="md:col-span-12 lg:col-span-7 bg-surface-container-lowest rounded-3xl p-space-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col justify-between border border-surface-container-high/40">
                    <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
                        <div class="flex items-center gap-space-sm">
                            <div class="w-10 h-10 rounded-2xl bg-primary-container/20 flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-[22px]">hub</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-on-surface-variant uppercase font-semibold">Sistem Otomasi Cerdas</span>
                                <h3 class="text-base text-on-surface font-bold">Alur Kerja Pengelolaan Cucian</h3>
                            </div>
                        </div>
                        <span class="text-xs text-primary font-semibold bg-primary-container/20 px-3 py-1 rounded-full">Sistem Aktif</span>
                    </div>

                    <!-- 3-Step Multi-Appliance Visual Flow -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-space-md py-space-md">
                        <!-- Step 1: Laundry Basket -->
                        <div class="p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 border border-surface-container">
                            <div class="flex items-center justify-between text-primary">
                                <span class="material-symbols-outlined text-[22px]">shopping_basket</span>
                                <span class="text-[10px] font-bold text-primary uppercase">Deteksi</span>
                            </div>
                            <span class="text-xs text-on-surface font-bold">1. Monitoring Keranjang</span>
                            <span class="text-[11px] text-on-surface-variant">Sensor ultrasonik &amp; gas mengirim data mentah via HTTP POST ke server.</span>
                        </div>

                        <!-- Step 2: Server-Side Decision -->
                        <div class="p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 border border-surface-container">
                            <div class="flex items-center justify-between text-secondary">
                                <span class="material-symbols-outlined text-[22px]">dns</span>
                                <span class="text-[10px] font-bold text-secondary uppercase">Analisis</span>
                            </div>
                            <span class="text-xs text-on-surface font-bold">2. Logika Terpusat</span>
                            <span class="text-[11px] text-on-surface-variant">Backend PHP mengevaluasi ambang batas, catat log, dan kirim instruksi aktuator.</span>
                        </div>

                        <!-- Step 3: Notification & Action -->
                        <div class="p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 border border-surface-container">
                            <div class="flex items-center justify-between text-tertiary">
                                <span class="material-symbols-outlined text-[22px]">notifications_active</span>
                                <span class="text-[10px] font-bold text-tertiary uppercase">Respons</span>
                            </div>
                            <span class="text-xs text-on-surface font-bold">3. Notifikasi &amp; Alarm</span>
                            <span class="text-[11px] text-on-surface-variant">ESP32 membunyikan buzzer &amp; LED, dashboard menampilkan push alert instan.</span>
                        </div>
                    </div>

                    <!-- Ambient Automation Control Footer -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-space-sm pt-space-xs">
                        <div class="flex items-center gap-space-xs text-on-surface-variant text-xs">
                            <span class="material-symbols-outlined text-primary text-[18px]">auto_awesome</span>
                            <span>Auto-Sync aktif: Dashboard memantau status secara kontinu setiap 3 detik.</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Historical Log Table (Contract: id="table-history-body") -->
            <div class="mt-space-lg w-full bg-surface-container-lowest rounded-3xl p-space-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col gap-space-md border border-surface-container-high/40" id="table-history-section">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm border-b border-surface-container">
                    <div>
                        <span class="text-[10px] text-on-surface-variant uppercase font-semibold">Audit Telemetri IoT</span>
                        <h3 class="text-base text-on-surface font-bold">Log Riwayat Sensor &amp; Respons Aktuator</h3>
                    </div>
                    <span class="text-xs text-on-surface-variant bg-surface-container-low px-space-md py-1.5 rounded-full font-medium">
                        Menampilkan 5 Data Terkini
                    </span>
                </div>

                <!-- Responsive Table Container -->
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-on-surface-variant text-xs font-semibold border-b border-surface-container bg-surface-container-low/40">
                                <th class="py-3 px-4 rounded-l-2xl">Waktu Transmisi</th>
                                <th class="py-3 px-4">Jarak Sensor</th>
                                <th class="py-3 px-4">Kapasitas</th>
                                <th class="py-3 px-4">Level Gas MQ-135</th>
                                <th class="py-3 px-4">Indeks Bau</th>
                                <th class="py-3 px-4">Tutup Keranjang</th>
                                <th class="py-3 px-4">LED</th>
                                <th class="py-3 px-4">Buzzer</th>
                                <th class="py-3 px-4 rounded-r-2xl">Status Alarm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container text-xs text-on-surface" id="table-history-body">
                            <tr>
                                <td colspan="9" class="text-center py-6 text-on-surface-variant font-medium">Memuat riwayat telemetri dari server...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- Footer -->
<footer class="w-full bg-surface-container-lowest shadow-[0_-1px_6px_rgba(0,0,0,0.02)] border-t border-surface-container">
    <div class="w-full max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop py-space-md flex flex-col md:flex-row items-center justify-between gap-space-sm">
        <div class="flex items-center gap-space-sm">
            <span class="w-2 h-2 rounded-full bg-tertiary-container"></span>
            <span class="text-xs text-on-surface-variant">Smart Laundry Basket IoT Hub &bull; Thin Client ESP32 &amp; PHP Server</span>
        </div>
        <div class="text-xs text-on-surface-variant">&copy; 2024-2026 Smart Laundry Basket System. All rights reserved.</div>
    </div>
</footer>

<script>
(function () {
    'use strict';

    // --- Konstanta Fisik & Ambang Batas ---
    const BASKET_TOTAL_HEIGHT = 40.0; // cm (kosong)
    const CAPACITY_THRESHOLD = 10.0;  // <= 10.0 cm = Penuh
    const GAS_TIDAK_BAU_MAX = 2000;   // <= 2000 = Tidak Bau
    const GAS_BAU_MIN = 2500;         // >= 2500 = Bau
    const POLLING_INTERVAL_MS = 3000; // Polling 3 detik

    // --- State Locks untuk Mencegah Spamming Notifikasi ---
    let prevPenuh = false;
    let prevBau = false;
    let alarmSilenced = false;
    let isFetching = false;

    // --- Cache Elemen DOM (Sesuai Kontrak design.md) ---
    const toast = document.getElementById('notification-toast');
    const toastTitle = document.getElementById('notification-toast-title');
    const toastMsg = document.getElementById('notification-toast-msg');
    const btnToastDismiss = document.getElementById('btn-toast-dismiss');
    const btnToastClose = document.getElementById('btn-toast-close');
    const btnEnableWebNotify = document.getElementById('btn-enable-web-notify');

    const notificationBell = document.getElementById('notification-bell');
    const notificationBadge = document.getElementById('notification-badge');
    const notificationPopover = document.getElementById('notification-popover');
    const notificationDrawerList = document.getElementById('notification-drawer-list');
    const notificationDrawerCount = document.getElementById('notification-drawer-count');
    const btnClearNotifications = document.getElementById('btn-clear-notifications');

    const lastUpdateText = document.getElementById('last-update-text');
    const btnManualRefresh = document.getElementById('btn-manual-refresh');
    const connectionStatusBadge = document.getElementById('connection-status-badge');
    const connectionPulse = document.getElementById('connection-pulse');
    const connectionText = document.getElementById('connection-text');

    const cardCapacity = document.getElementById('card-capacity');
    const valDistance = document.getElementById('val-distance');
    const badgeCapacity = document.getElementById('badge-capacity');
    const progressCapacity = document.getElementById('progress-capacity');
    const labelCapacityPct = document.getElementById('label-capacity-pct');
    const basketLiquidFill = document.getElementById('basket-liquid-fill');

    const cardGas = document.getElementById('card-gas');
    const valGas = document.getElementById('val-gas');
    const badgeGas = document.getElementById('badge-gas');
    const progressGas = document.getElementById('progress-gas');
    const labelGasThreshold = document.getElementById('label-gas-threshold');
    const svgGasRing = document.getElementById('svg-gas-ring');
    const iconGasStatus = document.getElementById('icon-gas-status');
    const labelGasState = document.getElementById('label-gas-state');

    const valActuatorSummary = document.getElementById('val-actuator-summary');
    const descActuatorSummary = document.getElementById('desc-actuator-summary');
    const boxActuatorSummary = document.getElementById('box-actuator-summary');
    const indicatorAlarmDot = document.getElementById('indicator-alarm-dot');
    const statusLed = document.getElementById('status-led');
    const statusBuzzer = document.getElementById('status-buzzer');
    const statusServo = document.getElementById('status-servo');
    const dotLed = document.getElementById('dot-led');
    const dotBuzzer = document.getElementById('dot-buzzer');
    const dotServo = document.getElementById('dot-servo');
    const btnSilenceAlarm = document.getElementById('btn-silence-alarm');

    const tableHistoryBody = document.getElementById('table-history-body');

    let notificationItemsCount = 0;

    // --- Notifikasi Drawer & Popover Toggle ---
    if (notificationBell && notificationPopover) {
        notificationBell.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationPopover.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!notificationPopover.contains(e.target) && !notificationBell.contains(e.target)) {
                notificationPopover.classList.add('hidden');
            }
        });
    }

    if (btnClearNotifications) {
        btnClearNotifications.addEventListener('click', () => {
            if (notificationDrawerList) {
                notificationDrawerList.innerHTML = '<div class="p-space-sm text-center text-xs text-on-surface-variant">Tidak ada notifikasi aktif.</div>';
            }
            if (notificationBadge) {
                notificationBadge.classList.add('hidden');
                notificationBadge.textContent = '0';
            }
            if (notificationDrawerCount) {
                notificationDrawerCount.textContent = '0 Notifikasi';
            }
            notificationItemsCount = 0;
        });
    }

    // --- Dismiss Toast Handler ---
    const dismissToast = () => {
        if (toast) {
            toast.classList.add('translate-y-[-20px]', 'opacity-0', 'pointer-events-none');
            setTimeout(() => toast.classList.add('hidden'), 400);
        }
    };
    if (btnToastDismiss) btnToastDismiss.addEventListener('click', dismissToast);
    if (btnToastClose) btnToastClose.addEventListener('click', dismissToast);

    // --- Browser Web Push Notification Permission ---
    if (btnEnableWebNotify) {
        btnEnableWebNotify.addEventListener('click', async () => {
            if ('Notification' in window) {
                try {
                    const permission = await Notification.requestPermission();
                    if (permission === 'granted') {
                        btnEnableWebNotify.innerHTML = '<span class="material-symbols-outlined text-[15px]">done</span><span>Web Push Aktif</span>';
                        btnEnableWebNotify.classList.remove('bg-primary');
                        btnEnableWebNotify.classList.add('bg-tertiary', 'pointer-events-none');
                        new Notification('Smart Laundry Basket IoT', {
                            body: 'Notifikasi Cerdas peramban berhasil diaktifkan.',
                            icon: 'https://cdn-icons-png.flaticon.com/512/995/995053.png'
                        });
                    } else {
                        alert('Izin notifikasi tidak diberikan oleh peramban.');
                    }
                } catch (err) {
                    console.error('Gagal meminta izin notifikasi:', err);
                }
            } else {
                alert('Peramban ini tidak mendukung HTML5 Web Notification.');
            }
        });
    }

    // --- Tombol Senyapkan Alarm Sementara ---
    if (btnSilenceAlarm) {
        btnSilenceAlarm.addEventListener('click', () => {
            alarmSilenced = true;
            btnSilenceAlarm.innerHTML = '<span class="material-symbols-outlined text-[18px]">check</span><span>Alarm Disenyapkan (10 mnt)</span>';
            btnSilenceAlarm.classList.add('bg-tertiary-container/30', 'text-on-tertiary-container');
            if (statusBuzzer) {
                statusBuzzer.textContent = 'MUTED';
                statusBuzzer.className = 'text-sm font-bold text-tertiary';
            }
            setTimeout(() => {
                alarmSilenced = false;
                btnSilenceAlarm.innerHTML = '<span class="material-symbols-outlined text-[18px]">volume_off</span><span>Senyapkan Alarm Sementara (10 mnt)</span>';
                btnSilenceAlarm.classList.remove('bg-tertiary-container/30', 'text-on-tertiary-container');
            }, 600000);
        });
    }

    // --- Fungsi Pemicu Notifikasi Real-Time (State Lock) ---
    function triggerSmartNotification(title, message) {
        // 1. Tampilkan Floating Toast
        if (toast && toastMsg) {
            if (toastTitle) toastTitle.textContent = title;
            toastMsg.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.remove('translate-y-[-20px]', 'opacity-0', 'pointer-events-none');
            }, 20);
        }

        // 2. Tambahkan ke Popover Drawer List
        if (notificationDrawerList) {
            const timeNow = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            
            // Hapus pesan placeholder jika baru pertama kali
            if (notificationItemsCount === 0) {
                notificationDrawerList.innerHTML = '';
            }

            notificationItemsCount++;
            const item = document.createElement('div');
            item.className = 'p-space-sm rounded-2xl bg-error-container/40 flex items-start gap-space-sm border border-error/10 transition-all';
            item.innerHTML = `
                <span class="material-symbols-outlined text-error text-[18px] mt-0.5 shrink-0">notification_important</span>
                <div class="flex flex-col flex-1">
                    <span class="text-xs text-on-surface font-semibold">${timeNow} - ${title}</span>
                    <span class="text-[11px] text-on-surface-variant">${message}</span>
                </div>
            `;
            notificationDrawerList.prepend(item);

            if (notificationBadge) {
                notificationBadge.classList.remove('hidden');
                notificationBadge.textContent = notificationItemsCount.toString();
            }
            if (notificationDrawerCount) {
                notificationDrawerCount.textContent = `${notificationItemsCount} Baru`;
            }
        }

        // 3. HTML5 Browser Push Notification
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification(`Smart Laundry Basket: ${title}`, {
                    body: message,
                    icon: 'https://cdn-icons-png.flaticon.com/512/995/995053.png'
                });
            } catch (e) {
                console.warn('Native notification failed:', e);
            }
        }
    }

    // --- Render Data Telemetri & DOM Updates ---
    function updateDashboard(payload) {
        const latest = payload.latest;
        const history = payload.history || [];

        if (!latest) {
            if (valDistance) valDistance.textContent = '--';
            if (valGas) valGas.textContent = '--';
            if (lastUpdateText) lastUpdateText.textContent = 'Belum ada data sensor';
            if (tableHistoryBody) {
                tableHistoryBody.innerHTML = '<tr><td colspan="9" class="text-center py-6 text-on-surface-variant font-medium">Belum ada catatan log telemetri dari ESP32.</td></tr>';
            }
            return;
        }

        // 1. Kapasitas & Jarak Ultrasonik
        const distance = parseFloat(latest.jarak_cm);
        if (valDistance) valDistance.textContent = distance.toFixed(1);

        // Hitung persentase kapasitas: 40 cm = 0%, <= 10 cm = 100%
        let fillPct = 0;
        if (distance <= CAPACITY_THRESHOLD) {
            fillPct = 100;
        } else if (distance >= BASKET_TOTAL_HEIGHT) {
            fillPct = 0;
        } else {
            fillPct = Math.round(((BASKET_TOTAL_HEIGHT - distance) / (BASKET_TOTAL_HEIGHT - CAPACITY_THRESHOLD)) * 100);
        }
        fillPct = Math.min(Math.max(fillPct, 0), 100);

        if (labelCapacityPct) labelCapacityPct.textContent = `${fillPct}% Terisi`;

        const isFull = (latest.status_penuh === 1) || (distance <= CAPACITY_THRESHOLD);

        if (progressCapacity) {
            progressCapacity.style.width = `${fillPct}%`;
            progressCapacity.className = isFull 
                ? 'h-full bg-error rounded-full transition-all duration-500 ease-out' 
                : 'h-full bg-primary-container rounded-full transition-all duration-500 ease-out';
        }

        if (basketLiquidFill) {
            basketLiquidFill.style.height = `${fillPct}%`;
            basketLiquidFill.className = isFull
                ? 'w-full rounded-2xl bg-gradient-to-t from-error to-primary-container transition-all duration-700 ease-out flex items-center justify-center relative overflow-hidden'
                : 'w-full rounded-2xl bg-gradient-to-t from-primary to-primary-container transition-all duration-700 ease-out flex items-center justify-center relative overflow-hidden';
        }

        if (badgeCapacity) {
            if (isFull) {
                badgeCapacity.textContent = 'KERANJANG PENUH';
                badgeCapacity.className = 'px-space-md py-1 rounded-full bg-error-container text-error text-xs font-bold tracking-tight metric-badge';
            } else {
                badgeCapacity.textContent = 'KAPASITAS AMAN';
                badgeCapacity.className = 'px-space-md py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed text-xs font-bold tracking-tight metric-badge';
            }
        }

        // 2. Gas ADC MQ-135 & Kualitas Udara
        const gas = parseInt(latest.gas_adc, 10);
        if (valGas) valGas.textContent = gas;

        const gasPct = Math.min(Math.max(Math.round((gas / 4095) * 100), 0), 100);

        // Kategori Bau: <= 2000 (Tidak Bau) | 2000 - 2500 (Normal) | >= 2500 (Bau)
        let odorStatus = latest.status_bau;
        if (!odorStatus || odorStatus === '0' || odorStatus === '1') {
            if (gas <= GAS_TIDAK_BAU_MAX) odorStatus = 'Tidak Bau';
            else if (gas < GAS_BAU_MIN) odorStatus = 'Normal';
            else odorStatus = 'Bau';
        }

        const isOdor = (odorStatus === 'Bau') || (gas >= GAS_BAU_MIN);
        const isNormalOdor = (odorStatus === 'Normal') || (!isOdor && gas > GAS_TIDAK_BAU_MAX);

        if (progressGas) {
            progressGas.style.width = `${gasPct}%`;
            if (isOdor) {
                progressGas.className = 'h-full bg-error rounded-full transition-all duration-500 ease-out';
            } else if (isNormalOdor) {
                progressGas.className = 'h-full bg-secondary-container rounded-full transition-all duration-500 ease-out';
            } else {
                progressGas.className = 'h-full bg-primary-container rounded-full transition-all duration-500 ease-out';
            }
        }

        if (badgeGas) {
            if (isOdor) {
                badgeGas.textContent = 'BAU TERDETEKSI';
                badgeGas.className = 'px-space-md py-1 rounded-full bg-error-container text-error text-xs font-bold tracking-tight metric-badge';
            } else if (isNormalOdor) {
                badgeGas.textContent = 'NORMAL';
                badgeGas.className = 'px-space-md py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed text-xs font-bold tracking-tight metric-badge';
            } else {
                badgeGas.textContent = 'TIDAK BAU';
                badgeGas.className = 'px-space-md py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed text-xs font-bold tracking-tight metric-badge';
            }
        }

        // Update SVG Radial Ring
        if (svgGasRing) {
            const perimeter = 314.16;
            const offset = perimeter * (1 - (gas / 4095));
            svgGasRing.style.strokeDashoffset = offset.toFixed(1);
            if (isOdor) {
                svgGasRing.className.baseVal = 'text-error transition-all duration-700 ease-out';
                if (iconGasStatus) { iconGasStatus.textContent = 'warning'; iconGasStatus.className = 'material-symbols-outlined text-error text-[24px]'; }
                if (labelGasState) { labelGasState.textContent = 'Bau!'; labelGasState.className = 'text-xs text-error font-bold'; }
            } else if (isNormalOdor) {
                svgGasRing.className.baseVal = 'text-secondary transition-all duration-700 ease-out';
                if (iconGasStatus) { iconGasStatus.textContent = 'air'; iconGasStatus.className = 'material-symbols-outlined text-secondary text-[24px]'; }
                if (labelGasState) { labelGasState.textContent = 'Normal'; labelGasState.className = 'text-xs text-secondary font-bold'; }
            } else {
                svgGasRing.className.baseVal = 'text-primary transition-all duration-700 ease-out';
                if (iconGasStatus) { iconGasStatus.textContent = 'cloud_done'; iconGasStatus.className = 'material-symbols-outlined text-primary text-[24px]'; }
                if (labelGasState) { labelGasState.textContent = 'Segar'; labelGasState.className = 'text-xs text-primary font-bold'; }
            }
        }

        // 3. Status Aktuator Fisik ESP32
        const isAlarm = (isFull || isOdor) && !alarmSilenced;

        if (valActuatorSummary) {
            if (isAlarm) {
                valActuatorSummary.textContent = 'ALARM AKTIF!';
                valActuatorSummary.className = 'text-xl font-bold text-error';
                if (descActuatorSummary) descActuatorSummary.textContent = 'Keranjang penuh atau terdeteksi bau. LED & Buzzer diaktifkan.';
                if (boxActuatorSummary) boxActuatorSummary.className = 'p-space-md rounded-2xl bg-error-container/30 flex flex-col gap-1 my-space-sm border border-error/20';
                if (indicatorAlarmDot) indicatorAlarmDot.className = 'w-2.5 h-2.5 rounded-full bg-error animate-ping';
            } else {
                valActuatorSummary.textContent = 'STANDBY NORMAL';
                valActuatorSummary.className = 'text-xl font-bold text-tertiary';
                if (descActuatorSummary) descActuatorSummary.textContent = 'Kondisi keranjang aman dan kualitas udara normal.';
                if (boxActuatorSummary) boxActuatorSummary.className = 'p-space-md rounded-2xl bg-surface-container-low flex flex-col gap-1 my-space-sm border border-surface-container';
                if (indicatorAlarmDot) indicatorAlarmDot.className = 'w-2.5 h-2.5 rounded-full bg-tertiary';
            }
        }

        if (statusLed) {
            statusLed.textContent = (isFull || isOdor) ? 'MENYALA' : 'PADAM';
            statusLed.className = (isFull || isOdor) ? 'text-sm font-bold text-error' : 'text-sm font-bold text-on-surface-variant';
            if (dotLed) dotLed.className = (isFull || isOdor) ? 'w-2 h-2 rounded-full bg-error animate-pulse' : 'w-2 h-2 rounded-full bg-outline-variant';
        }

        if (statusBuzzer) {
            if (alarmSilenced) {
                statusBuzzer.textContent = 'MUTED (10m)';
                statusBuzzer.className = 'text-sm font-bold text-tertiary';
                if (dotBuzzer) dotBuzzer.className = 'w-2 h-2 rounded-full bg-tertiary';
            } else {
                statusBuzzer.textContent = (isFull || isOdor) ? 'BUNYI ALARM' : 'HENING';
                statusBuzzer.className = (isFull || isOdor) ? 'text-sm font-bold text-error' : 'text-sm font-bold text-on-surface-variant';
                if (dotBuzzer) dotBuzzer.className = (isFull || isOdor) ? 'w-2 h-2 rounded-full bg-error animate-ping' : 'w-2 h-2 rounded-full bg-outline-variant';
            }
        }

        if (statusServo) {
            const isLidOpen = (latest.status_tutup === 1);
            statusServo.textContent = isLidOpen ? 'Tutup Terbuka (Aktif)' : 'Tutup Tertutup';
            statusServo.className = isLidOpen ? 'text-sm font-bold text-secondary' : 'text-sm font-bold text-on-surface-variant';
            if (dotServo) {
                dotServo.className = isLidOpen ? 'w-2 h-2 rounded-full bg-secondary animate-pulse' : 'w-2 h-2 rounded-full bg-outline-variant';
            }
        }

        // 4. Logika State Lock Notifikasi Real-Time (Mencegah Spamming Polling 3s)
        if (isFull && !prevPenuh) {
            triggerSmartNotification(
                'Keranjang Cucian Penuh',
                `Jarak sensor ${distance.toFixed(1)} cm (terisi ${fillPct}%). Segera pindahkan pakaian ke mesin cuci.`
            );
            prevPenuh = true;
        } else if (!isFull) {
            prevPenuh = false;
        }

        if (isOdor && !prevBau) {
            triggerSmartNotification(
                'Aroma Lembab Berlebih Terdeteksi',
                `Sensor gas mencapai ${gas} ADC (Status: Bau). Sistem memicu alarm sanitasi & sirkulasi udara.`
            );
            prevBau = true;
        } else if (!isOdor) {
            prevBau = false;
        }

        // 5. Update Waktu Pembaruan Terakhir
        if (lastUpdateText) {
            lastUpdateText.textContent = latest.dibuat_pada || payload.server_time || 'Baru saja';
        }

        // 6. Render Tabel Riwayat
        renderTableHistory(history);
    }

    // --- Render Tabel Riwayat 5 Log Sensor ---
    function renderTableHistory(rows) {
        if (!tableHistoryBody) return;

        if (!rows || rows.length === 0) {
            tableHistoryBody.innerHTML = '<tr><td colspan="9" class="text-center py-6 text-on-surface-variant font-medium">Belum ada rekaman riwayat sensor.</td></tr>';
            return;
        }

        let html = '';
        rows.forEach(function (row) {
            const rowDist = parseFloat(row.jarak_cm).toFixed(1);
            const rowGas = parseInt(row.gas_adc, 10);
            const rowIsFull = (row.status_penuh === 1 || parseFloat(row.jarak_cm) <= CAPACITY_THRESHOLD);

            let rowOdor = row.status_bau;
            if (!rowOdor || rowOdor === '0' || rowOdor === '1') {
                if (rowGas <= GAS_TIDAK_BAU_MAX) rowOdor = 'Tidak Bau';
                else if (rowGas < GAS_BAU_MIN) rowOdor = 'Normal';
                else rowOdor = 'Bau';
            }

            const rowIsOdor = (rowOdor === 'Bau' || rowGas >= GAS_BAU_MIN);
            const rowIsNormalOdor = (rowOdor === 'Normal' || (!rowIsOdor && rowGas > GAS_TIDAK_BAU_MAX));
            const rowIsAlarm = (rowIsFull || rowIsOdor);

            let odorPill = '<span class="px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed text-[11px] font-bold">TIDAK BAU</span>';
            if (rowIsOdor) {
                odorPill = '<span class="px-2.5 py-1 rounded-full bg-error-container text-error text-[11px] font-bold">BAU</span>';
            } else if (rowIsNormalOdor) {
                odorPill = '<span class="px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed text-[11px] font-bold">NORMAL</span>';
            }

            const capPill = rowIsFull
                ? '<span class="px-2.5 py-1 rounded-full bg-error-container text-error text-[11px] font-bold">PENUH</span>'
                : '<span class="px-2.5 py-1 rounded-full bg-surface-container-high text-on-surface-variant text-[11px] font-bold">AMAN</span>';

            const rowIsLidOpen = (row.status_tutup === 1);
            const lidPill = rowIsLidOpen
                ? '<span class="px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-fixed text-[11px] font-bold">TERBUKA</span>'
                : '<span class="px-2.5 py-1 rounded-full bg-surface-container-high text-on-surface-variant text-[11px] font-bold">TERTUTUP</span>';

            const ledText = rowIsAlarm ? '<span class="text-error font-semibold">ON</span>' : '<span class="text-on-surface-variant">OFF</span>';
            const buzzerText = rowIsAlarm ? '<span class="text-error font-semibold">ON</span>' : '<span class="text-on-surface-variant">OFF</span>';
            const alarmBadge = rowIsAlarm
                ? '<span class="px-2 py-0.5 rounded-full bg-error text-white text-[10px] font-semibold">ALARM AKTIF</span>'
                : '<span class="px-2 py-0.5 rounded-full bg-tertiary-container/30 text-on-tertiary-container text-[10px] font-semibold">STANDBY</span>';

            html += `
                <tr class="hover:bg-surface-container-low/60 transition-colors">
                    <td class="py-3 px-4 font-semibold text-on-surface">${row.dibuat_pada}</td>
                    <td class="py-3 px-4">${rowDist} cm</td>
                    <td class="py-3 px-4">${capPill}</td>
                    <td class="py-3 px-4">${rowGas} ADC</td>
                    <td class="py-3 px-4">${odorPill}</td>
                    <td class="py-3 px-4">${lidPill}</td>
                    <td class="py-3 px-4">${ledText}</td>
                    <td class="py-3 px-4">${buzzerText}</td>
                    <td class="py-3 px-4">${alarmBadge}</td>
                </tr>
            `;
        });

        tableHistoryBody.innerHTML = html;
    }

    // --- Status Konektivitas Jaringan Polling ---
    function setConnectionStatus(online, message) {
        if (online) {
            if (connectionStatusBadge) {
                connectionStatusBadge.className = 'flex items-center gap-space-xs bg-surface-container-lowest px-space-md py-2 rounded-full shadow-sm text-primary border border-surface-container';
            }
            if (connectionPulse) {
                connectionPulse.className = 'w-2.5 h-2.5 rounded-full bg-tertiary-container animate-pulse';
            }
            if (connectionText) {
                connectionText.textContent = 'Live Sync (3s)';
            }
        } else {
            if (connectionStatusBadge) {
                connectionStatusBadge.className = 'flex items-center gap-space-xs bg-error-container px-space-md py-2 rounded-full shadow-sm text-error border border-error/20';
            }
            if (connectionPulse) {
                connectionPulse.className = 'w-2.5 h-2.5 rounded-full bg-error';
            }
            if (connectionText) {
                connectionText.textContent = message || 'Offline / Error';
            }
        }
    }

    // --- Polling Handler via Native Fetch API ---
    async function fetchLatestData() {
        if (isFetching) return;
        isFetching = true;

        try {
            const response = await fetch('api/latest.php', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('HTTP Status ' + response.status);
            }

            const data = await response.json();

            if (data.status === 'success') {
                updateDashboard(data);
                setConnectionStatus(true);
            } else {
                setConnectionStatus(false, data.message || 'Respons tidak valid');
            }
        } catch (err) {
            console.warn('Gagal sinkronisasi data telemetri:', err);
            setConnectionStatus(false, 'Gagal terhubung ke API');
        } finally {
            isFetching = false;
        }
    }

    // --- Tombol Manual Refresh Event Listener ---
    if (btnManualRefresh) {
        btnManualRefresh.addEventListener('click', () => {
            btnManualRefresh.classList.add('rotate-180');
            setTimeout(() => btnManualRefresh.classList.remove('rotate-180'), 400);
            fetchLatestData();
        });
    }

    // Inisialisasi awal saat halaman dimuat
    fetchLatestData();

    // Jalankan auto-polling interval 3 detik
    setInterval(fetchLatestData, POLLING_INTERVAL_MS);

})();
</script>

</body>
</html>
