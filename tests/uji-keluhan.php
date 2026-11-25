<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../includes/ai-complaint.php';require __DIR__.'/../includes/access-policy.php';
$n=0;function test_ai($name,$actual,$expected){global $n;$n++;if($actual!==$expected)throw new RuntimeException('GAGAL '.$name);echo "LULUS $name\n";}
$t=['no_transaksi'=>'TRX-1','resi_ekspedisi'=>'','tanggal'=>'2026-11-12 08:00:00','status'=>'perjalanan','eksp'=>'JNE','nama_layanan'=>'REG','asal'=>'Jakarta','tujuan'=>'Bandung','status_bayar'=>'sebagian','total_tagihan'=>'20000.00','terbayar'=>'10000.00','komisi'=>2000,'telp_penerima'=>'081234567890','nama_penerima'=>'Budi','alamat_penerima'=>'Jl Rahasia'];
$s=ai_snapshot($t,[]);
test_ai('staff keluhan diizinkan',role_can_access('staff','keluhan.php'),true);
test_ai('role asing ditolak',role_can_access('owner','keluhan.php'),false);
test_ai('staff tetap tidak boleh update status',role_can_access('staff','actions/update-status.php'),false);
test_ai('status kanonis dipertahankan',$s['status'],'perjalanan');
test_ai('resi kosong tidak dibuat-buat',$s['resi_ekspedisi'],'');
test_ai('tagihan tersimpan',$s['total_tagihan'],20000.0);
test_ai('pembayaran tersimpan',$s['terbayar'],10000.0);
foreach(['komisi','telp_penerima','nama_penerima','alamat_penerima'] as $key)test_ai('privasi '.$key,array_key_exists($key,$s),false);
test_ai('redaksi data pelanggan',ai_redact('Budi telepon 081234567890 alamat Jl Rahasia',$t),'[data pribadi disamarkan] telepon [data pribadi disamarkan] alamat [data pribadi disamarkan]');
foreach(['',str_repeat('a',2001),[]] as $bad){$rejected=false;try{ai_question($bad);}catch(InvalidArgumentException $e){$rejected=true;}test_ai('keluhan tidak valid ditolak',$rejected,true);}
test_ai('hasil kosong bukan sukses',ai_output(['ringkasan'=>'a','draf_balasan'=>'b','tindak_lanjut'=>[],'perlu_dikonfirmasi'=>[]]),false);
test_ai('saran valid',ai_output(['ringkasan'=>'Paket dalam perjalanan','draf_balasan'=>'Kami akan memeriksa','tindak_lanjut'=>['Konfirmasi ke ekspedisi'],'perlu_dikonfirmasi'=>[]]),true);
test_ai('uuid valid',preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/',ai_uuid()),1);
echo "$n uji lulus.\n";
