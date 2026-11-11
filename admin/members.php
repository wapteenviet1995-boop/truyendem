<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf'];$notice='';$error='';
function admin_member_h(?string $v):string{return htmlspecialchars($v??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$commentsEnabled=true;$settingsReady=true;
try{$v=$pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='comments_enabled' LIMIT 1")->fetchColumn();if($v!==false)$commentsEnabled=$v==='1';}catch(PDOException $e){$settingsReady=false;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên quản trị hết hạn. Hãy tải lại trang.';}
 else{
  $action=(string)($_POST['action']??'');$memberId=(int)($_POST['member_id']??0);
  try{
   if($action==='comments'){
    $enabled=($_POST['enabled']??'0')==='1'?'1':'0';
    $q=$pdo->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES('comments_enabled',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");$q->execute([$enabled]);header('Location: members.php?comments='.$enabled);exit;
   }elseif($action==='toggle'&&$memberId>0){
    $active=(int)($_POST['active']??0)===1?1:0;
    $pdo->prepare('UPDATE members SET is_active=? WHERE id=?')->execute([$active,$memberId]);
    header('Location: members.php?q='.rawurlencode((string)($_GET['q']??'' )).'&updated=1');exit;
   }elseif($action==='gift'&&$memberId>0){
    $amount=filter_var($_POST['amount']??null,FILTER_VALIDATE_INT);
    $amount=$amount===false?0:(int)$amount;
    if($amount<1||$amount>100000){$error='Số Xu phải từ 1 đến 100.000.';}
    else{
     $pdo->beginTransaction();
     $q=$pdo->prepare('SELECT id FROM members WHERE id=? AND is_active=1 FOR UPDATE');$q->execute([$memberId]);
     if(!$q->fetch()){$pdo->rollBack();$error='Tài khoản không tồn tại hoặc đang bị khóa.';}
     else{
      $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$memberId]);
      $pdo->prepare('UPDATE member_wallets SET balance=balance+? WHERE member_id=?')->execute([$amount,$memberId]);
      $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description) VALUES(?,?,'admin','Xu do quan tri vien tang')")->execute([$memberId,$amount]);
      $pdo->commit();header('Location: members.php?gifted=1');exit;
     }
    }
   }elseif($action==='delete'&&$memberId>0){
    $pdo->beginTransaction();
    $q=$pdo->prepare('SELECT id FROM members WHERE id=? FOR UPDATE');$q->execute([$memberId]);
    if(!$q->fetch()){$pdo->rollBack();$error='Tài khoản không tồn tại.';}
    else{$pdo->prepare('DELETE FROM members WHERE id=?')->execute([$memberId]);$pdo->commit();header('Location: members.php?deleted=1');exit;}
   }
  }catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();$error='Thao tác chưa thành công. Kiểm tra các bảng dữ liệu đã cài đặt và thử lại.';}
 }
}
if(isset($_GET['updated']))$notice='Đã cập nhật trạng thái tài khoản.';
if(isset($_GET['gifted']))$notice='Đã tặng Xu thành công.';
if(isset($_GET['deleted']))$notice='Đã xóa tài khoản.';
if(isset($_GET['comments']))$notice=$_GET['comments']==='1'?'Đã bật bình luận.':'Đã tắt bình luận.';
$search=trim((string)($_GET['q']??''));$params=[];$where='';
if($search!==''){$where=' WHERE m.display_name LIKE ? OR m.email LIKE ? OR CAST(m.id AS CHAR)=?';$params=['%'.$search.'%','%'.$search.'%',$search];}
$walletReady=true;
try{$q=$pdo->prepare("SELECT m.id,m.display_name,m.email,m.is_active,m.created_at,m.last_login_at,COALESCE(w.balance,0) AS balance FROM members m LEFT JOIN member_wallets w ON w.member_id=m.id".$where." ORDER BY m.id DESC LIMIT 100");$q->execute($params);$members=$q->fetchAll();}catch(PDOException $e){$walletReady=false;$q=$pdo->prepare("SELECT m.id,m.display_name,m.email,m.is_active,m.created_at,m.last_login_at,0 AS balance FROM members m".$where." ORDER BY m.id DESC LIMIT 100");$q->execute($params);$members=$q->fetchAll();}
$total=(int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quản lý thành viên | Admin</title><link rel="stylesheet" href="../css/style.css?v=20261111-4"><link rel="stylesheet" href="../css/admin-members.css?v=1"></head><body><main class="admin-wrap"><section class="admin-panel admin-wide"><div class="admin-top"><h1>Quản lý thành viên</h1><a href="logout.php">Đăng xuất</a></div><p>Xin chào <?=admin_member_h($_SESSION['blog_admin_name']??'quản trị viên')?> · <a href="index.php">Quản lý bài viết</a> · <a href="../community.php" target="_blank">Xem cộng đồng</a></p>
<?php if(!$walletReady):?><p class="admin-error">Chưa cài bảng Ví Xu. Hãy chạy database/xu_cua_hang.sql để bật chức năng tặng Xu.</p><?php endif;?><?php if($notice):?><p class="admin-success"><?=admin_member_h($notice)?></p><?php endif;?><?php if($error):?><p class="admin-error"><?=admin_member_h($error)?></p><?php endif;?>
<div class="am-stats"><strong><?=$total?> <small>tài khoản</small></strong><div><b>Quản lý bình luận</b><p><?=$settingsReady?'Trạng thái: '.($commentsEnabled?'Đang bật':'Đang tắt'):'Chưa cài bảng site_settings'?></p><form method="post"><input type="hidden" name="csrf" value="<?=admin_member_h($csrf)?>"><input type="hidden" name="action" value="comments"><input type="hidden" name="enabled" value="<?=$commentsEnabled?'0':'1'?>"><button class="am-button" <?=$settingsReady?'':'disabled'?>><?=$commentsEnabled?'Tắt bình luận':'Bật bình luận'?></button></form></div></div>
<form class="am-search" method="get"><input name="q" value="<?=admin_member_h($search)?>" placeholder="Tìm tên, email hoặc ID thành viên"><button class="am-button">Tìm kiếm</button><?php if($search!==''):?><a href="members.php">Xóa tìm</a><?php endif;?></form>
<div class="admin-table"><table><thead><tr><th>Thành viên</th><th>Trạng thái</th><th>Xu</th><th>Ngày tham gia</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach($members as $m):?><tr><td><b><?=admin_member_h($m['display_name'])?></b><small class="am-sub">#<?=(int)$m['id']?> · <?=admin_member_h($m['email'])?></small></td><td><?=$m['is_active']?'🟢 Hoạt động':'🔒 Đã khóa'?></td><td><?=number_format((int)$m['balance'])?></td><td><?=admin_member_h(date('d/m/Y',strtotime($m['created_at'])))?></td><td class="am-actions">
<form method="post"><input type="hidden" name="csrf" value="<?=admin_member_h($csrf)?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="member_id" value="<?=(int)$m['id']?>"><input type="hidden" name="active" value="<?=$m['is_active']?0:1?>"><button class="am-button am-muted"><?=$m['is_active']?'Khóa nick':'Mở khóa'?></button></form>
<form method="post" class="am-gift"><input type="hidden" name="csrf" value="<?=admin_member_h($csrf)?>"><input type="hidden" name="action" value="gift"><input type="hidden" name="member_id" value="<?=(int)$m['id']?>"><input type="number" name="amount" min="1" max="100000" placeholder="Số Xu" required><button class="am-button" <?=$walletReady?'':'disabled'?>>Tặng Xu</button></form>
<form method="post" onsubmit="return confirm('Xóa vĩnh viễn tài khoản này và dữ liệu liên quan?')"><input type="hidden" name="csrf" value="<?=admin_member_h($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="member_id" value="<?=(int)$m['id']?>"><button class="am-button am-danger">Xóa</button></form>
</td></tr><?php endforeach;?><?php if(!$members):?><tr><td colspan="5">Không tìm thấy thành viên.</td></tr><?php endif;?></tbody></table></div><p class="am-note">Danh sách hiển thị tối đa 100 tài khoản mới nhất. Xóa tài khoản là thao tác không thể hoàn tác.</p></section></main></body></html>