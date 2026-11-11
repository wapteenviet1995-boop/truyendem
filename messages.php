<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php'); exit; }
$currentId = (int)$_SESSION['member_id'];
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
function msg_h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function msg_len(string $s): int { return preg_match_all('/./us', $s, $m) ?: 0; }
function msg_is_friend(PDO $pdo, int $a, int $b): bool {
    $q=$pdo->prepare("SELECT id FROM friend_requests WHERE status='accepted' AND ((requester_id=? AND addressee_id=?) OR (requester_id=? AND addressee_id=?)) LIMIT 1");
    $q->execute([$a,$b,$b,$a]); return (bool)$q->fetchColumn();
}
try {
    $pdo->prepare('UPDATE members SET last_seen_at=NOW() WHERE id=?')->execute([$currentId]);
} catch (PDOException $e) {}
$target = filter_input(INPUT_GET, 'with', FILTER_VALIDATE_INT);
if (isset($_GET['fetch']) && $target && $target > 0) {
    header('Content-Type: application/json; charset=utf-8');
    if (!msg_is_friend($pdo,$currentId,$target)) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
    $pdo->prepare('UPDATE member_messages SET read_at=NOW() WHERE sender_id=? AND recipient_id=? AND read_at IS NULL')->execute([$target,$currentId]);
    $q=$pdo->prepare('SELECT id,sender_id,recipient_id,message,DATE_FORMAT(created_at,"%H:%i") AS time_label FROM member_messages WHERE (sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?) ORDER BY id DESC LIMIT 100');
    $q->execute([$currentId,$target,$target,$currentId]);
    echo json_encode(['ok'=>true,'messages'=>array_reverse($q->fetchAll())], JSON_UNESCAPED_UNICODE);
    exit;
}
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['member_csrf'], (string)($_POST['csrf']??''))) $error='Phiên đã hết hạn. Hãy tải lại trang.';
    else {
        $to=filter_var($_POST['recipient_id']??null,FILTER_VALIDATE_INT);
        $body=trim((string)($_POST['message']??''));
        if (!$to || $to<1 || $to===$currentId || !msg_is_friend($pdo,$currentId,(int)$to)) $error='Bạn chỉ có thể nhắn tin riêng với bạn bè.';
        elseif ($body==='' || msg_len($body)>2000) $error='Tin nhắn cần có nội dung và tối đa 2.000 ký tự.';
        else {
            $q=$pdo->prepare('INSERT INTO member_messages(sender_id,recipient_id,message) VALUES(?,?,?)');
            $q->execute([$currentId,$to,$body]);
            header('Location: messages.php?with='.(int)$to); exit;
        }
    }
}
$friendsQ=$pdo->prepare("SELECT m.id,m.display_name,p.zodiac_sign,m.last_seen_at,(SELECT COUNT(*) FROM member_messages mm WHERE mm.sender_id=m.id AND mm.recipient_id=? AND mm.read_at IS NULL) AS unread FROM friend_requests fr JOIN members m ON m.id=IF(fr.requester_id=?,fr.addressee_id,fr.requester_id) LEFT JOIN member_profiles p ON p.member_id=m.id WHERE fr.status='accepted' AND (fr.requester_id=? OR fr.addressee_id=?) AND m.is_active=1 ORDER BY m.display_name");
$friendsQ->execute([$currentId,$currentId,$currentId,$currentId]); $friends=$friendsQ->fetchAll();
$person=null; $chat=[];
if ($target && $target>0 && $target!==$currentId) {
    if (!msg_is_friend($pdo,$currentId,$target)) { $error='Hai bạn cần là bạn bè trước khi gửi tin nhắn riêng.'; $target=null; }
    else {
        $q=$pdo->prepare('SELECT id,display_name,last_seen_at FROM members WHERE id=? AND is_active=1'); $q->execute([$target]); $person=$q->fetch();
        if (!$person) { $error='Không tìm thấy thành viên.'; $target=null; }
        else {
            $pdo->prepare('UPDATE member_messages SET read_at=NOW() WHERE sender_id=? AND recipient_id=? AND read_at IS NULL')->execute([$target,$currentId]);
            $q=$pdo->prepare('SELECT id,sender_id,message,created_at FROM member_messages WHERE (sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?) ORDER BY id DESC LIMIT 100');
            $q->execute([$currentId,$target,$target,$currentId]); $chat=array_reverse($q->fetchAll());
        }
    }
}
function msg_presence_label(?string $lastSeen): string {
    if (!$lastSeen) return 'Chưa hoạt động';
    $ts=strtotime($lastSeen); if (!$ts) return 'Chưa rõ trạng thái';
    $diff=max(0,time()-$ts);
    if ($diff<=300) return 'Đang online';
    if ($diff<3600) return 'Hoạt động '.max(1,(int)floor($diff/60)).' phút trước';
    if ($diff<86400) return 'Hoạt động '.(int)floor($diff/3600).' giờ trước';
    return 'Hoạt động '.(int)floor($diff/86400).' ngày trước';
}
$unreadQ=$pdo->prepare('SELECT COUNT(*) FROM member_messages WHERE recipient_id=? AND read_at IS NULL');$unreadQ->execute([$currentId]);$totalUnread=(int)$unreadQ->fetchColumn();
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tin nhắn riêng | Truyện Đêm</title><link rel="stylesheet" href="css/style.css?v=messaging1"></head>
<body class="blog-page social-page"><header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="friends.php">Kết bạn</a><a href="community.php">Cộng đồng</a><a href="account.php">Tài khoản</a></nav></div></header>
<main class="social-main messages-main"><section class="social-hero"><span class="compat-kicker">✦ KẾT NỐI RIÊNG TƯ ✦</span><h1>Tin nhắn <em>riêng</em></h1><p>Trò chuyện thân mật cùng những người bạn dưới bầu trời hoàng đạo.</p></section>
<?php if($error):?><p class="community-error"><?=msg_h($error)?></p><?php endif;?>
<?php if($person):?>
<section class="social-card chat-card"><div class="chat-heading"><div><h2><?=msg_h($person['display_name'])?></h2><small class="<?=((time()-strtotime((string)$person['last_seen_at'])<=300 && $person['last_seen_at'])?'is-online':'')?>"><?=msg_h(msg_presence_label($person['last_seen_at']))?></small></div><a class="social-quiet-link" href="member.php?id=<?=(int)$person['id']?>">Xem hồ sơ</a></div>
<div class="chat-messages" id="chat-messages" data-current="<?= $currentId ?>" data-target="<?=(int)$person['id']?>"><?php if(!$chat):?><p class="chat-empty">Hãy gửi lời chào đầu tiên nhé ✨</p><?php else:foreach($chat as $m):?><div class="chat-bubble <?=((int)$m['sender_id']===$currentId?'mine':'theirs')?>"><p><?=nl2br(msg_h($m['message']))?></p><small><?=msg_h(date('d/m H:i',strtotime($m['created_at'])))?></small></div><?php endforeach;endif;?></div>
<form method="post" class="chat-compose"><input type="hidden" name="csrf" value="<?=msg_h($_SESSION['member_csrf'])?>"><input type="hidden" name="recipient_id" value="<?=(int)$person['id']?>"><textarea name="message" maxlength="8000" rows="2" required placeholder="Viết tin nhắn..."></textarea><button class="social-button" type="submit">Gửi tin ✦</button></form></section>
<?php else:?>
<section class="social-card"><div class="social-section-title"><h2>Bạn bè của tôi</h2><span><?=$totalUnread?> tin chưa đọc</span></div><?php if(!$friends):?><p class="social-muted">Bạn chưa có bạn bè. Hãy kết bạn trước để bắt đầu trò chuyện.</p><a class="social-button" href="friends.php">Tìm bạn bè</a><?php else:foreach($friends as $f):?><a class="chat-friend" href="messages.php?with=<?=(int)$f['id']?>"><span class="chat-avatar"><?=msg_h((preg_match('/^./us',(string)$f['display_name'],$first)?$first[0]:'✦'))?></span><span class="chat-friend-info"><strong><?=msg_h($f['display_name'])?></strong><small><?=msg_h(msg_presence_label($f['last_seen_at']))?></small></span><?php if((int)$f['unread']>0):?><b class="chat-unread"><?=(int)$f['unread']?></b><?php endif;?></a><?php endforeach;endif;?></section>
<?php endif;?>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm · Tin nhắn riêng</div></footer>
<script src="js/presence.js"></script>
<?php if($person):?><script>
(function(){
 const box=document.getElementById('chat-messages'); if(!box)return;
 const current=Number(box.dataset.current), target=Number(box.dataset.target);
 let lastId=0; box.querySelectorAll('.chat-bubble').forEach(el=>{const t=el.dataset.id;if(t)lastId=Math.max(lastId,Number(t));});
 async function refresh(){
  try{
   const r=await fetch('messages.php?with='+target+'&fetch=1',{cache:'no-store',credentials:'same-origin'});
   if(!r.ok)return; const data=await r.json(); if(!data.ok)return;
   box.replaceChildren();
   if(!data.messages.length){const p=document.createElement('p');p.className='chat-empty';p.textContent='Hãy gửi lời chào đầu tiên nhé ✨';box.append(p);}
   data.messages.forEach(m=>{
    const item=document.createElement('div');item.className='chat-bubble '+(Number(m.sender_id)===current?'mine':'theirs');
    const p=document.createElement('p');p.textContent=m.message;
    const small=document.createElement('small');small.textContent=m.time_label;
    item.append(p,small);box.append(item);
   });
   box.scrollTop=box.scrollHeight;
  }catch(e){}
 }
 box.scrollTop=box.scrollHeight; setInterval(refresh,5000);
})();
</script><?php endif;?>
</body></html>
