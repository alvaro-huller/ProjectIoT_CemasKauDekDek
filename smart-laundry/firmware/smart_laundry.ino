/**
 * ============================================================================
 * Proyek IoT: Smart Laundry Basket System
 * Berkas: firmware/smart_laundry.ino
 * Target Hardware: ESP32-WROOM-32 (DevKit v1 / DevKit C v4)
 * Simulator: Wokwi Simulator
 * Arsitektur: Hybrid (Edge Real-Time Reflex untuk Tutup + Server Centralized Logic)
 * ============================================================================
 * Deskripsi:
 * Firmware ESP32 dengan arsitektur non-blocking (millis) dan pola hybrid:
 * 1. Logika Lokal Instan (Edge Real-Time Reflex):
 *    - Sensor HC-SR04 ke-2 (User Presence / Lid Sensor) mendeteksi tangan/orang (<= 30.0 cm).
 *    - Menggerakkan Servo Motor SG90/MG996R ke sudut 90° (Terbuka) seketika tanpa latency HTTP.
 *    - Auto-Close Delay: Mempertahankan posisi terbuka selama 3000 ms setelah pengguna menjauh,
 *      lalu mengembalikan servo ke 0° (Tertutup).
 * 2. Logika Terpusat Sisi Server (Thin Client IoT):
 *    - Sensor HC-SR04 ke-1 (Kapasitas Dalam) dan Sensor Gas MQ-135 dikirim sebagai data mentah.
 *    - Evaluasi kepenuhan dan kebauan gas serta pemicuan alarm (LED GPIO 25 & Buzzer GPIO 26)
 *      dikelola terpusat oleh server PHP.
 *    - Status penutup keranjang ("lid_open": 1/0) dilaporkan ke server sebagai telemetri.
 * ============================================================================
 */

#include <WiFi.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ESP32Servo.h>

// ----------------------------------------------------------------------------
// 1. Konfigurasi Jaringan Wi-Fi & Endpoint Backend
// ----------------------------------------------------------------------------
// Untuk pengujian di Simulator Wokwi gunakan SSID "Wokwi-GUEST" tanpa sandi.
// Untuk implementasi fisik, ganti dengan kredensial router/hotspot 2.4 GHz Anda.
const char* wifiSsid     = "Wokwi-GUEST";
const char* wifiPassword = "";

// URL endpoint backend PHP (Server Ingestion & Centralized Logic)
// Contoh Tunnel: "https://smart-laundry.loca.lt/api/data.php" atau "https://xxxx.ngrok-free.app/api/data.php"
// Contoh Localhost/Wokwi Gateway: "http://10.0.2.2/smart-laundry/public/api/data.php"
const char* serverUrl    = "https://smart-laundry.loca.lt/api/data.php";

// Kunci API rahasia (Wajib cocok dengan SECRET_API_KEY di public/config.php)
const char* apiKey       = "SMART_LAUNDRY_SECRET_KEY_2026";

// ----------------------------------------------------------------------------
// 2. Pemetaan Pin Perangkat Keras
// ----------------------------------------------------------------------------
// Sensor 1: Kapasitas Pakaian Internal
const uint8_t PIN_TRIG_BASKET = 5;   // Output ESP32 pulsa pemicu HC-SR04 keranjang (10 us)
const uint8_t PIN_ECHO_BASKET = 18;  // Input ESP32 sinyal pantul HC-SR04 keranjang

// Sensor 2: Deteksi Keberadaan Pengguna (Smart Lid Automation)
const uint8_t PIN_TRIG_USER   = 19;  // Output ESP32 pulsa pemicu HC-SR04 deteksi orang (10 us)
const uint8_t PIN_ECHO_USER   = 21;  // Input ESP32 sinyal pantul HC-SR04 deteksi orang (divider 1k/2k)

// Sensor 3: Kualitas Udara & Gas Bau
const uint8_t PIN_MQ135       = 34;  // Input Analog ADC1 Channel 6 (Aman saat Wi-Fi aktif)

// Aktuator Fisik: Alarm & Indikator
const uint8_t PIN_LED         = 25;  // Output digital kendali LED Merah (seri 220 Ohm)
const uint8_t PIN_BUZZER      = 26;  // Output digital kendali Active Buzzer 5V

// Aktuator Fisik: Motor Servo Tutup Keranjang
const uint8_t PIN_SERVO       = 13;  // Output PWM kendali Servo Motor Tutup Keranjang

