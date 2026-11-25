<?php require '../config/app.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../tarif.php'); csrf_check();
if (!empty($_POST['toggle'])) { $id=(int)$_POST['toggle']; $st=$db->prepare('UPDATE tarif SET aktif=1-aktif WHERE id=?'); $st->bind_param('i',$id); $st->execute(); audit($db,'toggle_tarif',"id=$id"); flash('ok','Status tarif diperbarui.'); redirect('../tarif.php'); }
$n = fn($k) => max(0, (float)($_POST[$k] ?? 0));
$lay=(int)$_POST['layanan_id']; $asal=(int)$_POST['asal']; $tuj=(int)$_POST['tujuan'];
$tipe = in_array($_POST['komisi_tipe'] ?? '', ['persen','nominal','selisih']) ? $_POST['komisi_tipe'] : 'persen';
$mulai = DateTime::createFromFormat('Y-m-d', $_POST['berlaku_mulai'] ?? '') ? $_POST['berlaku_mulai'] : date('Y-m-d');
if ($asal === $tuj || $n('tarif_per_kg') <= 0) { flash('err','Kota asal dan tujuan harus berbeda, tarif per kg harus lebih dari 0.'); redirect('../tarif.php'); }
$est = trim($_POST['estimasi'] ?? '');
$st = $db->prepare('INSERT INTO tarif (layanan_id,kota_asal_id,kota_tujuan_id,tarif_per_kg,tarif_modal_per_kg,berat_minimum,biaya_tambahan,faktor_volumetrik,estimasi,komisi_tipe,komisi_nilai,berlaku_mulai) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
$a=$n('tarif_per_kg');$b=$n('tarif_modal_per_kg');$c=$n('berat_minimum');$d=$n('biaya_tambahan');$fv=(int)$n('faktor_volumetrik');$kn=$n('komisi_nilai');
$st->bind_param('iiiddddissds',$lay,$asal,$tuj,$a,$b,$c,$d,$fv,$est,$tipe,$kn,$mulai); $st->execute();
audit($db,'tambah_tarif',"id={$db->insert_id} tarif/kg=$a"); flash('ok','Tarif baru disimpan.'); redirect('../tarif.php');
