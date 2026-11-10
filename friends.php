<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php'); exit; }
$currentId=(int)$_SESSION['member_id'];
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(32));
$signs=[1=>'Bạch Dương',2=>'Kim Ngưu',3=>'Song Tử',4=>'Cự Giải',5=>'Sư Tử',6=>'Xử Nữ',7=>'Thiên Bình',8=>'Bọ Cạp',9=>'Nhân Mã',10=>'Ma Kết',11=>'Bảo Bình',12=>'Song Ngư'];
$symbols=[1=>'♈',2=>'♉',3=>'♊',4=>'♋',5=>'♌',6=>'♍',7=>'♎',8=>'♏',9=>'♐',10=>'♑',11=>'♒',12=>'♓'];
$error='';$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($_SESSION['member_csrf'],(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn. Hãy tải lại trang.';}
 else{
  $action=(string)($_POST['action']??'');
  try{
   if($action==='save_profile'){
    $sign=filter_var($_POST['zodiac_sign']??null,FILTER_VALIDATE_INT);
    $bio=trim((string)($_POST['bio']??''));
    if($sign===false||$sign===null||!isset($signs[$sign]))$error='Hãy chọn cung hoàng đạo của bạn.';
    elseif(mb_strlen($bio)>500)$error='Giới thiệu tối đa 500 ký tự.';
    else{$q=$pdo->prepare('INSERT INTO member_profiles(member_id,zodiac_sign,bio) VALUES(?,?,?) ON DUPLICATE KEY UPDATE zodiac_sign=VALUES(zodiac_sign),bio=VALUES(bio)');$q->execute([$currentId,$sign,$bio]);header('Location: friends.php?profile=1');exit;}
   }elseif($action==='send_request'){
    $target=filter_var($_POST['member_id']??null,FILTER_VALIDATE_INT);
    if(!$target||$target<1||$target===$currentId)$error='Không thể gửi lời mời đến tài khoản này.';
    else{
     $q=$pdo->prepare('SELECT id FROM members WHERE id=? AND is_active=1');$q->execute([$target]);
     if(!$q->fetch())$error='Thành viên không tồn tại.';
     else{
      $q=$pdo->prepare("SELECT id,status FROM friend_requests WHERE (requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?) ORDER BY id DESC LIMIT 1");
      $q->execute([$currentId,$target,$target,$currentId]);$existing=$q->fetch();
      if($existing&&$existing['status']==='accepted')$error='Hai bạn đã là bạn bè rồi.';
      elseif($existing&&$existing['status']==='pending')$error='Đã có lời mời kết bạn đang chờ xử lý.';
      elseif($existing){$q=$pdo->prepare("UPDATE friend_requests SET requester_id=?,addressee_id=?,status='pending' WHERE id=?");$q->execute([$currentId,$target,$existing['id']]);}
      else{$q=$pdo->prepare("INSERT INTO friend_requests(requester_id,addressee_id) VALUES(?,?)");$q->execute([$currentId,$target]);}
      if(!$error){header('Location: friends.php?sent=1');exit;}
     }
    }
   }elseif($action==='respond'){
    $rid=filter_var($_POST['request_id']??null,FILTER_VALIDATE_INT);$choice=(string)($_POST['choice']??'');
    if(!$rid||!in_array($choice,['accepted','rejected'],true))$error='Lời mời không hợp lệ.';
    else{$q=$pdo->prepare("UPDATE friend_requests SET status=? WHERE id=? AND addressee_id=? AND status='pending'");$q->execute([$choice,$rid,$currentId]);header('Location: friends.php?'.($choice==='accepted'?'accepted=1':'rejected=1'));exit;}
   }elseif($action==='cancel'){
    $rid=filter_var($_POST['request_id']??null,FILTER_VALIDATE_INT);
    if($rid){$q=$pdo->prepare("DELETE FROM friend_requests WHERE id=? AND requester_id=? AND status='pending'");$q->execute([$rid,$currentId]);}
    header('Location: friends.php?cancelled=1');exit;
   }elseif($action==='unfriend'){
    $target=filter_var($_POST['member_id']??null,FILTER_VALIDATE_INT);
    if($target){$q=$pdo->prepare("DELETE FROM friend_requests WHERE status='accepted' AND ((requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?))");$q->execute([$currentId,$target,$target,$currentId]);}
    header('Location: friends.php?removed=1');exit;
   }
  }catch(PDOException $ex){$error='Chưa cài đặt dữ liệu kết bạn. Hãy chạy database/social_diary.sql trong phpMyAdmin.';}
 }
}
if(isset($_GET['profile']))$notice='Hồ sơ cung hoàng đạo đã được cập nhật.';
if(isset($_GET['sent']))$notice='Đã gửi lời mời kết bạn.';
if(isset($_GET['accepted']))$notice='Hai bạn đã trở thành bạn bè!';
if(isset($_GET['rejected']))$notice='Đã từ chối lời mời.';
if(isset($_GET['cancelled']))$notice='Đã hủy lời mời kết bạn.';
if(isset($_GET['removed']))$notice='Đã xóa bạn bè khỏi danh sách.';
$meQ=$pdo->prepare('SELECT p.zodiac_sign,p.bio FROM members m LEFT JOIN member_profiles p ON p.member_id=m.id WHERE m.id=?');$meQ->execute([$currentId]);$mine=$meQ->fetch()?:['zodiac_sign'=>null,'bio'=>''];
$filter=filter_var($_GET['sign']??0,FILTER_VALIDATE_INT);if(!$filter||!isset($signs[$filter]))$filter=0;
$search=trim((string)($_GET['q']??''));$sqlWhere='m.id<>? AND m.is_active=1';$params=[$currentId];
if($filter){$sqlWhere.=' AND p.zodiac_sign=?';$params[]=$filter;}
if($search!==''){$sqlWhere.=' AND m.display_name LIKE ?';$params[]='%'.$search.'%';}
$find=$pdo->prepare("SELECT m.id,m.display_name,p.zodiac_sign,p.bio,m.last_seen_at,(SELECT id FROM friend_requests fr WHERE fr.requester_id=? AND fr.addressee_id=m.id AND fr.status='pending' LIMIT 1) AS outgoing,(SELECT id FROM friend_requests fr WHERE fr.requester_id=m.id AND fr.addressee_id=? AND fr.status='pending' LIMIT 1) AS incoming,(SELECT COUNT(*) FROM friend_requests fr WHERE fr.status='accepted' AND ((fr.requester_id=? AND fr.addressee_id=m.id) OR (fr.requester_id=m.id AND fr.addressee_id=?))) AS is_friend FROM members m LEFT JOIN member_profiles p ON p.member_id=m.id WHERE $sqlWhere ORDER BY m.created_at DESC LIMIT 60");
$find->execute(array_merge([$currentId,$currentId,$currentId,$currentId],$params));$people=$find->fetchAll();
$inQ=$pdo->prepare("SELECT fr.id,fr.created_at,m.id AS member_id,m.display_name,p.zodiac_sign FROM friend_requests fr JOIN members m ON m.id=fr.requester_id LEFT JOIN member_profiles p ON p.member_id=m.id WHERE fr.addressee_id=? AND fr.status='pending' ORDER BY fr.created_at DESC");$inQ->execute([$currentId]);$incoming=$inQ->fetchAll();
$outQ=$pdo->prepare("SELECT fr.id,fr.created_at,m.id AS member_id,m.display_name,p.zodiac_sign FROM friend_requests fr JOIN members m ON m.id=fr.addressee_id LEFT JOIN member_profiles p ON p.member_id=m.id WHERE fr.requester_id=? AND fr.status='pending' ORDER BY fr.created_at DESC");$outQ->execute([$currentId]);$outgoing=$outQ->fetchAll();
$friendQ=$pdo->prepare("SELECT m.id,m.display_name,p.zodiac_sign,m.last_seen_at FROM friend_requests fr JOIN members m ON m.id=IF(fr.requester_id=?,fr.addressee_id,fr.requester_id) LEFT JOIN member_profiles p ON p.member_id=m.id WHERE fr.status='accepted' AND (fr.requester_id=? OR fr.addressee_id=?) ORDER BY m.display_name");$friendQ->execute([$currentId,$currentId,$currentId]);$friends=$friendQ->fetchAll();
function fr_h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function fr_label($n,$signs,$symbols):string{$i=(int)$n;return isset($signs[$i])?$symbols[$i].' '.$signs[$i]:'Chưa chọn cung';}
function fr_online(?string $seen):bool{return $seen!==null && strtotime($seen)!==false && strtotime($seen)>=time()-300;}
function fr_activity(?string $seen):string{if(fr_online($seen))return 'Đang online';if(!$seen)return 'Chưa hoạt động';$d=max(0,time()-strtotime($seen));if($d<3600)return 'Hoạt động '.max(1,(int)floor($d/60)).' phút trước';if($d<86400)return 'Hoạt động '.(int)floor($d/3600).' giờ trước';return 'Hoạt động '.(int)floor($d/86400).' ngày trước';}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kết bạn theo cung | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=socialdiary1"></head>
<body class="blog-page social-page"><header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="community.php">Cộng đồng</a><a href="diary.php">Nhật ký</a><a href="messages.php">Tin nhắn</a><a href="account.php">Tài khoản</a></nav></div></header>
<main class="social-main"><section class="social-hero"><span class="compat-kicker">✦ NHỮNG TÂM HỒN CÙNG BẦU TRỜI ✦</span><h1>Kết bạn <em>theo cung</em></h1><p>Tìm những người cùng cung, khám phá cá tính và gửi lời chào làm quen.</p></section>
<?php if($notice):?><p class="social-notice"><?=fr_h($notice)?></p><?php endif;?><?php if($error):?><p class="community-error"><?=fr_h($error)?></p><?php endif;?>
<section class="social-card"><h2>Hồ sơ chiêm tinh của bạn</h2><form method="post" class="social-profile-form"><input type="hidden" name="csrf" value="<?=fr_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="save_profile"><label>Cung hoàng đạo<select name="zodiac_sign" required><option value="">Chọn cung của bạn</option><?php foreach($signs as $i=>$name):?><option value="<?=$i?>" <?=((int)$mine['zodiac_sign']===$i?'selected':'')?>><?=fr_h($symbols[$i].' '.$name)?></option><?php endforeach;?></select></label><label>Giới thiệu bản thân<textarea name="bio" maxlength="500" rows="3" placeholder="Một chút về bạn..."><?=fr_h($mine['bio'])?></textarea></label><button class="social-button" type="submit">Lưu hồ sơ ✦</button></form></section>
<section class="social-card"><div class="social-section-title"><h2>Lời mời kết bạn</h2><span><?=count($incoming)?> đang chờ</span></div><?php if(!$incoming):?><p class="social-muted">Chưa có lời mời mới.</p><?php else:foreach($incoming as $r):?><div class="social-person"><div><strong><a class="social-name-link" href="member.php?id=<?=(int)$r['member_id']?>"><?=fr_h($r['display_name'])?></a></strong><small><?=fr_h(fr_label($r['zodiac_sign'],$signs,$symbols))?></small></div><div class="social-actions"><form method="post"><input type="hidden" name="csrf" value="<?=fr_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="respond"><input type="hidden" name="request_id" value="<?=(int)$r['id']?>"><input type="hidden" name="choice" value="accepted"><button class="social-button" type="submit">Chấp nhận</button></form><form method="post"><input type="hidden" name="csrf" value="<?=fr_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="respond"><input type="hidden" name="request_id" value="<?=(int)$r['id']?>"><input type="hidden" name="choice" value="rejected"><button class="social-quiet" type="submit">Từ chối</button></form></div></div><?php endforeach;endif;?></section>
<section class="social-card"><div class="social-section-title"><h2>Bạn bè của tôi</h2><span><?=count($friends)?> người</span></div><?php if(!$friends):?><p class="social-muted">Bạn chưa có bạn bè. Hãy tìm người cùng cung bên dưới nhé.</p><?php else:foreach($friends as $p):?><div class="social-person"><div><strong><a class="social-name-link" href="member.php?id=<?=(int)$p['id']?>"><?=fr_h($p['display_name'])?></a></strong><small><?=fr_h(fr_label($p['zodiac_sign'],$signs,$symbols))?></small></div><form method="post" onsubmit="return confirm('Bạn muốn xóa người này khỏi danh sách bạn bè?')"><input type="hidden" name="csrf" value="<?=fr_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="unfriend"><input type="hidden" name="member_id" value="<?=(int)$p['id']?>"><button class="social-quiet" type="submit">Hủy kết bạn</button></form></div><?php endforeach;endif;?></section>
<section class="social-card"><div class="social-section-title"><h2>Khám phá thành viên</h2><span><?=count($people)?> kết quả</span></div><form method="get" class="social-search"><input name="q" value="<?=fr_h($search)?>" placeholder="Tìm theo tên..."><select name="sign"><option value="0">Tất cả cung</option><?php foreach($signs as $i=>$name):?><option value="<?=$i?>" <?=($filter===$i?'selected':'')?>><?=fr_h($name)?></option><?php endforeach;?></select><button class="social-button" type="submit">Tìm kiếm</button></form>
<?php if(!$people):?><p class="social-muted">Chưa tìm thấy thành viên phù hợp.</p><?php else:foreach($people as $p):?><div class="social-person"><div><strong><a class="social-name-link" href="member.php?id=<?=(int)$p['id']?>"><?=fr_h($p['display_name'])?></a></strong><small><?=fr_h(fr_label($p['zodiac_sign'],$signs,$symbols))?> · <span class="<?=fr_online($p['last_seen_at'])?'presence-online':'presence-offline'?>"><?=fr_h(fr_activity($p['last_seen_at']))?></span><br><?=fr_h($p['bio'])?></small></div><?php if((int)$p['is_friend']):?><span class="social-status">♥ Bạn bè</span><?php elseif($p['outgoing']):?><span class="social-status">Đã gửi lời mời</span><?php elseif($p['incoming']):?><span class="social-status">Đã gửi lời mời cho bạn</span><?php else:?><form method="post"><input type="hidden" name="csrf" value="<?=fr_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="send_request"><input type="hidden" name="member_id" value="<?=(int)$p['id']?>"><button class="social-button" type="submit">+ Kết bạn</button></form><?php endif;?></div><?php endforeach;endif;?></section>
<section class="social-card"><h2>Lời mời bạn đã gửi</h2><?php if(!$outgoing):?><p class="social-muted">Không có lời mời đang chờ.</p><?php else:foreach($outgoing as $r):?><div class="social-person"><div><strong><?=fr_h($r['display_name'])?></strong><small><?=fr_h(fr_label($r['zodiac_sign'],$signs,$symbols))?></small></div><form method="post"><input type="hidden" name="csrf" value="<?=fr_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="request_id" value="<?=(int)$r['id']?>"><button class="social-quiet" type="submit">Hủy lời mời</button></form></div><?php endforeach;endif;?></section>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Góc Hoàng Đạo · Kết nối dưới bầu trời</div></footer><script src="js/presence.js"></script></body></html>
