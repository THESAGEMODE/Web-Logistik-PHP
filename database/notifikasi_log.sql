-- LogiPro Tahap 4: riwayat struk WhatsApp dan Telegram.
-- Impor lewat phpMyAdmin SETELAH db_logistik.sql. Aman diimpor ulang.
USE db_logistik;

CREATE TABLE IF NOT EXISTS whatsapp_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  pengiriman_id INT NOT NULL,
  user_id INT NOT NULL,
  nomor_tujuan VARCHAR(20) NOT NULL,          -- tersamar, contoh 62812*****890
  pihak ENUM('pengirim','penerima') NOT NULL DEFAULT 'pengirim',
  jenis_pesan VARCHAR(30) NOT NULL DEFAULT 'struk',
  status ENUM('processing','accepted','failed','unknown') NOT NULL DEFAULT 'processing',
  http_code SMALLINT NULL,
  provider_message_id VARCHAR(100) NULL,
  response_summary VARCHAR(255) NULL,         -- ringkasan aman, tanpa token dan tanpa isi pesan
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_wa_transaksi (pengiriman_id, created_at),
  FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS telegram_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,                -- = pengiriman.id
  chat_id VARCHAR(30) NOT NULL,               -- tersamar
  status ENUM('processing','success','failed','unknown') NOT NULL DEFAULT 'processing',
  http_code SMALLINT NULL,
  telegram_message_id VARCHAR(30) NULL,
  response_message VARCHAR(255) NULL,         -- pesan error yang sudah disanitasi
  sent_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tg_transaksi (transaction_id, created_at),
  FOREIGN KEY (transaction_id) REFERENCES pengiriman(id),
  FOREIGN KEY (sent_by) REFERENCES users(id)
) ENGINE=InnoDB;