// ----------------------------------------------------------------------------
// 3. Parameter Servo & Edge Reflex Smart Lid
// ----------------------------------------------------------------------------
const int SERVO_ANGLE_CLOSED        = 0;     // Sudut Tutup Tertutup (0°)
const int SERVO_ANGLE_OPEN          = 90;    // Sudut Tutup Terbuka (90°)
const float USER_PRESENCE_THRESH_CM = 30.0f; // Jarak deteksi tangan/pengguna (<= 30.0 cm)
const unsigned long LID_HOLD_OPEN_DELAY_MS = 3000; // Tahan terbuka 3 detik setelah pengguna menjauh

Servo lidServo;
bool isLidOpen = false;
unsigned long lastMotionTime = 0;

// ----------------------------------------------------------------------------
// 4. Parameter Filter & Pewaktuan Non-blocking (Thin Client Telemetry)
// ----------------------------------------------------------------------------
const int MEDIAN_SAMPLES            = 5;     // Jumlah sampel median filter ultrasonik keranjang
const unsigned long SAMPLE_DELAY_MS = 10;    // Jeda antar-sampel pulsa ultrasonik (ms)

// Interval Tugas Non-blocking
const unsigned long USER_SENSOR_INTERVAL_MS = 300;  // Sampling deteksi orang tiap 300 ms (respons cepat)
const unsigned long SENSOR_INTERVAL_MS      = 2000; // Sampling data kapasitas & bau tiap 2 detik
const unsigned long TRANSMIT_INTERVAL_MS    = 5000; // Transmisi telemetri & sync server tiap 5 detik

// Variabel Pewaktuan Non-blocking
unsigned long lastUserSensorReadTime = 0;
unsigned long lastSensorReadTime     = 0;
unsigned long lastTransmitTime       = 0;

// Penyimpanan Data Telemetri Mentah (Raw Telemetry)
float currentDistanceCm     = 40.0f;
float currentUserDistanceCm = 100.0f;
int   currentGasAdc         = 0;

// Status Aktuator Pasif (Diperbarui berdasarkan balasan server)
bool currentAlarmState      = false;

// ----------------------------------------------------------------------------
// 5. Prototipe Fungsi
// ----------------------------------------------------------------------------
void connectToWiFi();
float readBasketUltrasonicMedian();
float readUserUltrasonicDistance();
void updateSmartLidAutomation(unsigned long currentMillis);
void sendRawTelemetryAndSyncActuators();
bool parseActuatorAlarmActive(const String& response);
int  parseJsonIntField(const String& response, const char* fieldName);
String parseJsonStringField(const String& response, const char* fieldName);
void applyActuatorCommand(bool alarmActive);

// ----------------------------------------------------------------------------
// 6. Inisialisasi Sistem (setup)
// ----------------------------------------------------------------------------
void setup() {
  Serial.begin(115200);
  delay(500);

  Serial.println(F("\n======================================================="));
  Serial.println(F("  SMART LAUNDRY BASKET - ESP32 HYBRID EDGE & THIN CLIENT"));
  Serial.println(F("  Fitur: Smart Lid Automation & Server Centralized Logic"));
  Serial.println(F("======================================================="));

  // Konfigurasi Mode Pin I/O
  pinMode(PIN_TRIG_BASKET, OUTPUT);
  pinMode(PIN_ECHO_BASKET, INPUT);
  pinMode(PIN_TRIG_USER, OUTPUT);
  pinMode(PIN_ECHO_USER, INPUT);
  pinMode(PIN_MQ135, INPUT);
  pinMode(PIN_LED, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);

  // Pastikan status awal pin output aman (LOW)
  digitalWrite(PIN_TRIG_BASKET, LOW);
  digitalWrite(PIN_TRIG_USER, LOW);
  digitalWrite(PIN_LED, LOW);
  digitalWrite(PIN_BUZZER, LOW);

  // Konfigurasi dan Alokasi Timer PWM Motor Servo ESP32
  ESP32PWM::allocateTimer(0);
  ESP32PWM::allocateTimer(1);
  ESP32PWM::allocateTimer(2);
  ESP32PWM::allocateTimer(3);
  lidServo.setPeriodHertz(50);             // Standar 50Hz PWM Servo
  lidServo.attach(PIN_SERVO, 500, 2400);   // Pulse width standar SG90/MG996R (500us - 2400us)
  lidServo.write(SERVO_ANGLE_CLOSED);      // Tutup awal dalam posisi tertutup (0°)
  isLidOpen = false;

  // Hubungkan ke Jaringan Wi-Fi
  connectToWiFi();

  // Pengujian Awal Aktuator (Self-Test Blink & Beep Singkat)
  digitalWrite(PIN_LED, HIGH);
  digitalWrite(PIN_BUZZER, HIGH);
  delay(150);
  digitalWrite(PIN_LED, LOW);
  digitalWrite(PIN_BUZZER, LOW);

  Serial.println(F("[SYSTEM] Inisialisasi selesai. Node siap menjalankan Edge Reflex & Telemetri.\n"));
}

