-- Backup dahulu. Database versi 4 menggunakan total_tagihan, bukan total_biaya.
USE db_logistik;
SET @ada_status_pengiriman = (SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pengiriman' AND COLUMN_NAME='status_pengiriman');
SET @sql_tahap5 = IF(@ada_status_pengiriman=0,
 'ALTER TABLE pengiriman ADD COLUMN status_pengiriman VARCHAR(50) GENERATED ALWAYS AS
 (CASE status
 WHEN ''dibuat'' THEN ''Manifest (Diproses di Agen)''
 WHEN ''menunggu_bayar'' THEN ''Manifest (Diproses di Agen)''
 WHEN ''menunggu_serah'' THEN ''Manifest (Diproses di Agen)''
 WHEN ''diserahkan'' THEN ''On Process (Dalam Pengiriman)''
 WHEN ''perjalanan'' THEN ''On Process (Dalam Pengiriman)''
 WHEN ''diantar'' THEN ''On Process (Dalam Pengiriman)''
 WHEN ''terkirim'' THEN ''Delivered (Terkirim)''
 WHEN ''dibatalkan'' THEN ''Dibatalkan''
 WHEN ''bermasalah'' THEN ''Bermasalah''
 ELSE ''Status belum tersedia'' END) VIRTUAL AFTER total_tagihan',
 'SELECT ''Kolom status_pengiriman sudah ada; tidak diubah. Periksa strukturnya bila dibuat manual.'' AS informasi');
PREPARE tahap5 FROM @sql_tahap5;
EXECUTE tahap5;
DEALLOCATE PREPARE tahap5;
