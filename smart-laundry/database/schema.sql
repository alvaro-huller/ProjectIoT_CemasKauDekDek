-- ============================================================================
-- Smart Laundry Basket System - Database Schema DDL
-- Database: smart_laundry
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `smart_laundry`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `smart_laundry`;

-- ----------------------------------------------------------------------------
-- 1. Table: sensor_log
-- Menyimpan riwayat telemetri jarak, kadar gas, dan status logika keranjang
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sensor_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `jarak_cm` FLOAT NOT NULL COMMENT 'Jarak pembacaan sensor HC-SR04 (cm)',
  `gas_adc` INT NOT NULL COMMENT 'Nilai analog mentah sensor MQ-135 (0 - 4095)',
  `status_penuh` TINYINT(1) NOT NULL COMMENT '1 = Penuh (jarak <= 10 cm), 0 = Normal',
  `status_bau` VARCHAR(20) NOT NULL COMMENT 'Tidak Bau (<= 2000), Normal (2000-2500), Bau (>= 2500)',
  `status_tutup` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = Tutup Tertutup, 1 = Tutup Terbuka (Deteksi Pengguna)',
  `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pencatatan rekaman di server',
  INDEX `idx_dibuat_pada` (`dibuat_pada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: users
-- Menyimpan kredensial pengguna terautentikasi untuk akses dashboard
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nama pengguna unik untuk login',
  `password` VARCHAR(255) NOT NULL COMMENT 'Hash sandi terenkripsi algoritma BCRYPT',
  `dibuat_pada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu registrasi akun',
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Seed Default Admin User
-- Username: admin
-- Password: password123 (Hash BCRYPT cost 10)
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`username`, `password`)
VALUES (
  'admin',
  '$2y$10$YtRZZ1p2tgr.yxLLQIIy/OmCnW/OubW7oWErq6L/pDKO/melRs6eu'
)
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`);
