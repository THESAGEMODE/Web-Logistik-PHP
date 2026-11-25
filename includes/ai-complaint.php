<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(403); exit; }
function ai_uuid(): string {
 $b=random_bytes(16); $b[6]=chr((ord($b[6])&15)|64); $b[8]=chr((ord($b[8])&63)|128);
 $h=bin2hex($b); return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20);
}
function ai_question($value): string {
 if (!is_string($value)) throw new InvalidArgumentException('Keluhan tidak valid.');
 $value=trim($value);
 if (mb_strlen($value)<5 || mb_strlen($value)>2000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)) throw new InvalidArgumentException('Keluhan harus 5–2000 karakter.');
 return $value;
}
function ai_redact(string $question, array $t): string {
 foreach(['nama_pengirim','nama_penerima','telp_pengirim','telp_penerima','alamat_pengirim','alamat_penerima'] as $key){
  $v=trim((string)($t[$key]??'')); if($v!=='') $question=str_ireplace($v,'[data pribadi disamarkan]',$question);
 }
 $question=preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i','[email disamarkan]',$question);
 return preg_replace('/(?<!\w)(?:\+?62|0)[\d\s().\-]{8,18}\d(?!\w)/u','[telepon disamarkan]',$question);
}
function ai_snapshot(array $t,array $history): array {
 $r=[]; foreach(['no_transaksi','resi_ekspedisi','tanggal','status','eksp','nama_layanan','asal','tujuan','status_bayar'] as $key) $r[$key]=(string)($t[$key]??'');
 $r['total_tagihan']=(float)$t['total_tagihan']; $r['terbayar']=(float)$t['terbayar'];
 $r['history']=array_map(fn($h)=>['status'=>(string)$h['status'],'created_at'=>(string)$h['created_at']],array_slice($history,0,10));
 return $r;
}
function ai_output($r): bool {
 if(!is_array($r))return false;
 foreach(['ringkasan'=>2000,'draf_balasan'=>3000] as $k=>$max)if(!isset($r[$k])||!is_string($r[$k])||trim($r[$k])===''||mb_strlen($r[$k])>$max)return false;
 foreach(['tindak_lanjut','perlu_dikonfirmasi'] as $k){
  if(!isset($r[$k])||!is_array($r[$k])||count($r[$k])>6||($k==='tindak_lanjut'&&!count($r[$k])))return false;
  foreach($r[$k] as $s)if(!is_string($s)||trim($s)===''||mb_strlen($s)>1000)return false;
 }
 return true;
}
function ai_send(array $payload): array {
 if(!ai_siap())return ['state'=>'failed','error'=>'Sambungan AI belum diatur oleh admin.'];
 if(!function_exists('curl_init'))return ['state'=>'failed','error'=>'Ekstensi cURL PHP belum aktif.'];
 $ch=curl_init(LOGIPRO_AI_RELAY_URL); $body='';
 curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.LOGIPRO_AI_RELAY_SECRET],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>120,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_WRITEFUNCTION=>function($ch,$part)use(&$body){if(strlen($body)+strlen($part)>32000)return 0;$body.=$part;return strlen($part);}]);
 $ok=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
 if($ok===false)return ['state'=>'unknown','error'=>'Hasil AI belum pasti (koneksi/timeout). Tidak dikirim ulang otomatis.'];
 $r=json_decode($body,true);
 if($code===200&&ai_output($r['recommendation']??null))return ['state'=>'completed','recommendation'=>$r['recommendation'],'model'=>'openai/gpt-6-astra'];
 $safe=[401=>'Sambungan AI ditolak; hubungi admin.',403=>'Model ditolak pengaturan workspace; pemilik perlu memulihkan izin.',409=>'Permintaan sudah diterima sebelumnya. Periksa riwayat; jangan kirim ulang otomatis.',429=>'Batas permintaan AI tercapai. Tunggu sebelum meminta rekomendasi baru.',503=>'Sambungan AI belum tersedia. Hubungi admin.',422=>'AI menolak keluhan ini; lakukan tindak lanjut manual.'];
 if(is_array($r)&&($r['code']??'')==='payment_required')$safe[$code]='Kredit AI habis; hubungi pemilik.';
 return ['state'=>$code>=500||$code===409?'unknown':'failed','error'=>$safe[$code]??'AI tidak menghasilkan rekomendasi yang valid. Lakukan tinjauan manual.'];
}
