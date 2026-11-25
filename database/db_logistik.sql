-- LogiPro — Struktur database. Impor lewat phpMyAdmin.
CREATE DATABASE IF NOT EXISTS db_logistik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_logistik;
CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL, username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL, role ENUM('admin','staff') NOT NULL DEFAULT 'staff', aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE ekspedisi (id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL UNIQUE, kode VARCHAR(10) NOT NULL UNIQUE,
  kontak TEXT, aktif TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE layanan_ekspedisi (id INT AUTO_INCREMENT PRIMARY KEY, ekspedisi_id INT NOT NULL, nama_layanan VARCHAR(50) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY (ekspedisi_id, nama_layanan), FOREIGN KEY (ekspedisi_id) REFERENCES ekspedisi(id)) ENGINE=InnoDB;
CREATE TABLE kota (id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB;
CREATE TABLE tarif (id INT AUTO_INCREMENT PRIMARY KEY, layanan_id INT NOT NULL, kota_asal_id INT NOT NULL, kota_tujuan_id INT NOT NULL,
  tarif_per_kg DECIMAL(12,2) NOT NULL, tarif_modal_per_kg DECIMAL(12,2) NOT NULL DEFAULT 0, berat_minimum DECIMAL(8,2) NOT NULL DEFAULT 1,
  biaya_tambahan DECIMAL(12,2) NOT NULL DEFAULT 0, faktor_volumetrik INT NOT NULL DEFAULT 6000, estimasi VARCHAR(30),
  komisi_tipe ENUM('persen','nominal','selisih') NOT NULL DEFAULT 'persen', komisi_nilai DECIMAL(12,2) NOT NULL DEFAULT 0,
  berlaku_mulai DATE NOT NULL, aktif TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cari (layanan_id, kota_asal_id, kota_tujuan_id, aktif, berlaku_mulai),
  FOREIGN KEY (layanan_id) REFERENCES layanan_ekspedisi(id), FOREIGN KEY (kota_asal_id) REFERENCES kota(id), FOREIGN KEY (kota_tujuan_id) REFERENCES kota(id)) ENGINE=InnoDB;
CREATE TABLE pengiriman (id INT AUTO_INCREMENT PRIMARY KEY, no_transaksi VARCHAR(30) NOT NULL UNIQUE, tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id INT NOT NULL, layanan_id INT NOT NULL, kota_asal_id INT NOT NULL, kota_tujuan_id INT NOT NULL, resi_ekspedisi VARCHAR(50) DEFAULT '',
  nama_pengirim VARCHAR(100) NOT NULL, telp_pengirim VARCHAR(20) NOT NULL, alamat_pengirim VARCHAR(255) NOT NULL,
  nama_penerima VARCHAR(100) NOT NULL, telp_penerima VARCHAR(20) NOT NULL, alamat_penerima VARCHAR(255) NOT NULL,
  jenis_barang VARCHAR(100) NOT NULL, koli INT NOT NULL DEFAULT 1, berat_aktual DECIMAL(8,2) NOT NULL, panjang DECIMAL(8,2) DEFAULT 0, lebar DECIMAL(8,2) DEFAULT 0, tinggi DECIMAL(8,2) DEFAULT 0,
  berat_volumetrik DECIMAL(8,2) DEFAULT 0, berat_tagih DECIMAL(8,2) NOT NULL, ongkir_dasar DECIMAL(12,2) NOT NULL, biaya_tambahan DECIMAL(12,2) NOT NULL DEFAULT 0,
  diskon DECIMAL(12,2) NOT NULL DEFAULT 0, total_tagihan DECIMAL(12,2) NOT NULL, komisi DECIMAL(12,2) NOT NULL DEFAULT 0, catatan VARCHAR(255),
  tarif_snapshot JSON, status VARCHAR(20) NOT NULL, status_bayar ENUM('belum','sebagian','lunas') NOT NULL DEFAULT 'belum',
  INDEX (resi_ekspedisi), INDEX (tanggal), INDEX (status),
  FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN KEY (layanan_id) REFERENCES layanan_ekspedisi(id),
  FOREIGN KEY (kota_asal_id) REFERENCES kota(id), FOREIGN KEY (kota_tujuan_id) REFERENCES kota(id)) ENGINE=InnoDB;
CREATE TABLE detail_pengiriman (id INT AUTO_INCREMENT PRIMARY KEY, pengiriman_id INT NOT NULL, keterangan VARCHAR(100), berat DECIMAL(8,2),
  FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id)) ENGINE=InnoDB;
CREATE TABLE pembayaran (id INT AUTO_INCREMENT PRIMARY KEY, pengiriman_id INT NOT NULL, jumlah DECIMAL(12,2) NOT NULL, metode ENUM('tunai','transfer','qris') NOT NULL,
  no_referensi VARCHAR(50), tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, user_id INT NOT NULL,
  FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id), FOREIGN KEY (user_id) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE riwayat_status (id INT AUTO_INCREMENT PRIMARY KEY, pengiriman_id INT NOT NULL, status VARCHAR(20) NOT NULL, keterangan VARCHAR(255),
  user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (pengiriman_id),
  FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id), FOREIGN KEY (user_id) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE pengeluaran (id INT AUTO_INCREMENT PRIMARY KEY, tanggal DATE NOT NULL, kategori VARCHAR(50) NOT NULL, deskripsi VARCHAR(255),
  nominal DECIMAL(12,2) NOT NULL, metode VARCHAR(20), no_referensi VARCHAR(50), user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE pengaturan (kunci VARCHAR(50) PRIMARY KEY, nilai TEXT) ENGINE=InnoDB;
CREATE TABLE audit_log (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, aksi VARCHAR(50) NOT NULL, detail TEXT, ip VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (created_at)) ENGINE=InnoDB;
