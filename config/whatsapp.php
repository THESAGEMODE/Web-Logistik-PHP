<?php
// Konfigurasi Fonnte. JANGAN tulis token asli di file ini.
// Isi token di config/rahasia.php (lihat rahasia.contoh.php) atau environment variable FONNTE_TOKEN.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }
if (is_file(__DIR__ . '/rahasia.php')) require_once __DIR__ . '/rahasia.php';

if (!defined('FONNTE_TOKEN')) define('FONNTE_TOKEN', (string)(getenv('FONNTE_TOKEN') ?: ''));
define('FONNTE_API_URL', (string)(getenv('FONNTE_API_URL') ?: 'https://api.fonnte.com/send'));
define('FONNTE_CONNECT_TIMEOUT', 5);
define('FONNTE_TIMEOUT', 20);

function whatsapp_siap() { return trim(FONNTE_TOKEN) !== '' && !str_contains(FONNTE_TOKEN, 'MASUKKAN'); }
