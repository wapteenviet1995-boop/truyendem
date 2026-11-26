<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php?next=community-room.php'); exit; }
$me=(int)$_SESSION['member_id'];
if(empty($_SESSION['member_csrf'])) $_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=(string)$_SESSION['member_csrf'];
function cchat_h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function cchat_json(array $data,int $status=200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST' && (string)($_POST['action']??'')==='presence') {
  if (!hash_equals($csrf,(string)($_POST['csrf']??''))) cchat_json(['ok'=>false],403);
  $q=$pdo->prepare("INSERT INTO community_chat_presence(member_id,last_seen) VALUES(?,NOW()) ON DUPLICATE KEY UPDATE last_seen=NOW()");
  $q->execute([$me]);
  $count=(int)$pdo->query("SELECT COUNT(*) FROM community_chat_presence WHERE last_seen>=DATE_SUB(NOW(),INTERVAL 90 SECOND)")->fetchColumn();
  cchat_json(['ok'=>true,'online'=>$count]);
}
if ($_SERVER['REQUEST_METHOD']==='POST' && (string)($_POST['action']??'')==='delete') {
  if (!hash_equals($csrf,(string)($_POST['csrf']??''))) cchat_json(['ok'=>false,'message'=>'Phiên đã hết hạn.'],403);
  $id=max(0,(int)($_POST['message_id']??0));
  $q=$pdo->prepare("UPDATE community_chat_messages SET deleted_at=NOW() WHERE id=? AND member_id=? AND deleted_at IS NULL");
  $q->execute([$id,$me]);
  cchat_json(['ok'=>$q->rowCount()===1,'message'=>$q->rowCount()===1?'Đã xóa tin nhắn.':'Không thể xóa tin nhắn này.']);
}
if(isset($_GET['fetch'])) {
  $after=max(0,(int)($_GET['after']??0));
  $q=$pdo->prepare("SELECT c.id,c.member_id,c.message,DATE_FORMAT(c.created_at,'%d/%m %H:%i') AS time_label,m.display_name,m.avatar_url,CASE WHEN p.last_seen>=DATE_SUB(NOW(),INTERVAL 90 SECOND) THEN 1 ELSE 0 END AS is_online FROM community_chat_messages c JOIN members m ON m.id=c.member_id LEFT JOIN community_chat_presence p ON p.member_id=m.id WHERE c.id>? AND c.deleted_at IS NULL AND m.is_active=1 ORDER BY c.id ASC LIMIT 80");
  $q->execute([$after]); $newMessages=$q->fetchAll();
  $deleted=$pdo->query("SELECT id FROM community_chat_messages WHERE deleted_at IS NOT NULL AND created_at>=DATE_SUB(NOW(),INTERVAL 2 DAY)")->fetchAll(PDO::FETCH_COLUMN);
  $online=(int)$pdo->query("SELECT COUNT(*) FROM community_chat_presence WHERE last_seen>=DATE_SUB(NOW(),INTERVAL 90 SECOND)")->fetchColumn();
  cchat_json(['ok'=>true,'messages'=>$newMessages,'deleted_ids'=>$deleted,'online'=>$online]);
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
$q=$pdo->query("SELECT c.id,c.member_id,c.message,c.created_at,m.display_name,m.avatar_url,CASE WHEN p.last_seen>=DATE_SUB(NOW(),INTERVAL 90 SECOND) THEN 1 ELSE 0 END AS is_online FROM community_chat_messages c JOIN members m ON m.id=c.member_id LEFT JOIN community_chat_presence p ON p.member_id=m.id WHERE c.deleted_at IS NULL AND m.is_active=1 ORDER BY c.id DESC LIMIT 80");
$messages=array_reverse($q->fetchAll());
$lastId=$messages?(int)end($messages)['id']:0;
$online=(int)$pdo->query("SELECT COUNT(*) FROM community_chat_presence WHERE last_seen>=DATE_SUB(NOW(),INTERVAL 90 SECOND)")->fetchColumn();
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Phòng trò chuyện | Truyện Đêm</title>
<link rel="stylesheet" href="/css/style.css?v=messaging2"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/theme-mode.css?v=20261122-global-light-audit5"><link rel="stylesheet" href="/css/community-room.css?v=2"></head>
<body class="blog-page community-chat-page"><?php require __DIR__.'/includes/header.php'; ?>
<main class="cchat-main"><section class="cchat-hero"><span>✦ KẾT NỐI CÙNG ĐỘC GIẢ ✦</span><h1>Phòng trò chuyện</h1><p>Cùng giao lưu, chia sẻ cảm nhận và trò chuyện với mọi người trong Truyện Đêm.</p></section>
<?php if($error):?><p class="cchat-error"><?=cchat_h($error)?></p><?php endif;?>
<section class="cchat-panel"><div class="cchat-top"><div><strong>☾ Phòng chung Truyện Đêm</strong><small id="cchat-status">Đang kết nối…</small></div><div class="cchat-top-actions"><span class="cchat-online">● <b id="cchat-online-count"><?=$online?></b> đang online</span><button type="button" id="cchat-notify">Bật thông báo</button></div></div>
<div class="cchat-stream" id="cchat-stream" data-last-id="<?=$lastId?>" data-me="<?=$me?>" data-csrf="<?=cchat_h($csrf)?>">
<?php if(!$messages):?><p class="cchat-empty">Chưa có tin nhắn nào. Hãy mở lời đầu tiên nhé!</p><?php else:foreach($messages as $m):?><article class="cchat-message <?=((int)$m['member_id']===$me?'mine':'')?> " data-id="<?=(int)$m['id']?>"><div class="cchat-avatar"><?php if(!empty($m['avatar_url'])):?><img src="<?=cchat_h($m['avatar_url'])?>" alt=""><?php else:?><?=cchat_h(mb_substr((string)$m['display_name'],0,1))?><?php endif;?></div><div class="cchat-content"><div class="cchat-meta"><a href="/member.php?id=<?=(int)$m['member_id']?>"><?=cchat_h($m['display_name'])?></a><span class="cchat-member-status <?=((int)$m['is_online']===1?'online':'offline')?>"><?=((int)$m['is_online']===1?'Đang online':'Ngoại tuyến')?></span><time><?=cchat_h(date('d/m H:i',strtotime($m['created_at'])))?></time></div><p><?=nl2br(cchat_h($m['message']))?></p><?php if((int)$m['member_id']===$me):?><button type="button" class="cchat-delete" data-delete="<?=(int)$m['id']?>">Xóa</button><?php endif;?></div></article><?php endforeach;endif;?>
</div><div class="cchat-emoji-bar" aria-label="Chọn biểu tượng cảm xúc"><?php foreach(['😀','😂','🥰','😍','😢','😮','👍','❤️','🎉','🌙','👻','✨'] as $emoji): ?><button type="button" class="cchat-emoji" data-emoji="<?=cchat_h($emoji)?>"><?=cchat_h($emoji)?></button><?php endforeach; ?></div><form class="cchat-compose" method="post"><input type="hidden" name="csrf" value="<?=cchat_h($csrf)?>"><textarea id="cchat-message-input" name="message" maxlength="1000" rows="2" required placeholder="Chia sẻ điều bạn đang nghĩ…"></textarea><button type="submit">Gửi tin nhắn ✦</button></form><p class="cchat-note">Vui lòng trò chuyện văn minh, không spam hoặc chia sẻ thông tin riêng tư.</p></section></main>
<?php require __DIR__.'/includes/footer.php'; ?>
<script>
(function(){
 const box=document.getElementById('cchat-stream'), status=document.getElementById('cchat-status'); if(!box)return;
 let last=Number(box.dataset.lastId||0), busy=false; const notifyButton=document.getElementById('cchat-notify');
 function add(m){
  const item=document.createElement('article');item.className='cchat-message '+(Number(m.member_id)===Number(box.dataset.me)?'mine':'');item.dataset.id=m.id;
  const avatar=document.createElement('div');avatar.className='cchat-avatar';
  if(m.avatar_url){const img=document.createElement('img');img.src=m.avatar_url;img.alt='';avatar.append(img);}else avatar.textContent=(m.display_name||'✦').trim().charAt(0)||'✦';
  const content=document.createElement('div');content.className='cchat-content';
  const meta=document.createElement('div');meta.className='cchat-meta';
  const link=document.createElement('a');link.href='/member.php?id='+Number(m.member_id);link.textContent=m.display_name||'Thành viên';
  const state=document.createElement('span');state.className='cchat-member-status '+(Number(m.is_online)===1?'online':'offline');state.textContent=Number(m.is_online)===1?'Đang online':'Ngoại tuyến';const time=document.createElement('time');time.textContent=m.time_label||'Vừa xong';meta.append(link,state,time);
  const p=document.createElement('p');p.textContent=m.message;content.append(meta,p);
  if(Number(m.member_id)===Number(box.dataset.me)){const del=document.createElement('button');del.type='button';del.className='cchat-delete';del.dataset.delete=m.id;del.textContent='Xóa';content.append(del);}
  item.append(avatar,content);box.append(item);last=Math.max(last,Number(m.id));
 }
 async function presence(){try{const fd=new FormData();fd.append('action','presence');fd.append('csrf',box.dataset.csrf);const r=await fetch('community-room.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await r.json();if(d.ok)document.getElementById('cchat-online-count').textContent=d.online;}catch(e){}}
 async function poll(){if(busy)return;busy=true;try{const r=await fetch('community-room.php?fetch=1&after='+last,{cache:'no-store',credentials:'same-origin'});if(!r.ok)throw new Error();const d=await r.json();if(!d.ok)throw new Error();d.messages.forEach(m=>{add(m);if(Number(m.member_id)!==Number(box.dataset.me))sendNotice(m);});(d.deleted_ids||[]).forEach(id=>{const el=box.querySelector('[data-id="'+Number(id)+'"]');if(el)el.remove();});if(typeof d.online==='number')document.getElementById('cchat-online-count').textContent=d.online;status.textContent='Đã kết nối';if(d.messages.length)box.scrollTop=box.scrollHeight;}catch(e){status.textContent='Mất kết nối, đang thử lại…';}finally{busy=false;}}
 box.addEventListener('click',async e=>{const btn=e.target.closest('[data-delete]');if(!btn)return;if(!confirm('Bạn muốn xóa tin nhắn này?'))return;btn.disabled=true;try{const fd=new FormData();fd.append('action','delete');fd.append('message_id',btn.dataset.delete);fd.append('csrf',box.dataset.csrf);const r=await fetch('community-room.php',{method:'POST',body:fd,credentials:'same-origin'});const d=await r.json();if(d.ok){const item=btn.closest('.cchat-message');if(item)item.remove();}else{alert(d.message||'Không thể xóa tin nhắn.');btn.disabled=false;}}catch(e){alert('Có lỗi kết nối.');btn.disabled=false;}});
 box.scrollTop=box.scrollHeight;status.textContent='Đã kết nối';presence();setInterval(presence,30000);setInterval(poll,3500);
 const input=document.getElementById('cchat-message-input');document.querySelectorAll('[data-emoji]').forEach(b=>b.addEventListener('click',()=>{const e=b.dataset.emoji;const start=input.selectionStart,end=input.selectionEnd;input.value=input.value.slice(0,start)+e+input.value.slice(end);input.focus();input.setSelectionRange(start+e.length,start+e.length);}));
 function sendNotice(m){if(!('Notification'in window)||Notification.permission!=='granted'||document.visibilityState==='visible')return;try{new Notification('Tin nhắn mới - Truyện Đêm',{body:(m.display_name||'Thành viên')+': '+m.message,tag:'truyendem-chat-'+m.id});}catch(e){}}
 if(notifyButton){if(!('Notification'in window))notifyButton.textContent='Trình duyệt không hỗ trợ';else if(Notification.permission==='granted')notifyButton.textContent='Đã bật thông báo';else if(Notification.permission==='denied')notifyButton.textContent='Thông báo bị chặn';notifyButton.addEventListener('click',async()=>{if(!('Notification'in window))return;const p=await Notification.requestPermission();notifyButton.textContent=p==='granted'?'Đã bật thông báo':p==='denied'?'Thông báo bị chặn':'Chưa bật';});}
})();
</script></body></html>