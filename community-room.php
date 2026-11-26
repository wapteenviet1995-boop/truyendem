<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php?next=community-room.php'); exit; }
$me=(int)$_SESSION['member_id'];
if(empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=(string)$_SESSION['member_csrf'];
function cchat_h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function cchat_json(array $data,int $status=200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE); exit; }
if(isset($_GET['fetch'])) {
  $after=max(0,(int)($_GET['after']??0));
  $q=$pdo->prepare("SELECT c.id,c.member_id,c.message,DATE_FORMAT(c.created_at,'%d/%m %H:%i') AS time_label,m.display_name,m.avatar_url FROM community_chat_messages c JOIN members m ON m.id=c.member_id WHERE c.id>? AND c.deleted_at IS NULL AND m.is_active=1 ORDER BY c.id ASC LIMIT 80");
  $q->execute([$after]); cchat_json(['ok'=>true,'messages'=>$q->fetchAll()]);
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
  if(!hash_equals($csrf,(string)($_POST['csrf']??''))) $error='Phiên đã hết hạn. Hãy tải lại trang.';
  else {
    $body=trim((string)($_POST['message']??''));
    if($body==='' || mb_strlen($body)>1000) $error='Tin nhắn cần có từ 1 đến 1.000 ký tự.';
    else {
      $q=$pdo->prepare('SELECT COUNT(*) FROM community_chat_messages WHERE member_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 1 MINUTE)');
      $q->execute([$me]);
      if((int)$q->fetchColumn()>=8) $error='Bạn gửi hơi nhanh. Vui lòng chờ một chút rồi thử lại.';
      else {
        $q=$pdo->prepare('INSERT INTO community_chat_messages(member_id,message) VALUES(?,?)');
        $q->execute([$me,$body]); header('Location: community-room.php'); exit;
      }
    }
  }
}
$q=$pdo->query("SELECT c.id,c.member_id,c.message,c.created_at,m.display_name,m.avatar_url FROM community_chat_messages c JOIN members m ON m.id=c.member_id WHERE c.deleted_at IS NULL AND m.is_active=1 ORDER BY c.id DESC LIMIT 80");
$messages=array_reverse($q->fetchAll());
$lastId=$messages?(int)end($messages)['id']:0;
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Phòng trò chuyện | Truyện Đêm</title>
<link rel="stylesheet" href="/css/style.css?v=messaging2"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/theme-mode.css?v=20261122-global-light-audit5"><link rel="stylesheet" href="/css/community-room.css?v=1"></head>
<body class="blog-page community-chat-page"><?php require __DIR__.'/includes/header.php'; ?>
<main class="cchat-main"><section class="cchat-hero"><span>✦ KẾT NỐI CÙNG ĐỘC GIẢ ✦</span><h1>Phòng trò chuyện</h1><p>Cùng giao lưu, chia sẻ cảm nhận và trò chuyện với mọi người trong Truyện Đêm.</p></section>
<?php if($error):?><p class="cchat-error"><?=cchat_h($error)?></p><?php endif;?>
<section class="cchat-panel"><div class="cchat-top"><div><strong>☾ Phòng chung Truyện Đêm</strong><small id="cchat-status">Đang kết nối…</small></div><span class="cchat-online">● Trò chuyện cộng đồng</span></div>
<div class="cchat-stream" id="cchat-stream" data-last-id="<?=$lastId?>" data-me="<?=$me?>">
<?php if(!$messages):?><p class="cchat-empty">Chưa có tin nhắn nào. Hãy mở lời đầu tiên nhé!</p><?php else:foreach($messages as $m):?><article class="cchat-message <?=((int)$m['member_id']===$me?'mine':'')?> " data-id="<?=(int)$m['id']?>"><div class="cchat-avatar"><?php if(!empty($m['avatar_url'])):?><img src="<?=cchat_h($m['avatar_url'])?>" alt=""><?php else:?><?=cchat_h(mb_substr((string)$m['display_name'],0,1))?><?php endif;?></div><div class="cchat-content"><div class="cchat-meta"><a href="/member.php?id=<?=(int)$m['member_id']?>"><?=cchat_h($m['display_name'])?></a><time><?=cchat_h(date('d/m H:i',strtotime($m['created_at'])))?></time></div><p><?=nl2br(cchat_h($m['message']))?></p></div></article><?php endforeach;endif;?>
</div><form class="cchat-compose" method="post"><input type="hidden" name="csrf" value="<?=cchat_h($csrf)?>"><textarea name="message" maxlength="1000" rows="2" required placeholder="Chia sẻ điều bạn đang nghĩ…"></textarea><button type="submit">Gửi tin nhắn ✦</button></form><p class="cchat-note">Vui lòng trò chuyện văn minh, không spam hoặc chia sẻ thông tin riêng tư.</p></section></main>
<?php require __DIR__.'/includes/footer.php'; ?>
<script>
(function(){
 const box=document.getElementById('cchat-stream'), status=document.getElementById('cchat-status'); if(!box)return;
 let last=Number(box.dataset.lastId||0), busy=false;
 function add(m){
  const item=document.createElement('article');item.className='cchat-message '+(Number(m.member_id)===Number(box.dataset.me)?'mine':'');item.dataset.id=m.id;
  const avatar=document.createElement('div');avatar.className='cchat-avatar';
  if(m.avatar_url){const img=document.createElement('img');img.src=m.avatar_url;img.alt='';avatar.append(img);}else avatar.textContent=(m.display_name||'✦').trim().charAt(0)||'✦';
  const content=document.createElement('div');content.className='cchat-content';
  const meta=document.createElement('div');meta.className='cchat-meta';
  const link=document.createElement('a');link.href='/member.php?id='+Number(m.member_id);link.textContent=m.display_name||'Thành viên';
  const time=document.createElement('time');time.textContent=m.time_label||'Vừa xong';meta.append(link,time);
  const p=document.createElement('p');p.textContent=m.message;content.append(meta,p);item.append(avatar,content);box.append(item);last=Math.max(last,Number(m.id));
 }
 async function poll(){if(busy)return;busy=true;try{const r=await fetch('community-room.php?fetch=1&after='+last,{cache:'no-store',credentials:'same-origin'});if(!r.ok)throw new Error();const d=await r.json();if(!d.ok)throw new Error();d.messages.forEach(add);status.textContent='Đã kết nối';if(d.messages.length)box.scrollTop=box.scrollHeight;}catch(e){status.textContent='Mất kết nối, đang thử lại…';}finally{busy=false;}}
 box.scrollTop=box.scrollHeight;status.textContent='Đã kết nối';setInterval(poll,3500);
})();
</script></body></html>