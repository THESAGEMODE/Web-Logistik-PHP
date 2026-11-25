<?php
// Pengiriman lewat Telegram Bot API. Token tidak pernah dicatat atau ditampilkan.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }

/** Hasil: ['status' => success|failed|unknown, 'http_code' => int, 'message_id' => ?string, 'pesan' => string] */
function sendTelegramMessage($chatId, $message) {
    if (!telegram_siap()) return ['status' => 'failed', 'http_code' => 0, 'message_id' => null, 'pesan' => 'Konfigurasi Telegram belum lengkap atau fitur dinonaktifkan.'];
    if (!function_exists('curl_init')) return ['status' => 'failed', 'http_code' => 0, 'message_id' => null, 'pesan' => 'Ekstensi cURL PHP belum aktif.'];
    $ch = curl_init(TELEGRAM_API_BASE . '/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        // Teks biasa (tanpa parse_mode), jadi karakter khusus dari data tidak merusak format
        CURLOPT_POSTFIELDS => ['chat_id' => $chatId, 'text' => $message, 'disable_web_page_preview' => 'true'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch); $errno = curl_errno($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) return hasil_error_curl($errno) + ['http_code' => 0, 'message_id' => null];
    $json = json_decode($body, true);
    if (!is_array($json)) return ['status' => 'unknown', 'http_code' => $http, 'message_id' => null, 'pesan' => 'Respons Telegram tidak dapat dibaca. Hasil belum pasti.'];
    if ($http === 200 && ($json['ok'] ?? false) === true) {
        return ['status' => 'success', 'http_code' => $http,
                'message_id' => isset($json['result']['message_id']) ? (string)$json['result']['message_id'] : null,
                'pesan' => 'Struk terkirim ke chat Telegram.'];
    }
    $alasan = str_replace(TELEGRAM_BOT_TOKEN, '[token]', (string)($json['description'] ?? 'tanpa keterangan'));
    return ['status' => 'failed', 'http_code' => $http, 'message_id' => null, 'pesan' => 'Ditolak Telegram: ' . ringkas($alasan, 150)];
}
