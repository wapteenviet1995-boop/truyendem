<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/avatar-frames.php';
if(empty($_SESSION['member_id'])){header('Location: login.php');exit;}
$currentId=(int)$_SESSION['member_id'];$target=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$target||$target<1){http_response_code(404);exit('Không tìm thấy thành viên.');}
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$signs=[1=>'Bạch Dương',2=>'Kim Ngưu',3=>'Song Tử',4=>'Cự Giải',5=>'Sư Tử',6=>'Xử Nữ',7=>'Thiên Bình',8=>'Cự Giải',9=>'Nhân Mã',10=>'Ma Kết',11=>'Bảo Bình',12=>'Song Ngư'];
$signs[8]='Bọ Cạp';
$symbols=[1=>'♈',2=>'♉',3=>'♊',4=>'♋',5=>'♌',6=>'♍',7=>'♎',8=>'♏',9=>'♐',10=>'♑',11=>'♒',12=>'♓'];
$q=$pdo->prepare('SELECT m.id,m.display_name,m.created_at,m.last_seen_at,m.avatar_url,m.account_type,p.zodiac_sign,p.bio,s.profile_visibility,s.show_online FROM members m LEFT JOIN member_profiles p ON p.member_id=m.id LEFT JOIN member_account_settings s ON s.member_id=m.id WHERE m.id=? AND m.is_active=1 LIMIT 1');$q->execute([$target]);$person=$q->fetch();
if(!$person){http_response_code(404);exit('Không tìm thấy thành viên.');}
$relation='none';$requestId=0;$isFollowing=false;$followerCount=0;
if(($person['account_type']??'')==='contributor'){
 try{$q=$pdo->prepare('SELECT COUNT(*) FROM member_follows WHERE following_id=?');$q->execute([$target]);$followerCount=(int)$q->fetchColumn();if($target!==$currentId){$q=$pdo->prepare('SELECT 1 FROM member_follows WHERE follower_id=? AND following_id=? LIMIT 1');$q->execute([$currentId,$target]);$isFollowing=(bool)$q->fetchColumn();}}catch(Throwable $e){}
}
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
  if($action==='follow' && ($person['account_type']??'')==='contributor' && !$isFollowing){
   try{
    $q=$pdo->prepare('INSERT IGNORE INTO member_follows(follower_id,following_id) VALUES(?,?)');$q->execute([$currentId,$target]);
    if($q->rowCount()>0)td_notify($pdo,$target,$currentId,'new_follower','Bạn có người theo dõi mới','Một thành viên vừa bắt đầu theo dõi bạn.','/member.php?id='.$currentId);
    header('Location: member.php?id='.$target.'&followed=1');exit;
   }catch(Throwable $e){$error='Chưa cài đặt bảng theo dõi. Hãy chạy database/community.sql trong phpMyAdmin.';}
  }elseif($action==='unfollow' && $isFollowing){
   try{$q=$pdo->prepare('DELETE FROM member_follows WHERE follower_id=? AND following_id=?');$q->execute([$currentId,$target]);header('Location: member.php?id='.$target.'&unfollowed=1');exit;}
   catch(Throwable $e){$error='Không thể hủy theo dõi lúc này. Vui lòng thử lại.';}
  }elseif($action==='send'&&$relation==='none'){
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
if(isset($_GET['followed']))$error='Bạn đã theo dõi tác giả này.';
if(isset($_GET['unfollowed']))$error='Bạn đã hủy theo dõi tác giả.';
if(isset($_GET['sent']))$error='Đã gửi lời mời kết bạn.';
if(isset($_GET['accepted']))$error='Hai bạn đã trở thành bạn bè.';
if(isset($_GET['cancelled']))$error='Đã hủy lời mời.';
if(isset($_GET['removed']))$error='Đã hủy kết bạn.';
function member_h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$creatorStats=null;$creatorStories=[];
if(($person['account_type']??'member')==='contributor'){
 $q=$pdo->prepare("SELECT COUNT(*) total,COALESCE(SUM(views_count),0) views,COALESCE(SUM(likes_count),0) likes FROM stories WHERE member_id=? AND status='published'");$q->execute([$target]);$creatorStats=$q->fetch()?:['total'=>0,'views'=>0,'likes'=>0];
 $q=$pdo->prepare("SELECT id,title,slug,story_kind,story_type,views_count,likes_count,created_at FROM stories WHERE member_id=? AND status='published' ORDER BY created_at DESC,id DESC LIMIT 12");$q->execute([$target]);$creatorStories=$q->fetchAll();
}
function member_rank(int $published,int $views):array{
 if($published>=20||$views>=50000)return ['Tác giả tiêu biểu','featured','✦'];
 if($published>=10||$views>=10000)return ['Tác giả chính thức','official','◆'];
 if($published>=3||$views>=2000)return ['Cây bút triển vọng','rising','✍'];
 return ['CTV mới','new','✧'];
}
function member_story_url(array $s):string{return (($s['story_type']??'')==='audio'?'/audio/':(($s['story_kind']??'')==='short'?'/truyen-ngan/':'/truyen/')).rawurlencode((string)$s['slug']).'.html';}
$signId=(int)($person['zodiac_sign']??0);
$seen=((int)($person['show_online']??1)===1)?($person['last_seen_at']??null):null;$online=$seen && strtotime($seen)>=time()-300;$activity='Chưa hoạt động';if($online)$activity='Đang online';elseif($seen){$gap=max(0,time()-strtotime($seen));$activity=$gap<3600?'Hoạt động '.max(1,(int)floor($gap/60)).' phút trước':($gap<86400?'Hoạt động '.(int)floor($gap/3600).' giờ trước':'Hoạt động '.(int)floor($gap/86400).' ngày trước');}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hồ sơ thành viên | Truyện Đêm</title><link rel="stylesheet" href="css/style.css?v=socialdiary1"><link rel="stylesheet" href="css/member-shop.css?v=1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
<link rel="stylesheet" href="/css/theme-mode.css?v=20261122-global-light-audit5"><link rel="stylesheet" href="/css/avatar-frames.css?v=1"><style><style>
.creator-follow-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:14px auto 0;width:min(100%,760px);padding:12px 15px;border:1px solid #40304f;border-radius:12px;background:#17131f;color:#e9dff1}.creator-follow-bar span{font-size:.82rem;color:#d9c7e8;font-weight:750}.creator-profile{width:min(100%,760px);margin:16px auto 0;padding:17px;border:1px solid #40304f;border-radius:15px;background:linear-gradient(145deg,#20182b,#12121b);color:#eee8f5}.creator-profile-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}.creator-profile-head h2{margin:0;font-size:1.05rem}.creator-profile-rank{padding:7px 11px;border-radius:30px;background:#392849;color:#ead5ff;font-size:.72rem;font-weight:850;white-space:nowrap}.creator-profile-rank.featured{background:#49351f;color:#ffe0a4}.creator-profile-rank.official{background:#302c49;color:#d6c7ff}.creator-profile-rank.rising{background:#263a36;color:#bde9d6}.creator-profile-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.creator-profile-stats article{padding:11px;border:1px solid #382e45;border-radius:10px;background:#191622}.creator-profile-stats strong{display:block;color:#f2e8fa;font-size:1.2rem}.creator-profile-stats small{display:block;margin-top:4px;color:#a99fb4;font-size:.7rem}.creator-profile-stories{display:grid;gap:8px;margin-top:13px}.creator-profile-story{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px;border:1px solid #342b40;border-radius:10px;background:#191722;text-decoration:none;color:#eee8f5}.creator-profile-story strong{display:block;font-size:.82rem;overflow-wrap:anywhere}.creator-profile-story small{display:block;margin-top:4px;color:#a99fb4;font-size:.7rem}.creator-profile-story em{font-style:normal;color:#d9b8f1;font-size:.75rem;white-space:nowrap}.creator-profile-empty{padding:15px;text-align:center;border:1px dashed #493a55;border-radius:10px;color:#a99fb4;font-size:.8rem}
@media(max-width:520px){.creator-follow-bar{align-items:stretch;flex-direction:column}.creator-follow-bar form,.creator-follow-bar button{width:100%}.creator-profile{margin:12px 0 0;padding:13px}.creator-profile-head{align-items:flex-start;flex-direction:column}.creator-profile-story{align-items:flex-start;flex-direction:column}.creator-profile-story em{white-space:normal}}
</style></head>
<body class="blog-page social-page"><?php require __DIR__ . '/includes/header.php'; ?>
<main class="social-main"><section class="social-card member-public-card"><?=td_avatar_html($pdo,(int)$person['id'],(string)($person['avatar_url']??''),isset($symbols[$signId])?(string)$symbols[$signId]:'✦','member-public-symbol')?><span class="compat-kicker">✦ HỒ SƠ THÀNH VIÊN ✦</span><h1><?=member_h($person['display_name'])?></h1><p class="member-public-sign"><?=isset($signs[$signId])?member_h($signs[$signId]):'Chưa cập nhật cung hoàng đạo'?></p><?php if(!empty($equippedItems)):?><div class="member-equipped"><?php foreach($equippedItems as $item):?><span><?=member_h($item['icon'].' '.$item['name'])?></span><?php endforeach;?></div><?php endif;?><p class="member-presence"><span class="<?=$online?'presence-online':'presence-offline'?>">●</span> <?=member_h($activity)?></p><p class="member-public-bio"><?=nl2br(member_h($person['bio']?:'Thành viên chưa viết lời giới thiệu.'))?></p><p class="social-muted">Tham gia từ <?=member_h(date('d/m/Y',strtotime($person['created_at'])))?></p>
<?php if($error):?><p class="social-notice"><?=member_h($error)?></p><?php endif;?><?php if($creatorStats!==null):[$rankName,$rankClass,$rankSymbol]=member_rank((int)$creatorStats['total'],(int)$creatorStats['views']);?><section class="creator-profile"><div class="creator-profile-head"><h2>✦ Hồ sơ tác giả Truyện Đêm</h2><span class="creator-profile-rank <?=member_h($rankClass)?>"><?=$rankSymbol?> <?=member_h($rankName)?></span></div><div class="creator-profile-stats"><article><strong><?=number_format((int)$creatorStats['total'])?></strong><small>Truyện xuất bản</small></article><article><strong><?=number_format((int)$creatorStats['views'])?></strong><small>Lượt đọc</small></article><article><strong><?=number_format((int)$creatorStats['likes'])?></strong><small>Lượt thích</small></article></div><div class="creator-profile-head" style="margin:17px 0 10px"><h2>Truyện đã xuất bản</h2><small style="color:#a99fb4;font-size:.72rem">Tối đa 12 truyện mới nhất</small></div><?php if(!$creatorStories):?><div class="creator-profile-empty">Tác giả chưa có truyện xuất bản.</div><?php else:?><div class="creator-profile-stories"><?php foreach($creatorStories as $story):?><a class="creator-profile-story" href="<?=member_h(member_story_url($story))?>"><span><strong><?=member_h($story['title'])?></strong><small><?=($story['story_type']==='audio'?'Audio':(($story['story_kind']??'')==='short'?'Truyện ngắn':'Truyện dài'))?> · <?=member_h(date('d/m/Y',strtotime($story['created_at'])))?></small></span><em>◉ <?=number_format((int)$story['views_count'])?> · ♥ <?=number_format((int)$story['likes_count'])?></em></a><?php endforeach;?></div><?php endif;?></section><?php endif;?>
<?php if($target!==$currentId && ($person['account_type']??'')==='contributor'):?><div class="creator-follow-bar"><span>♡ <?=number_format($followerCount)?> người theo dõi</span><?php if($isFollowing):?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="unfollow"><button class="social-quiet" type="submit">Đang theo dõi · Bỏ theo dõi</button></form><?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="follow"><button class="social-button" type="submit">+ Theo dõi tác giả</button></form><?php endif;?></div><?php endif;?><?php if($target===$currentId):?><a class="social-button" href="account.php">Chỉnh sửa hồ sơ</a><?php elseif($relation==='none'):?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="send"><button class="social-button" type="submit">+ Gửi lời mời kết bạn</button></form><?php elseif($relation==='incoming'):?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="accept"><button class="social-button" type="submit">Chấp nhận lời mời</button></form><?php elseif($relation==='outgoing'):?><form method="post"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="cancel"><button class="social-quiet" type="submit">Hủy lời mời đã gửi</button></form><?php else:?><p class="social-status">♥ Hai bạn đã là bạn bè</p><a class="social-button" href="messages.php?with=<?=(int)$person['id']?>">Nhắn tin riêng ✉</a><form method="post" onsubmit="return confirm('Bạn muốn hủy kết bạn?')"><input type="hidden" name="csrf" value="<?=member_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="unfriend"><button class="social-quiet" type="submit">Hủy kết bạn</button></form><?php endif;?>
<p><a class="social-quiet-link" href="friends.php">← Quay lại danh sách thành viên</a></p></section></main><?php require __DIR__ . '/includes/footer.php'; ?><script src="js/presence.js"></script></body></html>
