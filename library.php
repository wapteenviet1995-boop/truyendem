<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: /login.php?next=library.php'); exit; }
$me=(int)$_SESSION['member_id'];
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['member_csrf'];$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=(string)($_POST['action']??'');
 if(in_array($action,['remove_favorite','remove_history','clear_history'],true)){
  if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn. Hãy tải lại trang.';}
  elseif($action==='remove_favorite'){
   $storyId=(int)($_POST['story_id']??0);
   if($storyId>0)$pdo->prepare('DELETE FROM story_favorites WHERE member_id=? AND story_id=?')->execute([$me,$storyId]);
   header('Location: /library.php?removed=1');exit;
  }elseif($action==='remove_history'){
   $storyId=(int)($_POST['story_id']??0);
   if($storyId>0)$pdo->prepare('DELETE FROM story_reading_history WHERE member_id=? AND story_id=?')->execute([$me,$storyId]);
   header('Location: /library.php?history_removed=1');exit;
  }else{
   $pdo->prepare('DELETE FROM story_reading_history WHERE member_id=?')->execute([$me]);
   header('Location: /library.php?history_cleared=1');exit;
  }
 }
}
$favorites=[];$history=[];$recentHistory=[];
try{
 $q=$pdo->prepare("SELECT s.id,s.title,s.slug,s.author,s.cover_url,s.story_kind,s.category,s.story_type,s.updated_at FROM story_favorites f JOIN stories s ON s.id=f.story_id WHERE f.member_id=? AND s.status='published' ORDER BY f.created_at DESC");
 $q->execute([$me]);$favorites=$q->fetchAll();
 $q=$pdo->prepare("SELECT s.id story_id,s.title,s.slug,s.author,s.cover_url,s.story_kind,c.id chapter_id,c.chapter_number,c.title chapter_title,r.progress_percent,r.updated_at FROM story_reading_history r JOIN stories s ON s.id=r.story_id JOIN chapters c ON c.id=r.chapter_id WHERE r.member_id=? AND s.status='published' AND c.status='published' AND r.progress_percent<99.5 ORDER BY r.updated_at DESC LIMIT 30");
 $q->execute([$me]);$history=$q->fetchAll();
 $q=$pdo->prepare("SELECT s.id story_id,s.title,s.slug,s.author,s.cover_url,s.story_kind,c.id chapter_id,c.chapter_number,c.title chapter_title,r.progress_percent,r.updated_at FROM story_reading_history r JOIN stories s ON s.id=r.story_id JOIN chapters c ON c.id=r.chapter_id WHERE r.member_id=? AND s.status='published' AND c.status='published' ORDER BY r.updated_at DESC LIMIT 10");
 $q->execute([$me]);$recentHistory=$q->fetchAll();
}catch(PDOException $e){http_response_code(500);exit('Chưa cài đặt dữ liệu truyện. Hãy kiểm tra database/stories.sql.');}
function lib_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function lib_url(array $s):string{return (($s['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/').rawurlencode((string)$s['slug']).'.html';}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Tủ sách của tôi | Truyện Đêm</title><link rel="stylesheet" href="/css/style.css?v=memberhub1"><link rel="stylesheet" href="/css/stories.css?v=memberhub1"><link rel="stylesheet" href="/css/member-hub.css?v=20261122-light1"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
</head>
<body class="story-site"><?php require __DIR__ . '/includes/header.php'; ?>
<main class="member-hub-main"><section class="member-hub-hero"><span class="kicker">✦ KHÔNG GIAN RIÊNG CỦA BẠN ✦</span><h1>Tủ sách cá nhân</h1><p>Lưu lại những câu chuyện yêu thích và tiếp tục đọc từ nơi bạn đã dừng.</p></section>
<nav class="member-hub-tabs"><a href="/library.php">▣ Tủ sách</a><a href="/notifications.php">♧ Thông báo</a><a href="/shop.php">✧ Điểm danh & Xu</a><a href="/account.php">Hồ sơ</a></nav>
<?php if(isset($_GET['removed'])):?><div class="member-action-toast is-success" role="status"><span class="member-action-toast-icon">✓</span><div><strong>Đã cập nhật tủ sách</strong><small>Truyện đã được bỏ khỏi danh sách yêu thích.</small></div><button type="button" aria-label="Đóng thông báo" onclick="this.parentElement.remove()">×</button></div><?php endif;?><?php if($error):?><div class="member-action-toast is-error" role="alert"><span class="member-action-toast-icon">!</span><div><strong>Chưa thể thực hiện</strong><small><?=lib_h($error)?></small></div><button type="button" aria-label="Đóng thông báo" onclick="this.parentElement.remove()">×</button></div><?php endif;?>
<section class="member-hub-section"><h2>♡ Truyện đã lưu (<?=count($favorites)?>)</h2><?php if(!$favorites):?><div class="member-hub-empty">Bạn chưa lưu truyện nào. Hãy mở một truyện và chọn “Lưu vào tủ sách”.</div><?php else:?><div class="member-hub-grid"><?php foreach($favorites as $s):?><article class="member-hub-card"><?php if($s['cover_url']):?><img class="member-hub-cover" src="<?=lib_h($s['cover_url'])?>" alt=""><?php else:?><div class="member-hub-cover member-hub-cover-placeholder">☾</div><?php endif;?><div class="member-hub-card-copy"><strong><?=lib_h($s['title'])?></strong><small><?=lib_h($s['author'])?> · <?=($s['story_kind']==='short'?'Truyện ngắn':'Truyện dài')?></small><a href="<?=lib_h(lib_url($s))?>">Mở truyện →</a><form method="post" data-confirm-title="Bỏ truyện khỏi tủ sách?" data-confirm-message="Truyện sẽ được gỡ khỏi danh sách đã lưu. Bạn vẫn có thể tìm và lưu lại bất cứ lúc nào."><input type="hidden" name="csrf" value="<?=lib_h($csrf)?>"><input type="hidden" name="action" value="remove_favorite"><input type="hidden" name="story_id" value="<?=(int)$s['id']?>"><button class="member-hub-remove" type="submit">Bỏ khỏi tủ sách</button></form></div></article><?php endforeach;?></div><?php endif;?></section>
<section class="member-hub-section"><h2>◷ Đang đọc dở (<?=count($history)?>)</h2><?php if(!$history):?><div class="member-hub-empty">Bạn chưa có lịch sử đọc. Khi đăng nhập và mở chương truyện, lịch sử sẽ tự lưu tại đây.</div><?php else:?><div class="member-hub-grid"><?php foreach($history as $s):?><article class="member-hub-card"><?php if($s['cover_url']):?><img class="member-hub-cover" src="<?=lib_h($s['cover_url'])?>" alt=""><?php else:?><div class="member-hub-cover member-hub-cover-placeholder">☾</div><?php endif;?><div class="member-hub-card-copy"><strong><?=lib_h($s['title'])?></strong><small>Chương <?=(int)$s['chapter_number']?> · <?=lib_h($s['chapter_title'])?></small><small>Đọc lần cuối: <?=lib_h(date('d/m/Y H:i',strtotime($s['updated_at'])))?></small><div class="member-reading-progress"><span style="width:<?=max(0,min(100,(float)$s['progress_percent']))?>%"></span></div><small class="member-reading-percent">Đã đọc <?=number_format(max(0,min(100,(float)$s['progress_percent'])),0)?>%</small><a href="/read.php?id=<?=(int)$s['chapter_id']?>">Tiếp tục đọc →</a></div></article><?php endforeach;?></div><?php endif;?></section>
<section class="member-hub-section"><div class="member-history-heading"><h2>◷ Lịch sử đọc gần đây (<?=count($recentHistory)?>)</h2><?php if($recentHistory):?><form method="post" data-confirm-title="Xóa toàn bộ lịch sử đọc?" data-confirm-message="Lịch sử và tiến độ đọc của tất cả truyện sẽ bị xóa. Những truyện trong tủ sách yêu thích vẫn được giữ nguyên."><input type="hidden" name="csrf" value="<?=lib_h($csrf)?>"><input type="hidden" name="action" value="clear_history"><button class="member-hub-remove" type="submit">Xóa toàn bộ lịch sử</button></form><?php endif;?></div><?php if(isset($_GET['history_removed'])):?><div class="member-action-toast is-success" role="status"><span class="member-action-toast-icon">✓</span><div><strong>Đã xóa lịch sử</strong><small>Lịch sử và tiến độ của truyện đã được xóa.</small></div><button type="button" aria-label="Đóng thông báo" onclick="this.parentElement.remove()">×</button></div><?php endif;?><?php if(isset($_GET['history_cleared'])):?><div class="member-action-toast is-success" role="status"><span class="member-action-toast-icon">✓</span><div><strong>Đã dọn sạch lịch sử đọc</strong><small>Toàn bộ lịch sử và tiến độ đọc đã được xóa.</small></div><button type="button" aria-label="Đóng thông báo" onclick="this.parentElement.remove()">×</button></div><?php endif;?><?php if(!$recentHistory):?><div class="member-hub-empty">Những chương bạn đã mở gần đây sẽ xuất hiện tại đây.</div><?php else:?><div class="member-hub-grid"><?php foreach($recentHistory as $s):?><article class="member-hub-card"><?php if($s['cover_url']):?><img class="member-hub-cover" src="<?=lib_h($s['cover_url'])?>" alt=""><?php else:?><div class="member-hub-cover member-hub-cover-placeholder">☾</div><?php endif;?><div class="member-hub-card-copy"><strong><?=lib_h($s['title'])?></strong><small>Chương <?=(int)$s['chapter_number']?> · <?=lib_h($s['chapter_title'])?></small><small>Đọc gần đây: <?=lib_h(date('d/m/Y H:i',strtotime($s['updated_at'])))?></small><small>Tiến độ chương: <?=number_format(max(0,min(100,(float)$s['progress_percent'])),0)?>%</small><a href="/read.php?id=<?=(int)$s['chapter_id']?>">Mở lại chương →</a><form method="post" data-confirm-title="Xóa lịch sử truyện này?" data-confirm-message="Tiến độ đọc và lịch sử của truyện sẽ bị xóa. Truyện đã lưu trong tủ sách không bị ảnh hưởng."><input type="hidden" name="csrf" value="<?=lib_h($csrf)?>"><input type="hidden" name="action" value="remove_history"><input type="hidden" name="story_id" value="<?=(int)$s['story_id']?>"><button class="member-hub-remove" type="submit">Xóa lịch sử truyện</button></form></div></article><?php endforeach;?></div><?php endif;?></section></main>
<div class="member-confirm-backdrop" id="memberConfirm" aria-hidden="true">
 <section class="member-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="memberConfirmTitle" aria-describedby="memberConfirmMessage">
  <div class="member-confirm-symbol">?</div><h2 id="memberConfirmTitle">Xác nhận thao tác</h2><p id="memberConfirmMessage">Bạn có chắc muốn thực hiện thao tác này?</p>
  <div class="member-confirm-actions"><button type="button" class="member-confirm-cancel" id="memberConfirmCancel">Để sau</button><button type="button" class="member-confirm-submit" id="memberConfirmSubmit">Xác nhận xóa</button></div>
 </section>
</div>
<script>
(function(){
 const backdrop=document.getElementById('memberConfirm'),title=document.getElementById('memberConfirmTitle'),message=document.getElementById('memberConfirmMessage'),cancel=document.getElementById('memberConfirmCancel'),confirmButton=document.getElementById('memberConfirmSubmit');
 let pendingForm=null;
 function close(){backdrop.classList.remove('is-open');backdrop.setAttribute('aria-hidden','true');pendingForm=null;}
 document.querySelectorAll('form[data-confirm-title]').forEach(function(form){
  form.addEventListener('submit',function(event){
   if(form.dataset.confirmed==='1')return;
   event.preventDefault();pendingForm=form;
   title.textContent=form.dataset.confirmTitle||'Xác nhận thao tác';
   message.textContent=form.dataset.confirmMessage||'Bạn có chắc muốn tiếp tục?';
   backdrop.classList.add('is-open');backdrop.setAttribute('aria-hidden','false');cancel.focus();
  });
 });
 cancel.addEventListener('click',close);
 backdrop.addEventListener('click',function(event){if(event.target===backdrop)close();});
 confirmButton.addEventListener('click',function(){
  if(!pendingForm)return;
  const form=pendingForm;form.dataset.confirmed='1';close();form.submit();
 });
 document.addEventListener('keydown',function(event){if(event.key==='Escape'&&backdrop.classList.contains('is-open'))close();});
 document.querySelectorAll('.member-action-toast').forEach(function(toast){setTimeout(function(){toast.classList.add('is-hiding');setTimeout(function(){toast.remove();},350);},4500);});
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>