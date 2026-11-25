-- DATA DUMMY untuk pengujian lokal. Impor SETELAH db_logistik.sql.
USE db_logistik;
INSERT INTO ekspedisi (nama,kode,kontak) VALUES ('JNE (DUMMY)','JNE','Data contoh'),('J&T Express (DUMMY)','JNT','Data contoh'),('SiCepat (DUMMY)','SCP','Data contoh');
INSERT INTO layanan_ekspedisi (ekspedisi_id,nama_layanan) VALUES (1,'REG'),(1,'YES'),(2,'EZ'),(3,'BEST');
INSERT INTO kota (nama) VALUES ('Jakarta'),('Bandung'),('Surabaya'),('Medan');
INSERT INTO tarif (layanan_id,kota_asal_id,kota_tujuan_id,tarif_per_kg,tarif_modal_per_kg,berat_minimum,biaya_tambahan,estimasi,komisi_tipe,komisi_nilai,berlaku_mulai) VALUES
 (1,1,2,10000,8500,1,0,'2-3 hari','persen',10,'2024-01-01'),(2,1,2,18000,15000,1,0,'1 hari','persen',10,'2024-01-01'),
 (1,1,3,15000,12500,1,2000,'3-4 hari','persen',10,'2024-01-01'),(3,1,3,14000,0,1,0,'3-5 hari','nominal',1500,'2024-01-01'),
 (4,1,4,30000,26000,1,5000,'4-6 hari','selisih',0,'2024-01-01');
INSERT INTO pengaturan VALUES ('nama_usaha','LogiPro Agen Cargo (DUMMY)');
