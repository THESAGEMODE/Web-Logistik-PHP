<?php
// Pengiriman lewat Fonnte. Token tidak pernah dicatat atau ditampilkan.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) { http_response_code(403); exit; }

/** Hasil: ['status' => accepted|failed|unknown, 'http_code' => int, 'provider_id' => ?string, 'pesan' => string] */
function kirim_fonnte($nomor, $pesan) {
    if (!whatsapp_siap()) return ['status' => 'failed', 'http_code' => 0, 'provider_id' => null, 'pesan' => 'Token WhatsApp (Fonnte) belum diatur.'];
    if (!function_exists('curl_init')) return ['status' => 'failed', 'http_code' => 0, 'provider_id' => null, 'pesan' => 'Ekstensi cURL PHP belum aktif.'];
    $ch = curl_init(FONNTE_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['target' => $nomor, 'message' => $pesan, 'countryCode' => '62'],
        CURLOPT_HTTPHEADER => ['Authorization: ' . FONNTE_TOKEN],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => FONNTE_CONNECT_TIMEOUT,
        CURLOPT_TIMEOUT => FONNTE_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch); $errno = curl_errno($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) return hasil_error_curl($errno) + ['http_code' => 0, 'provider_id' => null];
    $json = json_decode($body, true);
    if (!is_array($json)) return ['status' => 'unknown', 'http_code' => $http, 'provider_id' => null, 'pesan' => 'Respons layanan WhatsApp tidak dapat dibaca. Hasil belum pasti.'];
    if ($http >= 200 && $http < 300 && ($json['status'] ?? false) === true) {
        $id = $json['id'] ?? null; $id = is_array($id) ? implode(',', $id) : $id;
        return ['status' => 'accepted', 'http_code' => $http, 'provider_id' => $id !== null ? ringkas($id, 100) : null,
                'pesan' => 'Permintaan pengiriman diterima oleh provider.'];
    }
    $alasan = str_replace(FONNTE_TOKEN, '[token]', (string)($json['reason'] ?? $json['detail'] ?? 'tanpa keterangan'));
    return ['status' => 'failed', 'http_code' => $http, 'provider_id' => null, 'pesan' => 'Ditolak provider: ' . ringkas($alasan, 150)];
}
