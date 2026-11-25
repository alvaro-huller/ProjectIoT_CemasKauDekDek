#include <WiFi.h>
    #include <HTTPClient.h>
    // #include <WiFiClientSecure.h>
    #include <ESP32Servo.h>  // Pustaka kendali motor servo pada ESP32

    // ==========================================
    // 1. DEFINISI PIN PERANGKAT KERAS
    // ==========================================
    // Sensor 1: Kapasitas Cucian Keranjang
    #define TRIG_PIN         5    // GPIO 5  -> Trig HC-SR04 Keranjang
    #define ECHO_PIN         18   // GPIO 18 -> Echo HC-SR04 Keranjang (lewat pembagi tegangan)

    // Sensor 2: Deteksi Pengguna (Smart Lid)
    #define TRIG_USER_PIN    19   // GPIO 19 -> Trig HC-SR04 Deteksi Orang
    #define ECHO_USER_PIN    21   // GPIO 21 -> Echo HC-SR04 Deteksi Orang (divider 1k/2k)

    // Sensor 3: Gas Kualitas Udara
    #define MQ135_PIN        34   // GPIO 34 -> AO MQ-135 (ADC1)

    // Aktuator Fisik
    #define LED_PIN          25   // GPIO 25 -> Anoda LED Merah (resistor 220 Ohm)
    #define BUZZER_PIN       26   // GPIO 26 -> Pin Positif Buzzer Aktif
    #define SERVO_PIN        27   // GPIO 27 -> PWM Motor Servo SG90/MG996R

    // ==========================================
    // 2. KREDENSIAL JARINGAN & SERVER LOKAL
    // ==========================================
    const char* ssid     = "Prambanan village";
    const char* password = "diandranaufal";

    // Endpoint API Server Ingestion
    //const char* serverUrl = "https://deploy-scaling-kabob.ngrok-free.dev/smart-laundry/public/api/data.php";
    const char* apiKey    = "SMART_LAUNDRY_SECRET_KEY_2026";
    const char* serverUrl = "http://192.168.0.117/smart-laundry/public/api/data.php";

    // ==========================================
    // 3. PARAMETER WAKTU & OBJEK SERVO
    // ==========================================
    Servo lidServo;
    bool isLidOpen = false;
    unsigned long lastMotionTime = 0;

    // Ambang Batas Tutup Otomatis
    const float USER_PRESENCE_THRESH = 30.0; // Jarak deteksi orang <= 30.0 cm
    const long LID_HOLD_OPEN_DELAY   = 3000; // Tahan buka 3 detik setelah orang menjauh

    // Pewaktuan Non-Blocking (millis)
    unsigned long previousUserReadTime = 0;
    const long userReadInterval = 300;  // Deteksi orang diperiksa tiap 300 ms (Real-time reflex)

    unsigned long previousReadTime = 0;
    const long readInterval = 2000;     // Akuisisi sensor keranjang tiap 2 detik

    unsigned long previousSendTime = 0;
    const long sendInterval = 4000;     // Transmisi telemetri ke server tiap 4 detik

    // Penampung Data Sensor Mentah
    float currentDistance     = 40.0;
    float currentUserDistance = 100.0;
    int currentGasADC          = 0;

    // ==========================================
    // 4. FUNGSI PENDUKUNG (HELPER)
    // ==========================================

    // Membaca jarak pantul sensor keranjang (tunggal)
    float readSingleDistance() {
      digitalWrite(TRIG_PIN, LOW);
      delayMicroseconds(2);
      digitalWrite(TRIG_PIN, HIGH);
      delayMicroseconds(10);
      digitalWrite(TRIG_PIN, LOW);

      long duration = pulseIn(ECHO_PIN, HIGH, 30000);
      if (duration == 0) {
        return 40.0;
      }
      return (duration * 0.0343) / 2.0;
    }

    // Filter Median 5 Sampel untuk Sensor Kapasitas Cucian
    float getMedianDistance() {
      float samples[5];
      for (int i = 0; i < 5; i++) {
        samples[i] = readSingleDistance();
        delay(15);
      }

      for (int i = 0; i < 4; i++) {
        for (int j = i + 1; j < 5; j++) {
          if (samples[i] > samples[j]) {
            float temp = samples[i];
            samples[i] = samples[j];
            samples[j] = temp;
          }
        }
      }
      return samples[2];
    }

    // Membaca jarak pengguna pada sensor luar (Cepat & Responsif)
    float readUserDistance() {
      digitalWrite(TRIG_USER_PIN, LOW);
      delayMicroseconds(2);
      digitalWrite(TRIG_USER_PIN, HIGH);
      delayMicroseconds(10);
      digitalWrite(TRIG_USER_PIN, LOW);

      long duration = pulseIn(ECHO_USER_PIN, HIGH, 25000);
      if (duration == 0) {
        return 999.0; // Tidak ada orang
      }
      return (duration * 0.0343) / 2.0;
    }

    // Transmisi Data Mentah & Eksekusi Perintah Aktuator dari Server
    // void sendDataToServer(float distance, int gas) {
    //   if (WiFi.status() == WL_CONNECTED) {
    //     WiFiClientSecure *client = new WiFiClientSecure;

    //     if (client) {
    //       client->setInsecure(); // Bypass validasi rantai sertifikat SSL

    //       HTTPClient https;
    //       if (https.begin(*client, serverUrl)) {
    //         https.addHeader("Content-Type", "application/json");
    //         https.addHeader("ngrok-skip-browser-warning", "69420");

    //         // Payload HANYA data mentah
    //         String payload = "{";
    //         payload += "\"api_key\":\"" + String(apiKey) + "\",";
    //         payload += "\"jarak_cm\":" + String(distance, 2) + ",";
    //         payload += "\"gas_adc\":" + String(gas);
    //         payload += "}";

    //         Serial.println("\n[HTTP] Mengirim data mentah ke server...");
    //         int httpResponseCode = https.POST(payload);

    //         if (httpResponseCode == 200) {
    //           String responseBody = https.getString();
    //           Serial.printf("[HTTP] Sukses (200). Respon Server: %s\n", responseBody.c_str());

    //           // Parsing instruksi aktuator dari server
    //           if (responseBody.indexOf("\"alarm_active\":true") != -1 ||
    //               responseBody.indexOf("\"alarm_active\": true") != -1) {
    //             digitalWrite(LED_PIN, HIGH);
    //             digitalWrite(BUZZER_PIN, HIGH);
    //             Serial.println("[AKTUATOR] Server menginstruksikan: ALARM AKTIF");
    //           } else {
    //             digitalWrite(LED_PIN, LOW);
    //             digitalWrite(BUZZER_PIN, LOW);
    //             Serial.println("[AKTUATOR] Server menginstruksikan: ALARM NONAKTIF");
    //           }
    //         } else {
    //           Serial.printf("[HTTP] Transmisi gagal. Status code: %d\n", httpResponseCode);
    //           // Fail-safe: pastikan aktuator mati jika komunikasi terputus
    //           digitalWrite(LED_PIN, LOW);
    //           digitalWrite(BUZZER_PIN, LOW);
    //         }

    //         https.end();
    //       } else {
    //         Serial.println("[HTTP] Tidak dapat terhubung ke endpoint.");
    //       }
    //       delete client;
    //     }
    //   } else {
    //     Serial.println("[WiFi] Koneksi terputus! Mencoba menghubungkan kembali...");
    //     WiFi.reconnect();
    //   }
    // }

    void sendDataToServer(float distance, int gas, bool lidOpen) {
      if (WiFi.status() == WL_CONNECTED) {
        WiFiClient client;
        HTTPClient http;

        if (http.begin(client, serverUrl)) {
          http.addHeader("Content-Type", "application/json");

          // Payload data mentah + status tutup keranjang
          String payload = "{";
          payload += "\"api_key\":\"" + String(apiKey) + "\",";
          payload += "\"jarak_cm\":" + String(distance, 2) + ",";
          payload += "\"gas_adc\":" + String(gas) + ",";
          payload += "\"lid_open\":" + String(lidOpen ? 1 : 0);
          payload += "}";

          Serial.println("\n[HTTP] Mengirim data telemetri ke server lokal...");
          int httpResponseCode = http.POST(payload);

          if (httpResponseCode == 200) {
            String responseBody = http.getString();
            Serial.printf("[HTTP] Sukses (200). Respon Server: %s\n", responseBody.c_str());

            // Parsing instruksi aktuator dari server
            if (responseBody.indexOf("\"alarm_active\":true") != -1 ||
                responseBody.indexOf("\"alarm_active\": true") != -1) {
              digitalWrite(LED_PIN, HIGH);
              digitalWrite(BUZZER_PIN, HIGH);
              Serial.println("[AKTUATOR] Server menginstruksikan: ALARM AKTIF");
            } else {
              digitalWrite(LED_PIN, LOW);
              digitalWrite(BUZZER_PIN, LOW);
              Serial.println("[AKTUATOR] Server menginstruksikan: ALARM NONAKTIF");
            }
          } else {
            Serial.printf("[HTTP] Transmisi gagal. Status code: %d\n", httpResponseCode);
            // Fail-safe: pastikan alarm mati jika komunikasi terputus
            digitalWrite(LED_PIN, LOW);
            digitalWrite(BUZZER_PIN, LOW);
          }

          http.end();
        } else {
          Serial.println("[HTTP] Tidak dapat terhubung ke endpoint server.");
        }
      } else {
        Serial.println("[WiFi] Koneksi terputus! Mencoba menghubungkan kembali...");
        WiFi.reconnect();
      }
    }

    // ==========================================
    // 5. INISIALISASI SISTEM (SETUP)
    // ==========================================
    void setup() {
      Serial.begin(115200);
      delay(1000);

      // Inisialisasi Mode Pin
      pinMode(TRIG_PIN, OUTPUT);
      pinMode(ECHO_PIN, INPUT);
      pinMode(TRIG_USER_PIN, OUTPUT);
      pinMode(ECHO_USER_PIN, INPUT);
      pinMode(MQ135_PIN, INPUT);
      pinMode(LED_PIN, OUTPUT);
      pinMode(BUZZER_PIN, OUTPUT);

      digitalWrite(TRIG_PIN, LOW);
      digitalWrite(TRIG_USER_PIN, LOW);
      digitalWrite(LED_PIN, LOW);
      digitalWrite(BUZZER_PIN, LOW);

      // Konfigurasi Motor Servo ESP32 (Smart Lid)
      ESP32PWM::allocateTimer(0);
      ESP32PWM::allocateTimer(1);
      ESP32PWM::allocateTimer(2);
      ESP32PWM::allocateTimer(3);
      lidServo.setPeriodHertz(50);
      lidServo.attach(SERVO_PIN, 500, 2400);
      lidServo.write(0); // Posisi awal tertutup (0°)
      isLidOpen = false;

      Serial.println("\n=================================");
      Serial.println(" SMART LAUNDRY - HYBRID NODE");
      Serial.println("=================================");
      Serial.printf("Menghubungkan ke Wi-Fi: %s ", ssid);

      WiFi.begin(ssid, password);
      int retryCount = 0;
      while (WiFi.status() != WL_CONNECTED && retryCount < 40) {
        delay(500);
        Serial.print(".");
        retryCount++;
      }

      if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\n[WiFi] Terhubung!");
        Serial.print("[WiFi] IP Address: ");
        Serial.println(WiFi.localIP());
      } else {
        Serial.println("\n[WiFi] Gagal terhubung ke Wi-Fi.");
      }
    }

    // ==========================================
    // 6. LOOP UTAMA (NON-BLOCKING)
    // ==========================================
    void loop() {
      unsigned long currentMillis = millis();

      // Task 0: Deteksi Orang & Otomasi Tutup Servo (Tiap 300 ms, Edge Reflex Instan)
      if (currentMillis - previousUserReadTime >= userReadInterval) {
        previousUserReadTime = currentMillis;
        currentUserDistance = readUserDistance();

        // Jika orang/tangan berada dalam jarak <= 30 cm
        if (currentUserDistance > 0 && currentUserDistance <= USER_PRESENCE_THRESH) {
          lastMotionTime = currentMillis;
          if (!isLidOpen) {
            lidServo.write(90); // Buka tutup ke 90°
            isLidOpen = true;
            Serial.printf("[SMART LID] Orang terdeteksi (%.1f cm) -> Tutup Terbuka (90°)\n", currentUserDistance);
          }
        }
      }

      // Auto-Close Delay: Tutup kembali jika sudah 3 detik tidak ada orang
      if (isLidOpen && (currentMillis - lastMotionTime >= LID_HOLD_OPEN_DELAY)) {
        lidServo.write(0); // Tutup kembali ke 0°
        isLidOpen = false;
        Serial.println("[SMART LID] Jeda 3 detik habis -> Tutup Tertutup (0°)");
      }

      // Task 1: Akuisisi Sinyal Sensor Mentah Keranjang (Tiap 2 detik)
      if (currentMillis - previousReadTime >= readInterval) {
        previousReadTime = currentMillis;

        currentDistance = getMedianDistance();
        currentGasADC   = analogRead(MQ135_PIN);

        Serial.printf("[RAW SENSOR] Jarak: %.2f cm | Gas: %d ADC | Tutup: %s\n",
                      currentDistance, currentGasADC, isLidOpen ? "Terbuka" : "Tertutup");
      }

      // Task 2: Kirim Data Mentah & Status Tutup ke Server Lokal (Tiap 4 detik)
      if (currentMillis - previousSendTime >= sendInterval) {
        previousSendTime = currentMillis;
        sendDataToServer(currentDistance, currentGasADC, isLidOpen);
      }
    } 