// ----------------------------------------------------------------------------
// 7. Loop Utama (Non-blocking Hybrid Reflex)
// ----------------------------------------------------------------------------
void loop() {
  unsigned long currentMillis = millis();

  // Task 1: Logika Otomatisasi Tutup Keranjang (Edge Real-Time Reflex, tiap 300 ms)
  updateSmartLidAutomation(currentMillis);

  // Task 2: Pembacaan Data Mentah Sensor Kapasitas & Bau (Tiap 2 Detik)
  if (currentMillis - lastSensorReadTime >= SENSOR_INTERVAL_MS) {
    lastSensorReadTime = currentMillis;

    // Baca sensor ultrasonik keranjang menggunakan median filter 5-sampel
    currentDistanceCm = readBasketUltrasonicMedian();

    // Baca sensor gas MQ-135 (ADC 12-bit: 0 - 4095)
    currentGasAdc = analogRead(PIN_MQ135);

    Serial.printf(
      "[RAW SENSOR] Jarak Cucian: %.2f cm | MQ-135 ADC: %d | Status Tutup: %s\n",
      currentDistanceCm,
      currentGasAdc,
      isLidOpen ? "TERBUKA (90°)" : "TERTUTUP (0°)"
    );
  }

  // Task 3: Transmisi Telemetri Mentah ke Backend & Sinkronisasi Aktuator (Tiap 5 Detik)
  if (currentMillis - lastTransmitTime >= TRANSMIT_INTERVAL_MS) {
    lastTransmitTime = currentMillis;
    sendRawTelemetryAndSyncActuators();
  }

  // Pemeriksaan & Pemulihan Koneksi Wi-Fi Otomatis jika Terputus
  if (WiFi.status() != WL_CONNECTED && (currentMillis % 10000 < 50)) {
    Serial.println(F("[WIFI] Koneksi terputus. Mencoba menghubungkan kembali..."));
    WiFi.reconnect();
  }
}

// ----------------------------------------------------------------------------
// 8. Logika Edge Reflex: Pembacaan Deteksi Pengguna & Kendali Motor Servo
// ----------------------------------------------------------------------------
float readUserUltrasonicDistance() {
  digitalWrite(PIN_TRIG_USER, LOW);
  delayMicroseconds(2);
  digitalWrite(PIN_TRIG_USER, HIGH);
  delayMicroseconds(10);
  digitalWrite(PIN_TRIG_USER, LOW);

  // Timeout 25 ms (~ 4 meter)
  unsigned long duration = pulseIn(PIN_ECHO_USER, HIGH, 25000);
  if (duration == 0) {
    return 999.0f; // Tidak ada pantulan / objek terlalu jauh
  }
  return (duration * 0.0343f) / 2.0f;
}

