<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if(empty($_SESSION['member_id'])){header('Location: /login.php?next=notifications.php');exit;}
$me=(int)$_SESSION['member_id'];
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['member_csrf'];$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn. Hãy tải lại trang.';}
 else{
  try{
   if(($_POST['action']??'')==='read_all'){$pdo->prepare('UPDATE member_notifications SET is_read=1 WHERE recipient_id=?')->execute([$me]);}
   elseif(($_POST['action']??'')==='read_one'){$id=(int)($_POST['notification_id']??0);$pdo->prepare('UPDATE member_notifications SET is_read=1 WHERE id=? AND recipient_id=?')->execute([$id,$me]);}
   header('Location: /notifications.php');exit;
  }catch(PDOException $e){$error='Chưa cài đặt bảng thông báo. Hãy chạy database/member_hub.sql trong phpMyAdmin.';}
 }
}
try{
 $q=$pdo->prepare("SELECT n.id,n.notification_type,n.title,n.message,n.target_url,n.is_read,n.created_at,m.display_name actor_name FROM member_notifications n LEFT JOIN members m ON m.id=n.actor_id WHERE n.recipient_id=? ORDER BY n.created_at DESC,n.id DESC LIMIT 100");
 $q->execute([$me]);$items=$q->fetchAll();
 $q=$pdo->prepare('SELECT COUNT(*) FROM member_notifications WHERE recipient_id=? AND is_read=0');$q->execute([$me]);$unread=(int)$q->fetchColumn();
}catch(PDOException $e){http_response_code(500);exit('Chưa cài đặt bảng thông báo. Hãy chạy database/member_hub.sql trong phpMyAdmin.');}
function noti_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Thông báo | Truyện Đêm</title><link rel="stylesheet" href="/css/style.css?v=memberhub1"><link rel="stylesheet" href="/css/member-hub.css?v=1"></head><body class="story-site"><header class="site-header"><div class="header-inner"><a class="brand" href="/index.php"><span class="brand-mark">☾</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="/index.php">Trang chủ</a><a href="/library.php">Tủ sách</a><a href="/community.php">Cộng đồng</a><a href="/notifications.php">Thông báo</a><a href="/account.php">Tài khoản</a></nav></div></header>
<main class="member-hub-main"><section class="member-hub-hero"><span class="kicker">✦ TIN TỨC TỪ CỘNG ĐỒNG ✦</span><h1>Thông báo <small>(<?=$unread?> chưa đọc)</small></h1><p>Không bỏ lỡ lượt thích, lời hồi đáp và người theo dõi mới.</p></section><nav class="member-hub-tabs"><a href="/library.php">▣ Tủ sách</a><a href="/notifications.php">♧ Thông báo</a><a href="/shop.php">✧ Điểm danh & Xu</a><a href="/account.php">Hồ sơ</a></nav>
<?php if($error):?><p class="member-hub-flash"><?=noti_h($error)?></p><?php endif;?>
<?php if($unread):?><form class="member-notice-tools" method="post"><input type="hidden" name="csrf" value="<?=noti_h($csrf)?>"><input type="hidden" name="action" value="read_all"><button type="submit">Đánh dấu tất cả đã đọc</button></form><?php endif;?>
<section class="member-hub-section"><div class="member-notice-list"><?php if(!$items):?><div class="member-hub-empty">Chưa có thông báo nào. Khi có người tương tác với bạn, thông báo sẽ xuất hiện ở đây.</div><?php endif;?><?php foreach($items as $n):?><article class="member-notice <?=((int)$n['is_read']===0?'unread':'')?>"><span class="member-notice-dot"></span><div class="member-notice-copy"><strong><?=noti_h($n['title'])?></strong><p><?=noti_h($n['message'])?></p><small><?=noti_h(date('d/m/Y H:i',strtotime($n['created_at'])))?><?php if($n['actor_name']):?> · <?=noti_h($n['actor_name'])?><?php endif;?></small><div><a href="<?=noti_h($n['target_url'])?>">Xem nội dung →</a><?php if(!(int)$n['is_read']):?> · <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=noti_h($csrf)?>"><input type="hidden" name="action" value="read_one"><input type="hidden" name="notification_id" value="<?=(int)$n['id']?>"><button type="submit" class="member-hub-remove">Đánh dấu đã đọc</button></form><?php endif;?></div></div></article><?php endforeach;?></div></section></main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm</div></footer></body></html>