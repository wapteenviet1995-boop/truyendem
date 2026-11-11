<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if(empty($_SESSION['member_id'])){header('Location: login.php');exit;}
$currentId=(int)$_SESSION['member_id'];$target=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$target||$target<1){http_response_code(404);exit('Không tìm thấy thành viên.');}
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$signs=[1=>'Bạch Dương',2=>'Kim Ngưu',3=>'Song Tử',4=>'Cự Giải',5=>'Sư Tử',6=>'Xử Nữ',7=>'Thiên Bình',8=>'Bọ Cạp',9=>'Nhân Mã',10=>'Ma Kết',11=>'Bảo Bình',12=>'Song Ngư'];
$symbols=[1=>'♈',2=>'♉',3=>'♊',4=>'♋',5=>'♌',6=>'♍',7=>'♎',8=>'♏',9=>'♐',10=>'♑',11=>'♒',12=>'♓'];
$q=$pdo->prepare('SELECT m.id,m.display_name,m.created_at,m.last_seen_at,m.avatar_url,p.zodiac_sign,p.bio,s.profile_visibility,s.show_online FROM members m LEFT JOIN member_profiles p ON p.member_id=m.id LEFT JOIN member_account_settings s ON s.member_id=m.id WHERE m.id=? AND m.is_active=1 LIMIT 1');$q->execute([$target]);$person=$q->fetch();
if(!$person){http_response_code(404);exit('Không tìm thấy thành viên.');}
$relation='none';$requestId=0;
if($target!==$currentId){
 $q=$pdo->prepare("SELECT id,requester_id,status FROM friend_requests WHERE (requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?) ORDER BY id DESC LIMIT 1");$q->execute([$currentId,$target,$target,$currentId]);$r=$q->fetch();
 if($r){$requestId=(int)$r['id'];if($r['status']==='accepted')$relation='friend';elseif($r['status']==='pending')$relation=((int)$r['requester_id']===$currentId?'outgoing':'incoming');}
}
$equippedItems=[];try{$q=$pdo->prepare('SELECT s.name,s.item_type,s.icon FROM member_inventory i JOIN shop_items s ON s.id=i.item_id WHERE i.member_id=? AND i.is_equipped=1 ORDER BY s.item_type');$q->execute([$target]);$equippedItems=$q->fetchAll();}catch(PDOException $e){}
$visibility=$person['profile_visibility']?:'public';
if($target!==$currentId && (($visibility==='private') || ($visibility==='friends' && $relation!=='friend'))){http_response_code(403);exit('Thành viên này chỉ cho phép bạn bè xem hồ sơ.');}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&$target!==$currentId){
 if(!hash_equals($_SESSION['member_csrf'],(string)($_POST['csrf']??'')))$error='Phiên đã hết hạn. Hãy tải lại trang.';
 else{
  $action=(string)($_POST['action']??'');
  if($action==='send'&&$relation==='none'){
   $q=$pdo->prepare("INSERT INTO friend_requests(requester_id,addressee_id,status) VALUES(?,?,'pending')");$q->execute([$currentId,$target]);header('Location: member.php?id='.$target.'&sent=1');exit;
  }elseif($action==='accept'&&$relation==='incoming'){
   $q=$pdo->prepare("UPDATE friend_requests SET status='accepted' WHERE id=? AND addressee_id=? AND status='pending'");$q->execute([$requestId,$currentId]);header('Location: member.php?id='.$target.'&accepted=1');exit;
  }elseif($action==='cancel'&&$relation==='outgoing'){
   $q=$pdo->prepare("DELETE FROM friend_requests WHERE id=? AND requester_id=? AND status='pending'");$q->execute([$requestId,$currentId]);header('Location: member.php?id='.$target.'&cancelled=1');exit;
  }elseif($action==='unfriend'&&$relation==='friend'){
   $q=$pdo->prepare("DELETE FROM friend_requests WHERE status='accepted' AND ((requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?))");$q->execute([$currentId,$target,$target,$currentId]);header('Location: member.php?id='.$target.'&removed=1');exit;
  }else $error='Thao tác không hợp lệ.';
 }
}
if(isset($_GET['sent']))$error='Đã gửi lời mời kết bạn.';
if(isset($_GET['accepted']))$error='Hai bạn đã trở thành bạn bè.';
if(isset($_GET['cancelled']))$error='Đã hủy lời mời.';
if(isset($_GET['removed']))$error='Đã hủy kết bạn.';
function member_h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$signId=(int)($person['zodiac_sign']??0);
$seen=((int)($person['show_online']??1)===1)?($person['last_seen_at']??null):null;$online=$seen && strtotime($seen)>=time()-300;$activity='Chưa hoạt động';if($online)$activity='Đang online';elseif($seen){$gap=max(0,time()-strtotime($seen));$activity=$gap<3600?'Hoạt động '.max(1,(int)floor($gap/60)).' phút trước':($gap<86400?'Hoạt động '.(int)floor($gap/3600).' giờ trước':'Hoạt động '.(int)floor($gap/86400).' ngày trước');}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hồ sơ thành viên | Truyện Đêm</title><link rel="stylesheet" href="css/style.css?v=socialdiary1"><link rel="stylesheet" href="css/member-shop.css?v=1"></head>
<body class="blog-page social-page"><header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="friends.php">Kết bạn</a><a href="diary.php">Nhật ký</a><a href="community.php">Cộng đồng</a><a href="messages.php">Tin nhắn</a></nav></div></header>
<main class="social-main"><section class="social-card member-public-card"><div class="member-public-symbol"><?php if(!empty($person['avatar_url'])):?><img src="<?=member_h($person['avatar_url'])?>" alt="Ảnh đại diện" style="width:100%;height:100%;object-fit:cover;border-radius:50%"><?php else:?><?=isset($symbols[$signId])?member_h($symbols[$signId]):'✦'?><?php endif;?></div><span class="compat-kicker">✦ HỒ SƠ THÀNH VIÊN ✦</span><h1><?=member_h($person['display_name'])?></h1><p class="member-public-sign"><?=isset($signs[$signId])?member_h($signs[$signId]):'Chưa cập nhật cung hoàng đạo'?></p><?php if(!empty($equippedItems)):?><div class="member-equipped"><?php foreach($equippedItems as $item):?><span><?=member_h($item['icon'].' '.$item['name'])?></span><?php endforeach;?></div><?php endif;?><p class="member-presence"><span class="<?=$online?'presence-online':'presence-offline'?>">●</span> <?=member_h($activity)?></p><p class="member-public-bio"><?=nl2br(member_h($person['bio']?:'Thành viên chưa viết lời giới thiệu.'))?></p><p class="social-muted">Tham gia từ <?=member_h(date('d/m/Y',strtotime($person['created_at'])))?></p>
<?php if($error):?><p class="social-notice"><?=member_h($error)?></p><?php endif;?>
<?php if($target===$currentId):?><a class="social-button" href="account.php">Chỉnh sửa hồ sơ</a><?php elseif($relation==='none'):?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="send"><button class="social-button" type="submit">+ Gửi lời mời kết bạn</button></form><?php elseif($relation==='incoming'):?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="accept"><button class="social-button" type="submit">Chấp nhận lời mời</button></form><?php elseif($relation==='outgoing'):?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="cancel"><button class="social-quiet" type="submit">Hủy lời mời đã gửi</button></form><?php else:?><p class="social-status">♥ Hai bạn đã là bạn bè</p><a class="social-button" href="messages.php?with=<?=(int)$person['id']?>">Nhắn tin riêng ✉</a><form method="post" onsubmit="return confirm('Bạn muốn hủy kết bạn?')"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="unfriend"><button class="social-quiet" type="submit">Hủy kết bạn</button></form><?php endif;?>
<p><a class="social-quiet-link" href="friends.php">← Quay lại danh sách thành viên</a></p></section></main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm</div></footer><script src="js/presence.js"></script></body></html>