void updateSmartLidAutomation(unsigned long currentMillis) {
  // Sampling jarak deteksi orang setiap 300 ms
  if (currentMillis - lastUserSensorReadTime >= USER_SENSOR_INTERVAL_MS) {
    lastUserSensorReadTime = currentMillis;
    currentUserDistanceCm = readUserUltrasonicDistance();

    // Kondisi 1: Pengguna mendekat (Jarak valid > 0 dan <= 30.0 cm)
    if (currentUserDistanceCm > 0.0f && currentUserDistanceCm <= USER_PRESENCE_THRESH_CM) {
      lastMotionTime = currentMillis; // Catat waktu terdeteksi

      if (!isLidOpen) {
        lidServo.write(SERVO_ANGLE_OPEN);
        isLidOpen = true;
        Serial.printf(
          "[LID-AUTO] Deteksi Pengguna: %.1f cm -> Tutup DIBUKA (Sudut: %d°)\n",
          currentUserDistanceCm,
          SERVO_ANGLE_OPEN
        );
      }
    }
  }

  // Kondisi 2: Auto-Close Delay (Tahan terbuka selama 3000 ms setelah deteksi terakhir)
  if (isLidOpen) {
    if (currentMillis - lastMotionTime >= LID_HOLD_OPEN_DELAY_MS) {
      lidServo.write(SERVO_ANGLE_CLOSED);
      isLidOpen = false;
      Serial.printf(
        "[LID-AUTO] Pengguna menjauh & jeda %lu ms terpenuhi -> Tutup DITUTUP (Sudut: %d°)\n",
        LID_HOLD_OPEN_DELAY_MS,
        SERVO_ANGLE_CLOSED
      );
    }
  }
}

// ----------------------------------------------------------------------------
// 9. Implementasi: Median Filter Ultrasonik HC-SR04 Keranjang Pakaian
// ----------------------------------------------------------------------------
float readBasketUltrasonicMedian() {
  float samples[MEDIAN_SAMPLES];

  for (int i = 0; i < MEDIAN_SAMPLES; i++) {
    digitalWrite(PIN_TRIG_BASKET, LOW);
    delayMicroseconds(2);
    digitalWrite(PIN_TRIG_BASKET, HIGH);
    delayMicroseconds(10);
    digitalWrite(PIN_TRIG_BASKET, LOW);

    unsigned long duration = pulseIn(PIN_ECHO_BASKET, HIGH, 25000);

    if (duration == 0) {
      samples[i] = 40.0f;
    } else {
      samples[i] = (duration * 0.0343f) / 2.0f;
    }

    delay(SAMPLE_DELAY_MS);
  }

  // Insertion Sort
  for (int i = 1; i < MEDIAN_SAMPLES; i++) {
    float key = samples[i];
    int j = i - 1;
    while (j >= 0 && samples[j] > key) {
      samples[j + 1] = samples[j];
      j = j - 1;
    }
    samples[j + 1] = key;
  }

  return samples[MEDIAN_SAMPLES / 2];
}

// ----------------------------------------------------------------------------
// 10. Transmisi Data Mentah & Sinkronisasi Respons Server
// ----------------------------------------------------------------------------
void sendRawTelemetryAndSyncActuators() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println(F("[TRANSMIT] Wi-Fi belum terhubung. Menunda transmisi data."));
    return;
  }

  // Format Payload JSON: Data mentah kapasitas & bau + status buka/tutup servo
  char jsonPayload[256];
  snprintf(
    jsonPayload,
    sizeof(jsonPayload),
    "{\"api_key\":\"%s\",\"jarak_cm\":%.2f,\"gas_adc\":%d,\"lid_open\":%d}",
    apiKey,
    currentDistanceCm,
    currentGasAdc,
    isLidOpen ? 1 : 0
  );

  Serial.println(F("[TRANSMIT] Mengirim payload telemetri ke server..."));
  Serial.printf("[TRANSMIT] Endpoint: %s\n", serverUrl);
  Serial.printf("[TRANSMIT] Payload : %s\n", jsonPayload);

  HTTPClient http;
  bool isHttps = (strncmp(serverUrl, "https://", 8) == 0);

  if (isHttps) {
    WiFiClientSecure secureClient;
    secureClient.setInsecure();
    if (!http.begin(secureClient, serverUrl)) {
      Serial.println(F("[TRANSMIT] Gagal inisialisasi sesi HTTPS"));
      return;
    }
  } else {
    WiFiClient plainClient;
    if (!http.begin(plainClient, serverUrl)) {
      Serial.println(F("[TRANSMIT] Gagal inisialisasi sesi HTTP"));
      return;
    }
  }

  // Set HTTP Headers
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", apiKey);
  http.addHeader("ngrok-skip-browser-warning", "true");

  int httpResponseCode = http.POST((uint8_t*)jsonPayload, strlen(jsonPayload));

  if (httpResponseCode == HTTP_CODE_OK) {
    String responseText = http.getString();
    Serial.printf("[TRANSMIT] Respons Server [%d]: %s\n", httpResponseCode, responseText.c_str());

    // 1. Ekstraksi Keputusan Aktuator dari Logika Terpusat Server
    bool serverAlarmActive = parseActuatorAlarmActive(responseText);
    int  serverStatusPenuh = parseJsonIntField(responseText, "\"status_penuh\"");
    String serverStatusBau = parseJsonStringField(responseText, "\"status_bau\"");

    // 2. Eksekusi Aktuator Alarm Pasif (LED & Buzzer)
    applyActuatorCommand(serverAlarmActive);

    Serial.printf(
      "[CENTRAL-LOGIC] Evaluasi Server -> Penuh: %d | Bau: %s | Alarm Aktif: %s\n",
      serverStatusPenuh,
      serverStatusBau.c_str(),
      serverAlarmActive ? "AKTIF [LED+BUZZER MENYALA]" : "STANDBY [PADAM]"
    );
  } else {
    Serial.printf(
      "[TRANSMIT] HTTP POST Gagal. Kode respons: %d (%s)\n",
      httpResponseCode,
      http.errorToString(httpResponseCode).c_str()
    );
  }

  http.end();
}

