# Project Context: IoT Smart Laundry Basket System (Server-Side Centralized Logic)

## 1. Executive Summary & Objective
Sistem **Smart Laundry Basket** adalah solusi berbasis *Internet of Things* (IoT) yang dirancang untuk memantau kapasitas fisik tumpukan pakaian dan indikasi bau secara otomatis dan *real-time*. Arsitektur sistem menerapkan pendekatan **Centralized Server Logic (Thin Client IoT)**:
* Mikrokontroler **ESP32** berfungsi murni sebagai pengumpul data telemetri mentah (*raw data*) serta pelaksana instruksi aktuasi (*actuator executor*).
* Seluruh aturan bisnis, komputasi evaluasi ambang batas (*threshold evaluation*), penentuan status kepenuhan (`status_penuh`), deteksi bau (`status_bau`), serta keputusan aktivasi alarm dikelola sepenuhnya di sisi server backend (**PHP**).
* Data historis disimpan di basis data **MySQL** dan divisualisasikan secara dinamis pada antarmuka *dashboard web*.

---

## 2. Arsitektur Sistem (4 Layer IoT)

```text
[ Layer 1: Persepsi (Perception - Thin Client) ]
  ├── Sensor HC-SR04 (Pengukuran jarak pantul ultrasonik)
  ├── Sensor MQ-135 (Pengukuran resistansi gas analog ADC)
  └── Aktuator: LED Merah (GPIO 25) & Buzzer Aktif (GPIO 26)
         │  ▲
         │  │ (Instruksi Kontrol Aktuator / Payload Response)
         ▼  │
[ Layer 2: Jaringan & Ingress (Network & Ingress) ]
  ├── ESP32 Wi-Fi 802.11 b/g/n (Pita 2.4 GHz, Mode Station)
  ├── Gateway: Hotspot Seluler / Router Wi-Fi
  └── Transport: HTTPS POST via Ngrok / Cloudflare Tunnel
         │  ▲
         ▼  │
[ Layer 3: Pemrosesan & Logika Terpusat (Server-Side Logic) ]
  ├── Web Server: Apache 2.4 (XAMPP / Linux)
  ├── Backend Runtime: PHP 8.x
  │     ├── Otentikasi SECRET_API_KEY
  │     ├── Evaluasi Ambang Batas (Jarak <= 10.0 cm & ADC >= 1800)
  │     ├── Penentuan Flag (status_penuh & status_bau)
  │     └── Keputusan Kontrol Aktuator (alarm_active: true/false)
  └── Basis Data: MySQL (Database: smart_laundry)
         │
         ▼
[ Layer 4: Aplikasi & Presentasi (Application) ]
  ├── Autentikasi Pengguna: Session Login (login.php, logout.php)
  └── Dashboard Web: index.php (Auto-polling AJAX setiap 3 detik)
```

---

## 3. Spesifikasi Perangkat Keras & Pinout

### 3.1 Komponen Fisik & Modul
* **Mikrokontroler**: ESP32-WROOM-32 (30/38-pin DevKit).
* **Sensor Jarak 1 (Kapasitas Keranjang)**: HC-SR04 ($5\text{ V}$ VCC, rentang efektif $2\text{ cm} - 400\text{ cm}$).
* **Sensor Jarak 2 (Deteksi Pengguna / Smart Lid)**: HC-SR04 ($5\text{ V}$ VCC, rentang deteksi tangan/orang $\le 30.0\text{ cm}$).
* **Sensor Gas**: MQ-135 ($5\text{ V}$ VCC, pin Analog Out AO).
* **Aktuator Mekanik (Tutup Keranjang)**: 1x Micro Servo SG90 / High Torque MG996R ($5\text{ V}$ VCC, kendali PWM via `ESP32Servo.h`).
* **Aktuator Visual**: 1x LED Merah $5\text{ mm}$ + Resistor pembatas arus $220\ \Omega$.
* **Aktuator Audio**: 1x Active Buzzer $5\text{ V}$ (Aktif-HIGH).
* **Proteksi Logika Sinyal**: Pembagi tegangan (*voltage divider*) menggunakan resistor $1\text{ k}\Omega$ (seri) dan $2\text{ k}\Omega$ (ke GND) untuk menurunkan output $5\text{ V} \to \approx 3.3\text{ V}$ pada pin Echo HC-SR04 (baik sensor keranjang maupun sensor pengguna) dan AO MQ-135 sebelum masuk ke GPIO ESP32.

