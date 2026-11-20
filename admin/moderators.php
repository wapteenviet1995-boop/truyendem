<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
if(empty($_SESSION['blog_admin_id'])){header('Location: login.php');exit;}
if(($_SESSION['blog_admin_role']??'admin')!=='admin'){http_response_code(403);exit('Bạn không có quyền quản lý tài khoản kiểm duyệt viên.');}
if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf'];$error='';$notice='';
function am_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên quản trị hết hạn.';}
 else{$action=(string)($_POST['action']??'');$id=(int)($_POST['admin_id']??0);
  try{if($action==='create'){
   $username=trim((string)($_POST['username']??''));$name=trim((string)($_POST['display_name']??''));$email=trim((string)($_POST['email']??''));$password=(string)($_POST['password']??'');
   if(!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/',$username))throw new RuntimeException('Tên đăng nhập cần 3–80 ký tự, chỉ gồm chữ, số, dấu chấm, gạch dưới hoặc gạch ngang.');
   if(strlen($password)<10)throw new RuntimeException('Mật khẩu KDV phải có ít nhất 10 ký tự.');
   if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Email không hợp lệ.');
   $q=$pdo->prepare("INSERT INTO blog_admins(username,password_hash,display_name,email,role,is_active) VALUES(?,?,?,?, 'editor',1)");$q->execute([$username,password_hash($password,PASSWORD_DEFAULT),$name!==''?$name:null,$email!==''?$email:null]);$notice='Đã tạo tài khoản kiểm duyệt viên.';
  }elseif($action==='toggle'&&$id>0){
   if($id===(int)$_SESSION['blog_admin_id'])throw new RuntimeException('Bạn không thể tự khóa tài khoản đang đăng nhập.');
   $q=$pdo->prepare('UPDATE blog_admins SET is_active=IF(is_active=1,0,1) WHERE id=? AND role=?');$q->execute([$id,'editor']);$notice='Đã cập nhật trạng thái tài khoản KDV.';
  }else throw new RuntimeException('Thao tác không hợp lệ.');
  }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'Không tạo được tài khoản. Tên đăng nhập hoặc email có thể đã được sử dụng.';}
 }
}
$items=$pdo->query('SELECT id,username,display_name,email,role,is_active,last_login_at,created_at FROM blog_admins ORDER BY role,id')->fetchAll();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quản lý KDV | Truyện Đêm CMS</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/admin-white.css?v=20261114-white1"><link rel="stylesheet" href="../css/admin-cms.css?v=20261121-cms1"></head><body class="admin-theme"><?php include __DIR__.'/_nav.php';?><main class="admin-wrap"><section class="admin-panel admin-wide"><div class="admin-top"><div><h1>Quản lý kiểm duyệt viên</h1><p>Tạo tài khoản biên tập viên (KDV), khóa hoặc mở quyền truy cập.</p></div></div><?php if($notice):?><p class="admin-success"><?=am_h($notice)?></p><?php endif;?><?php if($error):?><p class="admin-error"><?=am_h($error)?></p><?php endif;?>
<div class="staff-form-panel"><h2>＋ Tạo tài khoản KDV</h2><form method="post" class="staff-create-form"><input type="hidden" name="csrf" value="<?=am_h($csrf)?>"><input type="hidden" name="action" value="create"><label>Tên đăng nhập<input name="username" required minlength="3" maxlength="80" autocomplete="off"></label><label>Tên hiển thị<input name="display_name" maxlength="120"></label><label>Email<input type="email" name="email" maxlength="190"></label><label>Mật khẩu<input type="password" name="password" required minlength="10" autocomplete="new-password"></label><button type="submit">Tạo kiểm duyệt viên</button></form></div>
<div class="staff-table-wrap"><table><thead><tr><th>Tài khoản</th><th>Quyền</th><th>Trạng thái</th><th>Đăng nhập gần nhất</th><th>Thao tác</th></tr></thead><tbody><?php foreach($items as $m):?><tr><td><strong><?=am_h($m['display_name']?:$m['username'])?></strong><small>@<?=am_h($m['username'])?> · <?=am_h($m['email'])?></small></td><td><?=$m['role']==='admin'?'Quản trị viên':'Kiểm duyệt viên'?></td><td><?=$m['is_active']?'Đang hoạt động':'Đã khóa'?></td><td><?=$m['last_login_at']?am_h(date('d/m/Y H:i',strtotime($m['last_login_at']))):'Chưa đăng nhập'?></td><td><?php if($m['role']==='editor'):?><form method="post"><input type="hidden" name="csrf" value="<?=am_h($csrf)?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="admin_id" value="<?=(int)$m['id']?>"><button type="submit"><?=$m['is_active']?'Khóa tài khoản':'Mở khóa'?></button></form><?php else:?><small>Tài khoản chủ quản</small><?php endif;?></td></tr><?php endforeach;?></tbody></table></div></section></main></body></html>