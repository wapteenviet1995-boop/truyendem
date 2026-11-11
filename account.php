<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php'); exit; }
$currentId=(int)$_SESSION['member_id'];
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['member_csrf'];
$signs=[1=>'Bạch Dương',2=>'Kim Ngưu',3=>'Song Tử',4=>'Cự Giải',5=>'Sư Tử',6=>'Xử Nữ',7=>'Thiên Bình',8=>'Bọ Cạp',9=>'Nhân Mã',10=>'Ma Kết',11=>'Bảo Bình',12=>'Song Ngư'];
$symbols=[1=>'♈',2=>'♉',3=>'♊',4=>'♋',5=>'♌',6=>'♍',7=>'♎',8=>'♏',9=>'♐',10=>'♑',11=>'♒',12=>'♓'];
$error=''; $notice='';
function acc_h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function acc_redirect(string $query='saved'):never{header('Location: account.php?'.$query);exit;}
try {
 $pdo->prepare('INSERT IGNORE INTO member_account_settings(member_id,profile_visibility,show_online) VALUES(?,\'public\',1)')->execute([$currentId]);
} catch(PDOException $ex) {
 http_response_code(500); exit('Thiếu bảng tính năng tài khoản. Hãy chạy database/account_features.sql trong phpMyAdmin.');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))) $error='Phiên đã hết hạn. Hãy tải lại trang.';
 else {
  $action=(string)($_POST['action']??'');
  try {
   if($action==='profile'){
    $name=trim((string)($_POST['display_name']??''));$birth=trim((string)($_POST['birth_date']??''));$sign=filter_var($_POST['zodiac_sign']??null,FILTER_VALIDATE_INT);$bio=trim((string)($_POST['bio']??''));
    $dateValid=$birth==='' || (DateTime::createFromFormat('Y-m-d',$birth) && DateTime::createFromFormat('Y-m-d',$birth)->format('Y-m-d')===$birth);
    if(mb_strlen($name)<2||mb_strlen($name)>100)$error='Tên hiển thị cần từ 2 đến 100 ký tự.';
    elseif(!$dateValid)$error='Ngày sinh không hợp lệ.';
    elseif($sign===false||$sign===null||!isset($signs[$sign]))$error='Hãy chọn cung hoàng đạo của bạn.';
    elseif(mb_strlen($bio)>500)$error='Giới thiệu tối đa 500 ký tự.';
    else{
     $pdo->beginTransaction();
     $pdo->prepare('UPDATE members SET display_name=?,birth_date=? WHERE id=?')->execute([$name,$birth===''?null:$birth,$currentId]);
     $pdo->prepare('INSERT INTO member_profiles(member_id,zodiac_sign,bio) VALUES(?,?,?) ON DUPLICATE KEY UPDATE zodiac_sign=VALUES(zodiac_sign),bio=VALUES(bio)')->execute([$currentId,$sign,$bio]);
     $_SESSION['member_name']=$name;$pdo->commit();acc_redirect('profile_saved');
    }
   }elseif($action==='password'){
    $old=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
    $q=$pdo->prepare('SELECT password_hash FROM members WHERE id=?');$q->execute([$currentId]);$hash=(string)$q->fetchColumn();
    if(!password_verify($old,$hash))$error='Mật khẩu hiện tại chưa chính xác.';
    elseif(strlen($new)<8)$error='Mật khẩu mới cần ít nhất 8 ký tự.';
    elseif($new!==$confirm)$error='Mật khẩu xác nhận không khớp.';
    else{$pdo->prepare('UPDATE members SET password_hash=? WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$currentId]);acc_redirect('password_saved');}
   }elseif($action==='privacy'){
    $visibility=(string)($_POST['profile_visibility']??'public');$showOnline=isset($_POST['show_online'])?1:0;
    if(!in_array($visibility,['public','friends','private'],true))$error='Tùy chọn quyền riêng tư không hợp lệ.';
    else{$pdo->prepare('UPDATE member_account_settings SET profile_visibility=?,show_online=? WHERE member_id=?')->execute([$visibility,$showOnline,$currentId]);acc_redirect('privacy_saved');}
   }elseif($action==='favorites'){
    $chosen=array_unique(array_filter(array_map('intval',(array)($_POST['favorite_signs']??[])),fn($n)=>isset($signs[$n])));
    if(count($chosen)>6)$error='Bạn có thể chọn tối đa 6 cung yêu thích.';
    else{$pdo->beginTransaction();$pdo->prepare('DELETE FROM member_favorite_signs WHERE member_id=?')->execute([$currentId]);$ins=$pdo->prepare('INSERT INTO member_favorite_signs(member_id,zodiac_sign) VALUES(?,?)');foreach($chosen as $n)$ins->execute([$currentId,$n]);$pdo->commit();acc_redirect('favorites_saved');}
   }elseif($action==='avatar'){
    if(empty($_FILES['avatar'])||$_FILES['avatar']['error']!==UPLOAD_ERR_OK)$error='Bạn hãy chọn ảnh hợp lệ để tải lên.';
    elseif((int)$_FILES['avatar']['size']>2*1024*1024)$error='Ảnh đại diện tối đa 2 MB.';
    else{
     $tmp=$_FILES['avatar']['tmp_name'];$info=@getimagesize($tmp);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
     $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
     if(!$info||!isset($extensions[$mime]))$error='Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.';
     else{
      $dir=__DIR__.'/uploads/avatars';
      if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))$error='Không thể tạo thư mục lưu ảnh.';
      else{
       $filename=bin2hex(random_bytes(18)).'.'.$extensions[$mime];$relative='uploads/avatars/'.$filename;
       if(!move_uploaded_file($tmp,__DIR__.'/'.$relative))$error='Không thể lưu ảnh. Hãy kiểm tra quyền thư mục uploads.';
       else{
        $q=$pdo->prepare('SELECT avatar_url FROM members WHERE id=?');$q->execute([$currentId]);$old=(string)$q->fetchColumn();
        $pdo->prepare('UPDATE members SET avatar_url=? WHERE id=?')->execute([$relative,$currentId]);
        if($old!==''&&str_starts_with($old,'uploads/avatars/')){@unlink(__DIR__.'/'.$old);}
        acc_redirect('avatar_saved');
       }
      }
     }
    }
   }else $error='Thao tác không hợp lệ.';
  }catch(PDOException $ex){if($pdo->inTransaction())$pdo->rollBack();$error='Không thể lưu thay đổi. Hãy kiểm tra file database/account_features.sql đã được chạy chưa.';}
 }
}
foreach(['profile_saved'=>'Đã cập nhật hồ sơ.','password_saved'=>'Đã đổi mật khẩu thành công.','privacy_saved'=>'Đã lưu quyền riêng tư.','favorites_saved'=>'Đã lưu các cung yêu thích.','avatar_saved'=>'Đã cập nhật ảnh đại diện.'] as $key=>$msg){if(isset($_GET[$key]))$notice=$msg;}
$q=$pdo->prepare('SELECT m.id,m.display_name,m.email,m.created_at,m.last_login_at,m.last_seen_at,m.birth_date,m.avatar_url,p.zodiac_sign,p.bio,s.profile_visibility,s.show_online FROM members m LEFT JOIN member_profiles p ON p.member_id=m.id JOIN member_account_settings s ON s.member_id=m.id WHERE m.id=? AND m.is_active=1 LIMIT 1');$q->execute([$currentId]);$member=$q->fetch();
if(!$member){unset($_SESSION['member_id'],$_SESSION['member_name']);header('Location: login.php');exit;}
$q=$pdo->prepare('SELECT zodiac_sign FROM member_favorite_signs WHERE member_id=? ORDER BY zodiac_sign');$q->execute([$currentId]);$favoriteSigns=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
$stats=[];
foreach([
 'friends'=>"SELECT COUNT(*) FROM friend_requests WHERE status='accepted' AND (requester_id=? OR addressee_id=?)",
 'posts'=>"SELECT COUNT(*) FROM community_posts WHERE member_id=?",
 'likes'=>"SELECT COUNT(*) FROM community_likes l JOIN community_posts p ON p.id=l.post_id WHERE p.member_id=?",
 'diary'=>"SELECT COUNT(*) FROM member_diary WHERE member_id=?",
 'followers'=>"SELECT COUNT(*) FROM member_follows WHERE following_id=?"
] as $key=>$sql){try{$q=$pdo->prepare($sql);$q->execute($key==='friends'?[$currentId,$currentId]:[$currentId]);$stats[$key]=(int)$q->fetchColumn();}catch(PDOException $ex){$stats[$key]=0;}}
try{$q=$pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE addressee_id=? AND status='pending'");$q->execute([$currentId]);$pending=(int)$q->fetchColumn();}catch(PDOException $ex){$pending=0;}
try{$q=$pdo->prepare('SELECT COUNT(*) FROM member_messages WHERE recipient_id=? AND read_at IS NULL');$q->execute([$currentId]);$unread=(int)$q->fetchColumn();}catch(PDOException $ex){$unread=0;}
$signId=(int)($member['zodiac_sign']??0);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tài khoản của tôi | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=20261111-account1"><link rel="stylesheet" href="css/account.css?v=20261111-1"></head>
<body class="blog-page social-page account-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="index.php#library">Thư viện truyện</a><a href="community.php">Cộng đồng</a><a href="friends.php">Kết bạn</a><a href="diary.php">Nhật ký</a><a href="messages.php">Tin nhắn</a><a href="shop.php">Xu & Cửa hàng</a><a href="index.php#library">Thư viện truyện</a><a href="account.php">Tài khoản</a></nav></div></header>
<main class="account-main"><section class="account-hero"><span class="compat-kicker">✦ HỒ SƠ CỦA BẠN ✦</span><div class="account-avatar"><?php if($member['avatar_url']):?><img src="<?=acc_h($member['avatar_url'])?>" alt="Ảnh đại diện"><?php else:?><span><?=acc_h(mb_substr($member['display_name'],0,1))?></span><?php endif;?></div><h1><?=acc_h($member['display_name'])?></h1><p><?=isset($signs[$signId])?acc_h(($symbols[$signId]??'').' '.$signs[$signId]):'Chưa chọn cung hoàng đạo'?></p><small>Thành viên từ <?=acc_h(date('d/m/Y',strtotime($member['created_at'])))?></small><p><a class="account-button" href="member.php?id=<?=$currentId?>">Xem hồ sơ công khai của tôi</a></p></section>
<?php if($notice):?><p class="account-flash"><?=acc_h($notice)?></p><?php endif;?><?php if($error):?><p class="community-error"><?=acc_h($error)?></p><?php endif;?>
<section class="account-stats"><?php foreach(['friends'=>'Bạn bè','followers'=>'Người theo dõi','posts'=>'Bài cộng đồng','likes'=>'Lượt thích','diary'=>'Trang nhật ký'] as $key=>$label):?><div><strong><?=$stats[$key]??0?></strong><span><?=acc_h($label)?></span></div><?php endforeach;?></section>
<section class="account-notices"><a href="friends.php">✉ <?=$pending?> lời mời kết bạn</a><a href="messages.php">☾ <?=$unread?> tin nhắn chưa đọc</a></section>
<div class="account-grid">
<section class="account-card"><h2>✦ Thông tin hồ sơ</h2><form method="post"><input type="hidden" name="csrf" value="<?=acc_h($csrf)?>"><input type="hidden" name="action" value="profile"><label>Tên hiển thị<input name="display_name" maxlength="100" value="<?=acc_h($member['display_name'])?>" required></label><label>Ngày sinh<input type="date" name="birth_date" value="<?=acc_h($member['birth_date'])?>"></label><label>Cung hoàng đạo<select name="zodiac_sign" required><option value="">Chọn cung</option><?php foreach($signs as $n=>$name):?><option value="<?=$n?>" <?=($signId===$n?'selected':'')?>><?=acc_h($symbols[$n].' '.$name)?></option><?php endforeach;?></select></label><label>Giới thiệu<textarea name="bio" rows="3" maxlength="500" placeholder="Viết vài dòng về bạn..."><?=acc_h($member['bio'])?></textarea></label><button class="account-button">Lưu hồ sơ</button></form></section>
<section class="account-card"><h2>☾ Ảnh đại diện</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=acc_h($csrf)?>"><input type="hidden" name="action" value="avatar"><label>Chọn ảnh JPG, PNG hoặc WEBP<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label><small>Tối đa 2 MB. Ảnh cũ sẽ được thay thế.</small><button class="account-button">Tải ảnh lên</button></form></section>
<section class="account-card"><h2>♡ Cung hoàng đạo yêu thích</h2><p class="account-muted">Chọn tối đa 6 cung bạn muốn theo dõi.</p><form method="post"><input type="hidden" name="csrf" value="<?=acc_h($csrf)?>"><input type="hidden" name="action" value="favorites"><div class="account-sign-grid"><?php foreach($signs as $n=>$name):?><label><input type="checkbox" name="favorite_signs[]" value="<?=$n?>" <?=in_array($n,$favoriteSigns,true)?'checked':''?>><span><?=acc_h($symbols[$n].' '.$name)?></span></label><?php endforeach;?></div><button class="account-button">Lưu cung yêu thích</button></form></section>
<section class="account-card"><h2>♙ Quyền riêng tư</h2><form method="post"><input type="hidden" name="csrf" value="<?=acc_h($csrf)?>"><input type="hidden" name="action" value="privacy"><label>Ai có thể xem hồ sơ<select name="profile_visibility"><option value="public" <?=($member['profile_visibility']==='public'?'selected':'')?>>Mọi người</option><option value="friends" <?=($member['profile_visibility']==='friends'?'selected':'')?>>Chỉ bạn bè</option><option value="private" <?=($member['profile_visibility']==='private'?'selected':'')?>>Chỉ mình tôi</option></select></label><label class="account-check"><input type="checkbox" name="show_online" value="1" <?=((int)$member['show_online']===1?'checked':'')?>><span>Hiển thị trạng thái online</span></label><button class="account-button">Lưu quyền riêng tư</button></form></section>
<section class="account-card"><h2>🔒 Đổi mật khẩu</h2><form method="post"><input type="hidden" name="csrf" value="<?=acc_h($csrf)?>"><input type="hidden" name="action" value="password"><label>Mật khẩu hiện tại<input type="password" name="current_password" autocomplete="current-password" required></label><label>Mật khẩu mới<input type="password" name="new_password" minlength="8" autocomplete="new-password" required></label><label>Nhập lại mật khẩu mới<input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label><button class="account-button">Đổi mật khẩu</button></form></section>
</div><div class="account-links"><a href="shop.php">✦ Ví Xu & Cửa hàng</a><a href="friends.php">Danh sách bạn bè</a><a href="messages.php">Tin nhắn riêng</a><a href="diary.php">Nhật ký của tôi</a><a href="community.php">Cộng đồng</a></div>
<form method="post" action="logout.php" class="account-logout"><input type="hidden" name="csrf" value="<?=acc_h($csrf)?>"><button>Đăng xuất</button></form>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm</div></footer><script src="js/presence.js"></script></body></html>