### 3.2 Tabel Pemetaan Pin (Wiring Diagram)

| Komponen | Pin Modul | Pin ESP32 | Peran Sirkuit |
| :--- | :--- | :--- | :--- |
| **HC-SR04 (Keranjang)** | VCC | VIN ($5\text{ V}$) | Catu daya modul sensor |
| | GND | GND | Ground bersama (*Common GND*) |
| | TRIG | GPIO 5 | Output pulsa pemicu ultrasonik ($10\ \mu\text{s}$) |
| | ECHO | GPIO 18 | Input pulsa pantul (melalui divider $1\text{ k}\Omega / 2\text{ k}\Omega$) |
| **HC-SR04 (Deteksi Orang)** | VCC | VIN ($5\text{ V}$) | Catu daya modul sensor |
| | GND | GND | Ground bersama (*Common GND*) |
| | TRIG | GPIO 19 | Output pulsa pemicu ultrasonik deteksi pengguna |
| | ECHO | GPIO 21 | Input pulsa pantul deteksi pengguna (divider $1\text{ k}\Omega / 2\text{ k}\Omega$) |
| **Motor Servo (Tutup)** | V+ (Merah) | VIN ($5\text{ V}$) | Catu daya motor servo |
| | GND (Cokelat) | GND | Ground bersama |
| | PWM (Oranye) | GPIO 13 | Output sinyal kendali PWM (0° = Tutup, 90° = Buka) |
| **MQ-135** | VCC | VIN ($5\text{ V}$) | Catu daya pemanas modul |
| *(Wokwi: Pot)* | GND | GND | Ground bersama |
| | AO / Wiper | GPIO 34 | Input analog ADC1 (0–4095, channel bebas konflik Wi-Fi) |
| **LED Merah** | Anoda (+) | GPIO 25 | Seri resistor $220\ \Omega$ (dikendalikan via instruksi server) |
| | Katoda (−) | GND | Common GND |
| **Buzzer** | Positif (+) | GPIO 26 | Input digital aktif-HIGH (dikendalikan via instruksi server) |
| | Negatif (−) | GND | Common GND |

---

## 4. Parameter Operasional & Ambang Batas (Server-Side Configuration)

Nilai ambang batas operasional diatur terpusat di server backend (`config.php` atau `api/data.php`), memudahkan rekalibrasi tanpa perlu memprogram ulang (*re-flashing*) firmware mikrokontroler:

* **Tinggi Keranjang Penuh ($d_{\text{full}}$)**: Jarak pantul $\le 10.0\text{ cm}$ (mengindikasikan volume cucian $\ge 75\%$).
* **Parameter Ambang Batas Status Bau ($\text{ADC}_{\text{gas}}$)**:
  * $\le 2000$: **Tidak Bau** (status aman / normal).
  * $2000 - 2500$: **Normal** (ambang toleransi normal).
  * $\ge 2500$: **Bau** (status bau terdeteksi, memicu alarm aktuator).
* **Pemrosesan di ESP32**:
  * Sampling sensor mentah setiap $2\text{ detik}$.
  * Filter median 5 sampel berurutan (jeda $15\text{ ms}$) pada sensor ultrasonik untuk meredam derau (*noise*) pantulan pakaian.
  * Transmisi data mentah (`jarak_cm`, `gas_adc`) ke server setiap $4 - 10\text{ detik}$.
  * Eksekusi status LED dan Buzzer berdasarkan parsing atribut `actuator.alarm_active` dari respon HTTP server.
* **Polling Dashboard Frontend**: Interval penarikan data via AJAX setiap $2 - 3\text{ detik}$.

---

## 5. Skema Basis Data (MySQL)

Nama Database: `smart_laundry`

