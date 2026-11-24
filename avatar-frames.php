<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/avatar-frames.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php'); exit; }
$memberId=(int)$_SESSION['member_id'];
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=(string)$_SESSION['member_csrf'];$error='';$notice='';
function af_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??'')))$error='Phiên đã hết hạn. Hãy tải lại trang.';
 else{
  $action=(string)($_POST['action']??'');
  try{
   if($action==='equip'){
    $frameId=filter_var($_POST['frame_id']??null,FILTER_VALIDATE_INT);
    if(!$frameId||$frameId<1)$error='Khung avatar không hợp lệ.';
    else{
     $q=$pdo->prepare("SELECT f.id FROM avatar_frames f JOIN member_avatar_frames o ON o.frame_id=f.id AND o.member_id=? WHERE f.id=? AND f.is_active=1 LIMIT 1");
     $q->execute([$memberId,$frameId]);
     if(!$q->fetchColumn())$error='Bạn chưa sở hữu khung này hoặc khung đã ngừng hoạt động.';
     else{$pdo->prepare('INSERT INTO member_avatar_equipped(member_id,frame_id) VALUES(?,?) ON DUPLICATE KEY UPDATE frame_id=VALUES(frame_id),equipped_at=CURRENT_TIMESTAMP')->execute([$memberId,$frameId]);$notice='Đã trang bị khung avatar.';}
    }
   }elseif($action==='unequip'){
    $pdo->prepare('DELETE FROM member_avatar_equipped WHERE member_id=?')->execute([$memberId]);$notice='Đã tháo khung avatar.';
   }else $error='Thao tác không hợp lệ.';
  }catch(Throwable $e){$error='Chưa cài đặt kho khung avatar. Hãy chạy database/avatar_frames.sql trong phpMyAdmin.';}
 }
}
$frames=[];$equippedId=0;$setupError=false;
try{
 $q=$pdo->prepare("SELECT f.id,f.name,f.image_url,f.rarity,f.description,f.is_active,e.frame_id AS equipped_id FROM member_avatar_frames o JOIN avatar_frames f ON f.id=o.frame_id LEFT JOIN member_avatar_equipped e ON e.member_id=o.member_id AND e.frame_id=f.id WHERE o.member_id=? ORDER BY (e.frame_id IS NOT NULL) DESC,f.sort_order,f.id DESC");
 $q->execute([$memberId]);$frames=$q->fetchAll(PDO::FETCH_ASSOC);
 foreach($frames as $f)if(!empty($f['equipped_id']))$equippedId=(int)$f['id'];
}catch(Throwable $e){$setupError=true;}
$memberAvatar='';$memberName='Thành viên';
try{$q=$pdo->prepare('SELECT display_name,avatar_url FROM members WHERE id=?');$q->execute([$memberId]);$m=$q->fetch(PDO::FETCH_ASSOC)?:[];$memberAvatar=(string)($m['avatar_url']??'');$memberName=(string)($m['display_name']??$memberName);}catch(Throwable $e){}
$rarityNames=['common'=>'Thường','rare'=>'Hiếm','epic'=>'Sử thi','legendary'=>'Huyền thoại','mythic'=>'Thần thoại'];
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kho khung avatar | Truyện Đêm</title><link rel="stylesheet" href="/css/style.css"><link rel="stylesheet" href="/css/member-hub.css"><link rel="stylesheet" href="/css/theme-mode.css"><link rel="stylesheet" href="/css/avatar-frames.css?v=1"><style>
.af-main{width:min(100% - 24px,980px);margin:26px auto 90px;color:var(--text,#eee)}.af-hero,.af-panel{padding:20px;border:1px solid var(--border,#40304f);border-radius:17px;background:var(--surface,#17141e);margin-bottom:16px}.af-hero{display:flex;align-items:center;gap:18px;flex-wrap:wrap}.af-hero h1{margin:0 0 5px;font-size:1.5rem}.af-hero p{margin:0;color:var(--muted,#aaa)}.af-current{width:82px;height:82px}.af-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:13px}.af-card{padding:16px;border:1px solid var(--border,#40304f);border-radius:14px;background:var(--card,#201a2a);text-align:center}.af-preview{width:82px;height:82px;margin:4px auto 12px}.af-card h3{margin:4px 0;font-size:1rem}.af-card p{min-height:36px;color:var(--muted,#aaa);font-size:.8rem}.af-rarity{display:inline-block;padding:4px 9px;border-radius:20px;background:#30263d;color:#d9c0f2;font-size:.72rem}.af-card button,.af-button{border:0;border-radius:9px;padding:9px 13px;background:#8052a8;color:#fff;font-weight:700;cursor:pointer}.af-card button:disabled{opacity:.5}.af-message{padding:12px 14px;border-radius:10px;background:#1c332b;color:#b9efd0;margin:12px 0}.af-error{background:#3b2529;color:#ffc4c4}.af-empty{text-align:center;padding:30px 15px;color:var(--muted,#aaa)}@media(max-width:520px){.af-main{margin-top:16px}.af-hero,.af-panel{padding:15px}.af-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.af-card{padding:12px}.af-preview{width:70px;height:70px}}
</style></head><body class="story-site"><?php require __DIR__.'/includes/header.php';?><main class="af-main"><section class="af-hero"><?=td_avatar_html($pdo,$memberId,$memberAvatar,mb_substr($memberName,0,1,'UTF-8'),'af-current')?><div><span>✦ BỘ SƯU TẬP CÁ NHÂN</span><h1>Kho khung avatar</h1><p>Chọn khung đã được cấp để làm nổi bật hồ sơ của bạn.</p><p><a href="/account.php">Quay lại tài khoản</a></p></div></section>
<?php if($notice):?><div class="af-message"><?=af_h($notice)?></div><?php endif;?><?php if($error):?><div class="af-message af-error"><?=af_h($error)?></div><?php endif;?>
<section class="af-panel"><h2>Khung bạn sở hữu</h2><?php if($setupError):?><div class="af-empty">Kho khung avatar chưa được cài đặt. Hãy liên hệ quản trị viên.</div><?php elseif(!$frames):?><div class="af-empty">Bạn chưa được cấp khung avatar nào. Hãy tham gia sự kiện hoặc chờ quản trị viên trao tặng nhé.</div><?php else:?><div class="af-grid"><?php foreach($frames as $f):?><article class="af-card"><div class="af-preview"><span class="td-avatar-shell td-frame-<?=af_h($f['rarity'])?>" style="width:100%;height:100%"><span class="td-avatar-photo"><?php if($memberAvatar!==''):?><img src="<?=af_h($memberAvatar)?>" alt=""><?php else:?><?=af_h(mb_substr($memberName,0,1,'UTF-8'))?><?php endif;?></span><img class="td-avatar-frame-image" src="<?=af_h($f['image_url'])?>" alt="<?=af_h($f['name'])?>"></span></div><h3><?=af_h($f['name'])?></h3><span class="af-rarity"><?=af_h($rarityNames[$f['rarity']]??'Đặc biệt')?></span><p><?=af_h($f['description']?:'Khung avatar dành riêng cho bạn.')?></p><?php if((int)$f['id']===$equippedId):?><form method="post"><input type="hidden" name="csrf" value="<?=af_h($csrf)?>"><input type="hidden" name="action" value="unequip"><button type="submit">Đang sử dụng · Tháo khung</button></form><?php elseif((int)$f['is_active']===1):?><form method="post"><input type="hidden" name="csrf" value="<?=af_h($csrf)?>"><input type="hidden" name="action" value="equip"><input type="hidden" name="frame_id" value="<?=(int)$f['id']?>"><button type="submit">Trang bị</button></form><?php else:?><button type="button" disabled>Đang tạm khóa</button><?php endif;?></article><?php endforeach;?></div><?php endif;?></section></main><?php require __DIR__.'/includes/footer.php';?></body></html>
