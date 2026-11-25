<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../includes/access-policy.php';require __DIR__.'/../includes/report-range.php';
$n=0;function cek6($name,$actual,$expected){global $n;$n++;if($actual!==$expected)throw new RuntimeException('GAGAL '.$name);echo 'LULUS '.$name."\n";}
foreach(['input-resi.php','transaksi.php','detail-transaksi.php','cetak-resi.php','api/hitung-ongkir.php','actions/simpan-pengiriman.php','logout.php'] as $p)cek6('staff boleh '.$p,role_can_access('staff',$p),true);
foreach(['dashboard.php','tarif.php','ekspedisi.php','users.php','pengguna.php','laporan.php','profil.php','pembayaran.php','pengeluaran.php','backup.php','tracking-internal.php','actions/update-status.php','actions/kirim-struk-whatsapp.php','actions/kirim-struk-telegram.php','actions/simpan-pengguna.php','api/baru.php'] as $p)cek6('staff ditolak '.$p,role_can_access('staff',$p),false);
cek6('admin penuh',role_can_access('admin','laporan.php'),true);cek6('role asing ditolak',role_can_access('owner','transaksi.php'),false);
cek6('tanggal akhir termasuk',report_range('2026-11-01','2026-11-09'),['2026-11-01 00:00:00','2026-11-10 00:00:00']);
foreach([['2026-11-10','2026-11-09'],['2026-02-30','2026-03-01'],[[],'2026-11-09'],["2026-11-01' OR 1=1",'2026-11-09']] as $range){$bad=false;try{report_range(...$range);}catch(InvalidArgumentException $ex){$bad=true;}cek6('tanggal salah ditolak',$bad,true);}
echo "$n uji lulus.\n";
