<?php
// SALIN file ini menjadi config/rahasia.php lalu isi nilainya.
// Folder config dilindungi .htaccess. Jangan bagikan atau unggah rahasia.php ke GitHub.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }

define('FONNTE_TOKEN', '');        // Token perangkat dari dashboard Fonnte
define('TELEGRAM_BOT_TOKEN', '');  // Token dari @BotFather
define('TELEGRAM_CHAT_ID', '');    // Chat ID tujuan, misal 123456789 atau -100xxxxxxxxxx
define('TELEGRAM_ENABLED', true);

// URL aplikasi Lovable yang sudah dipublikasikan + path di bawah.
define('LOGIPRO_AI_RELAY_URL', ''); // https://domain-anda/api/public/complaint-recommendation
define('LOGIPRO_AI_RELAY_SECRET', ''); // Kunci sambungan yang sama dengan formulir rahasia Cloud; minimal 32 karakter
