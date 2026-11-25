<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(403); exit; }
if (is_file(__DIR__.'/rahasia.php')) require_once __DIR__.'/rahasia.php';
if (!defined('LOGIPRO_AI_RELAY_URL')) define('LOGIPRO_AI_RELAY_URL', (string)(getenv('LOGIPRO_AI_RELAY_URL') ?: ''));
if (!defined('LOGIPRO_AI_RELAY_SECRET')) define('LOGIPRO_AI_RELAY_SECRET', (string)(getenv('LOGIPRO_AI_RELAY_SECRET') ?: ''));
function ai_siap(): bool {
 $u=parse_url(LOGIPRO_AI_RELAY_URL);
 return is_array($u) && ($u['scheme']??'')==='https' && !empty($u['host']) && !isset($u['user']) && !isset($u['pass']) && !isset($u['query']) && !isset($u['fragment']) && ($u['path']??'')==='/api/public/complaint-recommendation' && strlen(LOGIPRO_AI_RELAY_SECRET)>=32;
}