// ----------------------------------------------------------------------------
// 11. Ekstraktor Parsing JSON Sederhana
// ----------------------------------------------------------------------------
bool parseActuatorAlarmActive(const String& response) {
  int keyIndex = response.indexOf("\"alarm_active\"");
  if (keyIndex == -1) {
    return false;
  }

  int colonIndex = response.indexOf(':', keyIndex);
  if (colonIndex == -1) {
    return false;
  }

  int searchEnd = colonIndex + 12;
  if (searchEnd > (int)response.length()) {
    searchEnd = response.length();
  }

  String valPart = response.substring(colonIndex + 1, searchEnd);
  valPart.trim();
  valPart.toLowerCase();

  return (valPart.startsWith("true") || valPart.startsWith("1"));
}

int parseJsonIntField(const String& response, const char* fieldName) {
  int keyIndex = response.indexOf(fieldName);
  if (keyIndex == -1) return -1;

  int colonIndex = response.indexOf(':', keyIndex);
  if (colonIndex == -1) return -1;

  int commaIndex = response.indexOf(',', colonIndex);
  int braceIndex = response.indexOf('}', colonIndex);
  int endIndex = response.length();

  if (commaIndex != -1 && commaIndex < endIndex) endIndex = commaIndex;
  if (braceIndex != -1 && braceIndex < endIndex) endIndex = braceIndex;

  String val = response.substring(colonIndex + 1, endIndex);
  val.trim();
  return val.toInt();
}

String parseJsonStringField(const String& response, const char* fieldName) {
  int keyIndex = response.indexOf(fieldName);
  if (keyIndex == -1) return "";

  int colonIndex = response.indexOf(':', keyIndex);
  if (colonIndex == -1) return "";

  int startQuote = response.indexOf('"', colonIndex);
  if (startQuote == -1) return "";

  int endQuote = response.indexOf('"', startQuote + 1);
  if (endQuote == -1) return "";

  return response.substring(startQuote + 1, endQuote);
}

// ----------------------------------------------------------------------------
// 12. Eksekutor Aktuator Alarm Pasif (Passive Actuator Executor)
// ----------------------------------------------------------------------------
void applyActuatorCommand(bool alarmActive) {
  currentAlarmState = alarmActive;

  if (currentAlarmState) {
    digitalWrite(PIN_LED, HIGH);
    digitalWrite(PIN_BUZZER, HIGH);
  } else {
    digitalWrite(PIN_LED, LOW);
    digitalWrite(PIN_BUZZER, LOW);
  }
}

// ----------------------------------------------------------------------------
// 13. Implementasi: Koneksi Wi-Fi Station
// ----------------------------------------------------------------------------
void connectToWiFi() {
  Serial.printf("[WIFI] Menghubungkan ke SSID: %s ", wifiSsid);
  WiFi.mode(WIFI_STA);
  WiFi.begin(wifiSsid, wifiPassword);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(F("."));
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println(F("\n[WIFI] Berhasil terhubung!"));
    Serial.printf("[WIFI] Alamat IP ESP32: %s\n", WiFi.localIP().toString().c_str());
    Serial.printf("[WIFI] Kekuatan Sinyal (RSSI): %d dBm\n", WiFi.RSSI());
  } else {
    Serial.println(F("\n[WIFI] Peringatan: Belum berhasil terhubung saat startup. Reconnect otomatis diaktifkan."));
  }
}
