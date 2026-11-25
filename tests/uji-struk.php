<?php
// Uji aturan struk. Jalankan: php tests/uji-struk.php  (opsional: MOCK_URL dan DB_TEST=1)
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
date_default_timezone_set('Asia/Jakarta');
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/struk.php';
$gagal = 0;
function cek($nama, $hasil, $harap) { global $gagal; $ok = $hasil === $harap; if (!$ok) $gagal++;
    echo ($ok ? 'LULUS ' : 'GAGAL ') . $nama . ($ok ? '' : ' -> dapat ' . var_export($hasil, true) . ', harap ' . var_export($harap, true)) . "\n"; }

cek('0812 jadi 62812', normalisasi_wa('081234567890'), '6281234567890');
cek('+62 jadi 62', normalisasi_wa('+6281234567890'), '6281234567890');
cek('62 tetap', normalisasi_wa('6281234567890'), '6281234567890');
cek('spasi/strip dibersihkan', normalisasi_wa('0812-3456 7890'), '6281234567890');
cek('nomor kosong ditolak', normalisasi_wa(''), null);
cek('telepon rumah ditolak', normalisasi_wa('0215551234'), null);
cek('huruf ditolak', normalisasi_wa('0812abc4567'), null);
cek('terlalu pendek ditolak', normalisasi_wa('08123'), null);
cek('nomor disamarkan', samarkan_nomor('6281234567890'), '62812*****890');

$t = ['no_transaksi' => 'TRX-1', 'tanggal' => '2026-10-22 19:05:00', 'eksp' => 'JNE', 'nama_layanan' => 'REG', 'resi_ekspedisi' => '',
      'nama_pengirim' => 'Budi', 'telp_pengirim' => '0812', 'nama_penerima' => 'Sari', 'telp_penerima' => '', 'asal' => 'Jakarta', 'tujuan' => 'Bandung',
      'berat_tagih' => '2.00', 'ongkir_dasar' => 20000, 'biaya_tambahan' => 2000, 'diskon' => 1000, 'total_tagihan' => 21000,
      'terbayar' => 15000, 'status_bayar' => 'sebagian', 'metode_bayar' => null];
$wa = format_struk_wa($t, 'Budi');
cek('sisa pembayaran 21.000 - 15.000', str_contains($wa, 'Sisa pembayaran: Rp 6.000'), true);
cek('resi kosong dijelaskan, bukan diganti no transaksi', str_contains($wa, 'No. Resi: Belum tersedia'), true);
cek('tanggal format Indonesia', str_contains($wa, 'Tanggal: 22 Oktober 2026, 19:05 WIB'), true);
cek('berat 2 kg', str_contains($wa, 'Berat: 2 kg'), true);
$tg = format_struk_telegram($t);
cek('metode kosong jadi strip', str_contains($tg, 'Metode Pembayaran: -'), true);
cek('struk di bawah batas', mb_strlen($wa) < STRUK_BATAS_KARAKTER && mb_strlen($tg) < STRUK_BATAS_KARAKTER, true);

if ($mock = getenv('MOCK_URL')) {
    putenv("FONNTE_API_URL=$mock/fonnte-ok"); putenv('FONNTE_TOKEN=tok-rahasia-123');
    putenv("TELEGRAM_API_BASE=$mock/tg"); putenv('TELEGRAM_BOT_TOKEN=123456:ABCDEFGHIJKLMNOPQRSTUVWXYZ'); putenv('TELEGRAM_CHAT_ID=987654321');
    require __DIR__ . '/../config/whatsapp.php'; require __DIR__ . '/../config/telegram.php';
    require __DIR__ . '/../includes/whatsapp.php'; require __DIR__ . '/../includes/telegram.php';
    $r = kirim_fonnte('6281234567890', 'x');
    cek('Fonnte status true = accepted', $r['status'], 'accepted');
    cek('Fonnte id disimpan', $r['provider_id'], '111');
    $tg = sendTelegramMessage(TELEGRAM_CHAT_ID, 'x');
    cek('Telegram ok = success', $tg['status'], 'success');
    file_put_contents('/tmp/mode-mock', 'tolak');
    cek('Fonnte status false = failed', kirim_fonnte('6281234567890', 'x')['status'], 'failed');
    $tg = sendTelegramMessage(TELEGRAM_CHAT_ID, 'x');
    cek('Telegram ditolak = failed', $tg['status'], 'failed');
    cek('token tidak bocor di pesan error', str_contains($tg['pesan'], 'ABCDEFGHIJ'), false);
    file_put_contents('/tmp/mode-mock', 'rusak');
    cek('respons rusak = unknown (bukan sukses)', kirim_fonnte('6281234567890', 'x')['status'], 'unknown');
    cek('tidak tersambung = failed', hasil_error_curl(7)['status'], 'failed');
    cek('timeout = unknown', hasil_error_curl(28)['status'], 'unknown');
}

if (getenv('DB_TEST')) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli('127.0.0.1', 'root', '', 'db_logistik', 3399); $db->query("SET time_zone='+07:00'");
    $db->query("DELETE FROM whatsapp_logs");
    cek('belum pernah kirim: boleh', cek_duplikat_struk($db, 'whatsapp_logs', 'pengiriman_id', 1, false), null);
    $db->query("INSERT INTO whatsapp_logs (pengiriman_id,user_id,nomor_tujuan,status) VALUES (1,1,'x','accepted')");
    cek('baru diterima: ditolak tanpa kirim ulang', cek_duplikat_struk($db, 'whatsapp_logs', 'pengiriman_id', 1, false) !== null, true);
    cek('baru diterima: boleh dengan kirim ulang', cek_duplikat_struk($db, 'whatsapp_logs', 'pengiriman_id', 1, true), null);
    $db->query("UPDATE whatsapp_logs SET status='unknown', created_at=NOW() - INTERVAL 30 MINUTE");
    cek('unknown lama: tetap butuh konfirmasi', cek_duplikat_struk($db, 'whatsapp_logs', 'pengiriman_id', 1, false) !== null, true);
    for ($i = 0; $i < 5; $i++) $db->query("INSERT INTO whatsapp_logs (pengiriman_id,user_id,nomor_tujuan,status) VALUES (1,1,'x','failed')");
    cek('lebih dari 5 per jam: ditolak walau kirim ulang', str_contains((string)cek_duplikat_struk($db, 'whatsapp_logs', 'pengiriman_id', 1, true), 'Batas 5'), true);
}
echo $gagal ? "\n$gagal uji GAGAL\n" : "\nSemua uji lulus\n";
exit($gagal ? 1 : 0);
