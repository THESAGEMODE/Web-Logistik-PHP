<?php
// Konfigurasi Telegram Bot. JANGAN tulis token asli di file ini.
// Isi di config/rahasia.php (lihat rahasia.contoh.php) atau environment variable.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }
if (is_file(__DIR__ . '/rahasia.php')) require_once __DIR__ . '/rahasia.php';

if (!defined('TELEGRAM_BOT_TOKEN')) define('TELEGRAM_BOT_TOKEN', (string)(getenv('TELEGRAM_BOT_TOKEN') ?: 'MASUKKAN_TOKEN_BOT_DI_SINI'));
if (!defined('TELEGRAM_CHAT_ID')) define('TELEGRAM_CHAT_ID', (string)(getenv('TELEGRAM_CHAT_ID') ?: 'MASUKKAN_CHAT_ID_DI_SINI'));
if (!defined('TELEGRAM_ENABLED')) define('TELEGRAM_ENABLED', true);
define('TELEGRAM_API_BASE', (string)(getenv('TELEGRAM_API_BASE') ?: 'https://api.telegram.org'));

function telegram_siap() {
    return TELEGRAM_ENABLED
        && preg_match('/^\d+:[A-Za-z0-9_-]{20,}$/', TELEGRAM_BOT_TOKEN)
        && preg_match('/^(-?\d{1,20}|@[A-Za-z0-9_]{5,})$/', TELEGRAM_CHAT_ID);
}
