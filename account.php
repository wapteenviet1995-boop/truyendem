<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/avatar-frames.php';
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
$signId=(int)($member['zodiac_sign']??0);$avatarCropFrame=td_avatar_frame_data($pdo,$currentId);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hồ sơ của tôi | Truyện Đêm</title><link rel="stylesheet" href="css/style.css?v=20261111-account1"><link rel="stylesheet" href="css/account.css?v=20261126-profile-settings1"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261122-footer-account-fix1">
<link rel="stylesheet" href="/css/avatar-frames.css?v=20261125-fix1">
</head>
<body class="blog-page social-page account-page">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="account-main"><section class="account-hero"><span class="compat-kicker">✦ HỒ SƠ CỦA BẠN ✦</span><?=td_avatar_html($pdo,$currentId,(string)($member['avatar_url']??''),mb_substr((string)$member['display_name'],0,1,'UTF-8'),'account-avatar')?><h1><?=acc_h($member['display_name'])?></h1><p><?=isset($signs[$signId])?acc_h(($symbols[$signId]??'').' '.$signs[$signId]):'Chưa chọn cung hoàng đạo'?></p><small>Thành viên từ <?=acc_h(date('d/m/Y',strtotime($member['created_at'])))?></small><p><a class="account-button" href="member.php?id=<?=$currentId?>">Xem hồ sơ công khai của tôi</a></p></section>
<?php if($notice):?><p class="account-flash"><?=acc_h($notice)?></p><?php endif;?><?php if($error):?><p class="community-error"><?=acc_h($error)?></p><?php endif;?>
<section class="account-stats"><?php foreach(['friends'=>'Bạn bè','followers'=>'Người theo dõi','posts'=>'Bài cộng đồng','likes'=>'Lượt thích','diary'=>'Trang nhật ký'] as $key=>$label):?><div><strong><?=$stats[$key]??0?></strong><span><?=acc_h($label)?></span></div><?php endforeach;?></section>
<section class="account-notices"><a href="friends.php">✉ <?=$pending?> lời mời kết bạn</a><a href="messages.php">☾ <?=$unread?> tin nhắn chưa đọc</a></section>
<section class="account-card account-about"><h2>✦ Thông tin thành viên</h2><dl class="account-details"><div><dt>Ngày sinh</dt><dd><?=!empty($member['birth_date'])?acc_h(date('d/m/Y',strtotime($member['birth_date']))):'Chưa cập nhật'?></dd></div><div><dt>Giới thiệu</dt><dd><?=trim((string)($member['bio']??''))!==''?nl2br(acc_h((string)$member['bio'])):'Chưa có lời giới thiệu.'?></dd></div><div><dt>Tham gia</dt><dd><?=acc_h(date('d/m/Y',strtotime($member['created_at'])))?></dd></div></dl><a class="account-button" href="profile-settings.php">⚙ Cài đặt hồ sơ</a></section></div><div class="account-links"><a href="shop.php">✦ Ví Xu & Cửa hàng</a><a href="friends.php">Danh sách bạn bè</a><a href="messages.php">Tin nhắn riêng</a><a href="diary.php">Nhật ký của tôi</a><a href="community.php">Cộng đồng</a></div>
<form method="post" action="logout.php" class="account-logout"><input type="hidden" name="csrf" value="<?=acc_h($csrf)?>"><button>Đăng xuất</button></form>
</main><?php require __DIR__ . '/includes/footer.php'; ?><script src="js/presence.js"></script><script>
(function(){
 const form=document.getElementById('avatarCropForm'),input=document.getElementById('avatarSource'),canvas=document.getElementById('avatarCropCanvas'),zoom=document.getElementById('avatarZoom'),reset=document.getElementById('avatarCropReset'),data=document.getElementById('avatarCroppedData'),hint=document.getElementById('avatarCropHint');
 if(!form||!input||!canvas)return;
 const ctx=canvas.getContext('2d');let image=null,scale=1,offsetX=0,offsetY=0,baseScale=1,drag=null;
 const size=320;
 function draw(){
  ctx.clearRect(0,0,size,size);ctx.fillStyle='#171321';ctx.fillRect(0,0,size,size);
  if(!image)return;
  const w=image.naturalWidth,h=image.naturalHeight;
  baseScale=Math.max(size/w,size/h);
  const s=baseScale*scale,drawW=w*s,drawH=h*s;
  const maxX=Math.max(0,(drawW-size)/2),maxY=Math.max(0,(drawH-size)/2);
  offsetX=Math.max(-maxX,Math.min(maxX,offsetX));offsetY=Math.max(-maxY,Math.min(maxY,offsetY));
  ctx.save();ctx.beginPath();ctx.rect(0,0,size,size);ctx.clip();
  ctx.drawImage(image,(size-drawW)/2+offsetX,(size-drawH)/2+offsetY,drawW,drawH);ctx.restore();
 }
 input.addEventListener('change',()=>{
  const file=input.files&&input.files[0];data.value='';
  if(!file){image=null;draw();return;}
  if(!/^image\/(jpeg|png|webp)$/.test(file.type)||file.size>2*1024*1024){hint.textContent='Chỉ nhận ảnh JPG, PNG hoặc WEBP tối đa 2 MB.';input.value='';image=null;draw();return;}
  const reader=new FileReader();reader.onload=()=>{
   const img=new Image();img.onload=()=>{image=img;scale=1;offsetX=0;offsetY=0;zoom.value='1';zoom.disabled=false;reset.disabled=false;hint.textContent='Kéo ảnh trong khung để đưa khuôn mặt vào giữa.';draw();};img.onerror=()=>{hint.textContent='Không thể đọc ảnh này.';};img.src=reader.result;
  };reader.readAsDataURL(file);
 });
 zoom.addEventListener('input',()=>{if(!image)return;scale=Number(zoom.value)||1;draw();});
 reset.addEventListener('click',()=>{scale=1;offsetX=0;offsetY=0;zoom.value='1';draw();});
 canvas.addEventListener('pointerdown',e=>{if(!image)return;drag={x:e.clientX,y:e.clientY,ox:offsetX,oy:offsetY};canvas.setPointerCapture(e.pointerId);});
 canvas.addEventListener('pointermove',e=>{if(!drag)return;const rect=canvas.getBoundingClientRect();const factor=size/rect.width;offsetX=drag.ox+(e.clientX-drag.x)*factor;offsetY=drag.oy+(e.clientY-drag.y)*factor;draw();});
 ['pointerup','pointercancel','lostpointercapture'].forEach(name=>canvas.addEventListener(name,()=>{drag=null;}));
 form.addEventListener('submit',e=>{
  if(!image)return;
  e.preventDefault();draw();const out=document.createElement('canvas');out.width=512;out.height=512;const outCtx=out.getContext('2d');outCtx.drawImage(canvas,0,0,size,size,0,0,512,512);
  data.value=out.toDataURL('image/jpeg',0.9);form.submit();
 });
 draw();
})();
</script></body></html>
