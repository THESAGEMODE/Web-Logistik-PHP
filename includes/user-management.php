<?php
function save_managed_user($db,array $data,int $me):string {
 $id=filter_var($data['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);$action=$data['action']??'save';
 if($id===false||!in_array($action,['save','toggle','delete'],true)||($action!=='save'&&!$id))throw new InvalidArgumentException('Permintaan tidak valid.');
 $db->begin_transaction();try{
 $admins=$db->query("SELECT id,aktif FROM users WHERE role='admin' ORDER BY id FOR UPDATE")->fetch_all(MYSQLI_ASSOC);
 $q=$db->prepare('SELECT id,role,aktif FROM users WHERE id=? FOR UPDATE');$q->bind_param('i',$id);$q->execute();$old=$q->get_result()->fetch_assoc();
 if($id&&!$old)throw new InvalidArgumentException('Akun tidak ditemukan.');
 $role=$data['role']??'staff';if(!in_array($role,['admin','staff'],true))throw new InvalidArgumentException('Role harus admin atau staff.');
 if($id&&$id===$me&&($action!=='save'||$role!=='admin'))throw new InvalidArgumentException('Tidak boleh menghapus, menonaktifkan, atau menurunkan role akun sendiri.');
 $loses=$action==='delete'||($action==='toggle'&&$old&&$old['aktif'])||($action==='save'&&$role!=='admin');
 if($old&&$old['role']==='admin'&&$old['aktif']&&$loses&&count(array_filter($admins,fn($a)=>(int)$a['aktif']===1))<=1)throw new InvalidArgumentException('Minimal satu admin aktif harus tersedia.');
 $message='Pengguna disimpan.';
 if($action==='delete'){
 $refs=0;foreach(['pengiriman','pembayaran','riwayat_status','pengeluaran','whatsapp_logs','telegram_logs','ai_keluhan'] as $table){
 $q=$db->prepare('SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$q->bind_param('s',$table);$q->execute();if(!(int)$q->get_result()->fetch_assoc()['n'])continue;
 $column=$table==='telegram_logs'?'sent_by':'user_id';$q=$db->prepare("SELECT COUNT(*) n FROM `$table` WHERE `$column`=?");$q->bind_param('i',$id);$q->execute();$refs+=(int)$q->get_result()->fetch_assoc()['n'];}
 if($refs){$q=$db->prepare('UPDATE users SET aktif=0 WHERE id=?');$message='Akun dinonaktifkan karena memiliki riwayat transaksi; riwayat tetap aman.';}else{$q=$db->prepare('DELETE FROM users WHERE id=?');$message='Akun dihapus.';}$q->bind_param('i',$id);$q->execute();
 }elseif($action==='toggle'){$q=$db->prepare('UPDATE users SET aktif=1-aktif WHERE id=?');$q->bind_param('i',$id);$q->execute();$message='Status akun diperbarui.';}
 else{
 $name=trim($data['nama']??'');$username=trim($data['username']??'');$password=$data['password']??'';
 if($name===''||mb_strlen($name)>100||!preg_match('/^[a-zA-Z0-9_.]{3,50}$/',$username))throw new InvalidArgumentException('Nama/username tidak valid.');
 if((!$id||$password!=='')&&(strlen($password)<8||strlen($password)>72))throw new InvalidArgumentException('Password harus 8–72 byte.');
 $q=$db->prepare('SELECT id FROM users WHERE username=? AND id<>?');$q->bind_param('si',$username,$id);$q->execute();if($q->get_result()->num_rows)throw new InvalidArgumentException('Username sudah dipakai.');
 if($id){$q=$db->prepare('UPDATE users SET nama=?,username=?,role=? WHERE id=?');$q->bind_param('sssi',$name,$username,$role,$id);$q->execute();if($password!==''){$hash=password_hash($password,PASSWORD_DEFAULT);$q=$db->prepare('UPDATE users SET password=? WHERE id=?');$q->bind_param('si',$hash,$id);$q->execute();}}
 else{$hash=password_hash($password,PASSWORD_DEFAULT);$q=$db->prepare('INSERT INTO users(nama,username,password,role) VALUES(?,?,?,?)');$q->bind_param('ssss',$name,$username,$hash,$role);$q->execute();$id=(int)$db->insert_id;}}
 audit($db,'user_'.$action,"id=$id");$db->commit();return $message;
 }catch(Throwable $ex){$db->rollback();throw $ex;}
}
