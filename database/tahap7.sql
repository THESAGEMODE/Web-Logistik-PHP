-- Jalankan satu kali pada db_logistik yang sudah dipakai. Tidak mengubah transaksi.
CREATE TABLE IF NOT EXISTS ai_keluhan (
 id INT AUTO_INCREMENT PRIMARY KEY,
 request_id CHAR(36) NOT NULL UNIQUE,
 pengiriman_id INT NOT NULL,
 user_id INT NOT NULL,
 question TEXT NOT NULL,
 snapshot JSON NOT NULL,
 recommendation JSON NULL,
 status ENUM('processing','completed','failed','unknown') NOT NULL DEFAULT 'processing',
 error_message VARCHAR(500) NOT NULL DEFAULT '',
 model VARCHAR(100) NOT NULL DEFAULT '',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX (pengiriman_id,created_at), INDEX (user_id,created_at),
 FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id),
 FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
