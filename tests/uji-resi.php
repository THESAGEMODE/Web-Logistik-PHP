<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../includes/resi.php';
$n=0; $gagal=0;
function cek_resi($label,$actual,$expected) { global $n,$gagal; $n++; $ok=$actual===$expected; if(!$ok)$gagal++; echo ($ok?'LULUS ':'GAGAL ').$label."\n"; }
foreach (['0','-1','12abc','2147483648'] as $v) cek_resi('id invalid '.$v,parameter_id_resi($v),null);
cek_resi('id positif',parameter_id_resi('12'),12);
cek_resi('id array',parameter_id_resi(['1']),null);
cek_resi('resi dirapikan',parameter_resi(' JNE-123 '),'JNE-123');
cek_resi('resi kosong',parameter_resi(''),null);
cek_resi('resi array',parameter_resi(['x']),null);
cek_resi('resi panjang',parameter_resi(str_repeat('x',51)),null);
cek_resi('resi kontrol',parameter_resi("A\nB"),null);
foreach(['58','80','a4'] as $s) cek_resi('kertas '.$s,ukuran_resi($s),$s);
cek_resi('kertas invalid',ukuran_resi('900'),'80');
foreach(['dibuat','menunggu_bayar','menunggu_serah'] as $s) {
 cek_resi($s.' label',status_resi($s),'Manifest (Diproses di Agen)'); cek_resi($s.' progres',progres_resi($s),1);
}
foreach(['diserahkan','perjalanan','diantar'] as $s) {
 cek_resi($s.' label',status_resi($s),'On Process (Dalam Pengiriman)'); cek_resi($s.' progres',progres_resi($s),2);
}
cek_resi('terkirim label',status_resi('terkirim'),'Delivered (Terkirim)');
cek_resi('terkirim progres',progres_resi('terkirim'),3);
foreach(['dibatalkan','bermasalah','asing'] as $s) cek_resi($s.' bukan Delivered',progres_resi($s),0);
echo "$n uji; $gagal gagal\n"; exit($gagal?1:0);
