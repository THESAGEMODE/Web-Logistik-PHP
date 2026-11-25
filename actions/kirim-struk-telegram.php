<?php require '../config/app.php'; require_login();
require '../config/telegram.php'; require '../includes/struk.php'; require '../includes/telegram.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../transaksi.php');
csrf_check();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$kirimUlang = ($_POST['kirim_ulang'] ?? '') === '1';
$uid = (int)$_SESSION['user']['id'];
if (!$id) { flash('err', 'Permintaan tidak valid.'); redirect('../transaksi.php'); }

$back = '../transaksi.php';
try {
    $db->begin_transaction();
    $st = $db->prepare('SELECT id, no_transaksi FROM pengiriman WHERE id=? FOR UPDATE');
    $st->bind_param('i', $id); $st->execute();
    $kunci = $st->get_result()->fetch_assoc();
    if (!$kunci) throw new RuntimeException('Transaksi tidak ditemukan.');
    $back = '../detail-transaksi.php?no=' . urlencode($kunci['no_transaksi']);
    if (!telegram_siap()) throw new RuntimeException('Konfigurasi Telegram belum lengkap atau dinonaktifkan. Hubungi admin.');

    $pesan = format_struk_telegram(ambil_transaksi_id($db, $id));
    if (mb_strlen($pesan) > STRUK_BATAS_KARAKTER) throw new RuntimeException('Isi struk terlalu panjang untuk Telegram.');
    if ($tolak = cek_duplikat_struk($db, 'telegram_logs', 'transaction_id', $id, $kirimUlang)) throw new RuntimeException($tolak);

    // Chat ID SELALU dari konfigurasi, tidak pernah dari formulir
    $chatSamar = samarkan_nomor(TELEGRAM_CHAT_ID);
    $st = $db->prepare("INSERT INTO telegram_logs (transaction_id, chat_id, status, sent_by) VALUES (?,?,'processing',?)");
    $st->bind_param('isi', $id, $chatSamar, $uid); $st->execute();
    $logId = $db->insert_id;
    $db->commit();
} catch (RuntimeException $e) {
    $db->rollback(); flash('err', $e->getMessage()); redirect($back);
} catch (Throwable $e) {
    $db->rollback(); error_log('[kirim-struk-telegram] ' . $e->getMessage());
    flash('err', 'Gagal menyiapkan pengiriman. Pastikan database/notifikasi_log.sql sudah diimpor.'); redirect($back);
}

$hasil = sendTelegramMessage(TELEGRAM_CHAT_ID, $pesan);

try {
    $ringkasan = ringkas($hasil['pesan']);
    $st = $db->prepare('UPDATE telegram_logs SET status=?, http_code=?, telegram_message_id=?, response_message=? WHERE id=?');
    $st->bind_param('sissi', $hasil['status'], $hasil['http_code'], $hasil['message_id'], $ringkasan, $logId); $st->execute();
    audit($db, 'kirim_telegram', "pengiriman_id=$id log=$logId status={$hasil['status']}" . ($kirimUlang ? ' ulang' : ''));
} catch (Throwable $e) {
    error_log('[kirim-struk-telegram] gagal menyimpan log: ' . $e->getMessage());
    flash('warn', 'Hasil: ' . $hasil['pesan'] . ' Namun riwayat gagal disimpan.'); redirect($back);
}

match ($hasil['status']) {
    'success' => flash('ok', 'Struk terkirim ke chat Telegram.'),
    'unknown' => flash('warn', $hasil['pesan'] . ' Periksa chat Telegram sebelum kirim ulang.'),
    default => flash('err', 'Struk gagal dikirim ke Telegram. ' . $hasil['pesan']),
};
redirect($back);