### 5.1 Tabel `sensor_log` (Pencatatan Telemetri & Hasil Evaluasi Server)
```sql
CREATE TABLE sensor_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jarak_cm FLOAT NOT NULL,
  gas_adc INT NOT NULL,
  status_penuh TINYINT(1) NOT NULL,   -- Dihitung & diisi oleh logika PHP
  status_bau VARCHAR(20) NOT NULL,    -- 'Tidak Bau', 'Normal', atau 'Bau'
  status_tutup TINYINT(1) NOT NULL DEFAULT 0, -- 0 = Tertutup, 1 = Terbuka
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5.2 Tabel `users` (Manajemen Akses Dashboard)
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 6. Protokol Komunikasi & Spesifikasi API

### 6.1 `POST /api/data.php` (Ingestion & Logic Decision Endpoint)
Titik masuk utama transmisi data ESP32. Server memvalidasi payload, menghitung status, menyimpan log ke MySQL, dan mengembalikan instruksi kendali perangkat keras.

* **Headers**: 
  * `Content-Type: application/json`
  * `ngrok-skip-browser-warning: true` (jika melalui tunnel Ngrok)
* **Metode**: `POST`
* **Payload Request (ESP32 $\to$ Server)**:
  ```json
  {
    "api_key": "SMART_LAUNDRY_SECRET_KEY_2026",
    "jarak_cm": 8.75,
    "gas_adc": 1920,
    "lid_open": 1
  }
  ```
  *(Catatan: ESP32 mengirimkan data mentah beserta status tutup keranjang saat transmisi).*

* **Alur Logika Server**:
  1. Validasi kecocokan `api_key` dengan `SECRET_API_KEY`.
  2. Verifikasi ketersediaan atribut numerik `jarak_cm` dan `gas_adc`.
  3. Evaluasi logika ambang batas:
     * `$status_penuh = ($jarak_cm <= 10.0) ? 1 : 0;`
     * `$status_bau   = ($gas_adc <= 2000) ? 'Tidak Bau' : (($gas_adc < 2500) ? 'Normal' : 'Bau');`
     * `$alarm_aktif  = ($status_penuh === 1 || $status_bau === 'Bau');`
  4. Penyimpanan record lengkap ke tabel `sensor_log`.
  5. Pengembalian respon kontrol format JSON ke ESP32.

* **Respons Berhasil (200 OK) (Server $\to$ ESP32)**:
  ```json
  {
    "status": "success",
    "message": "Data berhasil dianalisis dan dicatat",
    "data_id": 123,
    "status_penuh": 1,
    "status_bau": "Bau",
    "status_bau_code": 2,
    "actuator": {
      "alarm_active": true
    }
  }
  ```

* **Respons Galat**:
  * `401 Unauthorized`: API Key tidak valid.
  * `422 Unprocessable Entity`: Parameter `jarak_cm` atau `gas_adc` tidak lengkap.
  * `500 Internal Server Error`: Gangguan koneksi database PDO.

### 6.2 `GET /api/latest.php` (Frontend Polling Endpoint)
* **Metode**: `GET`
* **Output**: Objek JSON berisi rekaman terkini (`latest`) beserta riwayat telemetri (`history`) untuk memperbarui kartu indikator dan grafik tren dashboard.

---

## 7. Struktur Direktori Proyek

```text
smart_laundry/
├── context.md                  # Dokumentasi spesifikasi arsitektur & panduan sistem
├── config.php                  # Kredensial DB (PDO), SECRET_API_KEY, & konstanta ambang batas
├── login.php                   # Autentikasi sesi pengguna web
├── logout.php                  # Pembersihan sesi login
├── index.php                   # Dashboard monitoring real-time (HTML, CSS, Chart.js/AJAX)
├── api/
│   ├── data.php                # Endpoint kalkulasi logika, pencatatan DB, & instruksi aktuasi
│   └── latest.php              # Web service penyuplai data JSON untuk antarmuka web
├── firmware/
│   └── sketch.ino              # Kode program C++ ESP32 (akuisisi sensor mentah & aktuasi respon)
└── simulation/
    └── diagram.json            # Berkas perancangan wiring sirkuit Wokwi Simulator
```

---

## 8. Pola Aliran Kontrol Operasional

1. **Siklus Sampling ESP32**: ESP32 membaca durasi pulsa ultrasonik dan nilai analog MQ-135 secara non-blocking (`millis()`), menerapkan median filter untuk menjamin stabilitas data jarak.
2. **Pengiriman Telemetri**: ESP32 menyusun JSON ringkas berisi nilai metrik mentah dan menembakkannya via HTTPS POST ke `/api/data.php`.
3. **Analisis Terpusat**: Server PHP mengevaluasi nilai sensor terhadap konstanta batas kapasitas dan batas kualitas udara, lalu menuliskan hasil kalkulasi status ke tabel MySQL.
4. **Instruksi Balik**: Server menyematkan status boolean `actuator.alarm_active` di dalam respon HTTP 200.
5. **Eksekusi Fisik**: ESP32 membaca balasan server; jika `alarm_active: true`, ESP32 mengaktifkan pin GPIO 25 (LED) dan GPIO 26 (Buzzer), sebaliknya pin akan dimatikan jika bernilai `false`.