<?php
/**
 * Smart Laundry Basket System
 * File: public/login.php
 * Halaman Otentikasi Pengguna & Manajemen Sesi Masuk
 * Desain: Xiaomi Mijia Smart Home Style (Google Stitch Reference)
 */

require_once __DIR__ . '/config.php';

startSecureSession();

// Jika pengguna sudah terautentikasi, alihkan langsung ke dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$usernameVal = '';

// Proses pengiriman formulir POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $usernameVal = trim($_POST['username'] ?? '');
    $passwordVal = $_POST['password'] ?? '';

    if (!verifyCsrfToken($submittedToken)) {
        $errorMessage = 'Validasi sesi keamanan (CSRF) gagal. Silakan muat ulang halaman.';
    } elseif ($usernameVal === '' || $passwordVal === '') {
        $errorMessage = 'Nama pengguna dan kata sandi wajib diisi.';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $usernameVal]);
            $user = $stmt->fetch();

            if ($user && password_verify($passwordVal, $user['password'])) {
                // Regenerasi ID sesi untuk mencegah session fixation attacks
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in_at'] = time();

                // Alihkan ke halaman dashboard utama
                header('Location: index.php');
                exit;
            } else {
                $errorMessage = 'Nama pengguna atau kata sandi yang Anda masukkan tidak valid.';
            }
        } catch (PDOException $e) {
            error_log("Login Database Error: " . $e->getMessage());
            $errorMessage = 'Terjadi kesalahan sistem internal. Silakan coba sesaat lagi.';
        }
    }
}

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - Smart Laundry Basket System</title>
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
                        "tertiary": "#006c46",
                        "on-tertiary": "#ffffff",
                        "tertiary-container": "#2dc687",
                        "on-tertiary-container": "#004c30",
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
                    fontFamily: {
                        sans: ["Plus Jakarta Sans", "system-ui", "sans-serif"]
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-background font-sans text-on-surface antialiased min-h-screen flex items-center justify-center p-4">

<main class="w-full flex items-center justify-center">
    <div class="flex flex-col w-full items-center justify-center py-6">

        <!-- Dynamic Ambient Glow Element (Contained) -->
        <div class="relative w-full max-w-[440px] flex flex-col items-center">
            <div class="absolute -top-12 -z-10 w-72 h-72 rounded-full bg-primary-container/15 blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 -right-8 -z-10 w-48 h-48 rounded-full bg-secondary-container/15 blur-2xl pointer-events-none"></div>

            <!-- Main Auth Squircle Card (Xiaomi Home Aesthetic) -->
            <div class="w-full bg-surface-container-lowest rounded-[24px] shadow-[0_8px_30px_rgba(29,33,41,0.06)] p-8 sm:p-10 flex flex-col relative transition-all duration-300 border border-surface-container-high/60">

                <!-- Top Gateway Telemetry Pill -->
                <div class="flex items-center justify-between mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-low text-primary">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-container opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                        </span>
                        <span class="text-xs tracking-tight font-medium text-on-surface-variant">ESP32 Online</span>
                        <span class="text-outline-variant text-xs">•</span>
                        <span class="text-xs text-on-surface-variant">Gateway Siap</span>
                    </div>
                    <div class="flex items-center gap-1 text-outline">
                        <span class="material-symbols-outlined text-[18px]">wifi</span>
                        <span class="text-xs font-semibold">2.4GHz</span>
                    </div>
                </div>

                <!-- Mijia Hardware Icon Emblem -->
                <div class="flex flex-col items-center text-center">
                    <div class="relative flex items-center justify-center w-16 h-16 rounded-[20px] bg-surface-container-low shadow-[0_4px_16px_rgba(0,196,180,0.18)] mb-4">
                        <div class="absolute inset-0 rounded-[20px] bg-gradient-to-tr from-primary-container/20 to-transparent"></div>
                        <!-- Clean Smart Laundry Icon Vector -->
                        <svg class="w-8 h-8 text-primary relative z-10" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24">
                            <rect height="18" rx="4" width="18" x="3" y="3"></rect>
                            <circle cx="12" cy="13" r="5"></circle>
                            <path d="M12 10a3 3 0 0 1 3 3"></path>
                            <circle cx="7" cy="7" fill="currentColor" r="1"></circle>
                            <circle cx="10" cy="7" fill="currentColor" r="1"></circle>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-on-surface tracking-tight">Smart Laundry Basket</h1>
                    <p class="text-sm text-on-surface-variant mt-1">Autentikasi Akses Konsol Monitoring IoT</p>
                </div>

                <!-- Backend-Compatible Error Notification Box -->
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert-error mt-6 p-3 rounded-xl bg-error-container text-on-error-container text-sm flex items-center gap-2 border border-error/20" id="error-container">
                        <span class="material-symbols-outlined text-[18px] text-error shrink-0">error</span>
                        <span id="error-message"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php else: ?>
                    <div class="alert-error hidden mt-6 p-3 rounded-xl bg-error-container text-on-error-container text-sm flex items-center gap-2 border border-error/20" id="error-container">
                        <span class="material-symbols-outlined text-[18px] text-error shrink-0">error</span>
                        <span id="error-message">ID Pengguna atau Kata Sandi salah.</span>
                    </div>
                <?php endif; ?>

                <!-- Authentication Form -->
                <form action="login.php" class="mt-6 flex flex-col gap-4" method="POST" autocomplete="off">
                    <!-- CSRF Token Input for Server-Side Security -->
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"/>

                    <!-- Username / User ID Field -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-on-surface-variant ml-1" for="username">Nama Pengguna (Username)</label>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-[20px] text-outline pointer-events-none">account_circle</span>
                            <input 
                                autocomplete="username" 
                                class="w-full h-12 pl-11 pr-4 bg-surface-container-low text-on-surface rounded-xl text-sm placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest focus:shadow-[0_0_0_2px_#00c4b4] transition-all border border-surface-container-high" 
                                id="username" 
                                name="username" 
                                placeholder="Masukkan username" 
                                required 
                                autofocus
                                type="text"
                                value="<?= htmlspecialchars($usernameVal, ENT_QUOTES, 'UTF-8') ?>"
                            />
                        </div>
                    </div>

                    <!-- Password Field with Visibility Toggle -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-center px-1">
                            <label class="text-xs font-semibold text-on-surface-variant" for="password">Kata Sandi (Password)</label>
                        </div>
                        <div class="relative flex items-center">
                            <span class="material-symbols-outlined absolute left-3.5 text-[20px] text-outline pointer-events-none">lock</span>
                            <input 
                                autocomplete="current-password" 
                                class="w-full h-12 pl-11 pr-11 bg-surface-container-low text-on-surface rounded-xl text-sm placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest focus:shadow-[0_0_0_2px_#00c4b4] transition-all border border-surface-container-high" 
                                id="password" 
                                name="password" 
                                placeholder="••••••••••••" 
                                required 
                                type="password"
                            />
                            <button aria-label="Tampilkan atau sembunyikan kata sandi" class="absolute right-3 p-1.5 rounded-lg text-outline hover:text-on-surface transition-colors flex items-center justify-center focus:outline-none" id="toggle-password-btn" type="button">
                                <span class="material-symbols-outlined text-[20px]" id="pwd-icon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Submission Button (Xiaomi Smart Teal Gradient) -->
                    <button class="btn-submit w-full h-12 mt-2 rounded-full bg-gradient-to-r from-primary-container to-tertiary-container hover:opacity-95 text-white font-semibold text-sm shadow-[0_8px_20px_rgba(0,196,180,0.25)] active:scale-[0.99] transition-all duration-150 flex items-center justify-center gap-2 group cursor-pointer" type="submit">
                        <span>Masuk ke Dashboard</span>
                        <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </button>
                </form>

                <!-- Default Credentials Helper -->
                <div class="default-credentials text-center text-xs text-outline mt-5 pt-3 border-t border-surface-container">
                    Default Kredensial: <code class="px-1.5 py-0.5 rounded bg-surface-container-low text-primary font-mono text-xs">admin</code> / <code class="px-1.5 py-0.5 rounded bg-surface-container-low text-primary font-mono text-xs">password123</code>
                </div>
            </div>

            <!-- Security & Protocol Footer -->
            <div class="flex flex-col items-center justify-center gap-1 mt-6 text-center">
                <div class="flex items-center gap-1.5 text-outline text-xs">
                    <span class="material-symbols-outlined text-[15px]">verified_user</span>
                    <span>Protokol Keamanan IoT • Enkripsi Sesi PHP AES-256</span>
                </div>
                <p class="text-xs text-outline/80">Smart Laundry Basket System &bull; ESP32 & PHP Backend</p>
            </div>
        </div>

    </div>
</main>

<script>
    // Interaksi Tombol Tampilkan/Sembunyikan Kata Sandi
    const toggleBtn = document.getElementById('toggle-password-btn');
    const passwordInput = document.getElementById('password');
    const pwdIcon = document.getElementById('pwd-icon');

    if (toggleBtn && passwordInput && pwdIcon) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            pwdIcon.textContent = isPassword ? 'visibility_off' : 'visibility';
        });
    }
</script>

</body>
</html>
