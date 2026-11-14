<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/experience.php';
require_once __DIR__ . '/includes/story_rewards.php';
$slug=trim((string)($_GET['slug']??''));
$q=$pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id AND c.status='published') chapter_count FROM stories s WHERE s.slug=? AND s.status='published' LIMIT 1");
$q->execute([$slug]);$story=$q->fetch();
if(!$story){http_response_code(404);}
$chapters=[];
if($story){$q=$pdo->prepare("SELECT id,chapter_number,title,created_at FROM chapters WHERE story_id=? AND status='published' ORDER BY chapter_number");$q->execute([$story['id']]);$chapters=$q->fetchAll();}
$memberId=(int)($_SESSION['member_id']??0);$favorite=false;$liked=false;$likeCount=0;$viewCount=0;
$storyUrl = (($story['story_kind'] ?? 'serial') === 'short' ? '/truyen-ngan/' : '/truyen/') . rawurlencode($slug) . '.html';
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
if($story){
 $viewKey='story_viewed_'.(int)$story['id'];
 if(empty($_SESSION[$viewKey])){
  $pdo->prepare('UPDATE stories SET views_count=views_count+1 WHERE id=?')->execute([(int)$story['id']]);
  $_SESSION[$viewKey]=1;
 }
 $q=$pdo->prepare('SELECT views_count FROM stories WHERE id=?');$q->execute([(int)$story['id']]);$viewCount=(int)$q->fetchColumn();
 if($memberId && ($story['story_kind']??'serial')==='short'){
  td_reward_story_view($pdo,(int)$story['id'],$memberId,(int)($story['member_id']??0),'short_story',(int)$story['id']);
 }
 $q=$pdo->prepare('SELECT COUNT(*) FROM story_likes WHERE story_id=?');$q->execute([(int)$story['id']]);$likeCount=(int)$q->fetchColumn();
 if($memberId){$q=$pdo->prepare('SELECT 1 FROM story_likes WHERE member_id=? AND story_id=?');$q->execute([$memberId,$story['id']]);$liked=(bool)$q->fetchColumn();}
}
if($story&&$memberId){$q=$pdo->prepare('SELECT 1 FROM story_favorites WHERE member_id=? AND story_id=?');$q->execute([$memberId,$story['id']]);$favorite=(bool)$q->fetchColumn();}
if($story&&$memberId&&$_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='like'){
 if(empty($_SESSION['member_csrf'])||!hash_equals($_SESSION['member_csrf'],(string)($_POST['csrf']??''))){http_response_code(400);exit('Phiên đã hết hạn.');}
 if($liked){
  $pdo->prepare('DELETE FROM story_likes WHERE member_id=? AND story_id=?')->execute([$memberId,$story['id']]);
  $pdo->prepare('UPDATE stories SET likes_count=GREATEST(likes_count-1,0) WHERE id=?')->execute([$story['id']]);
 }else{
  $ins=$pdo->prepare('INSERT IGNORE INTO story_likes(member_id,story_id) VALUES(?,?)');
  $ins->execute([$memberId,$story['id']]);
  if($ins->rowCount())$pdo->prepare('UPDATE stories SET likes_count=likes_count+1 WHERE id=?')->execute([$story['id']]);
 }
 header('Location: '.((($story['story_kind']??'serial')==='short'?'truyen-ngan/':'truyen/').rawurlencode($slug).'.html'));exit;
}
if($story&&$memberId&&$_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='favorite'){
 if(empty($_SESSION['member_csrf'])||!hash_equals($_SESSION['member_csrf'],(string)($_POST['csrf']??''))){http_response_code(400);exit('Phiên đã hết hạn.');}
 if($favorite){$pdo->prepare('DELETE FROM story_favorites WHERE member_id=? AND story_id=?')->execute([$memberId,$story['id']]);}
 else{$pdo->prepare('INSERT IGNORE INTO story_favorites(member_id,story_id) VALUES(?,?)')->execute([$memberId,$story['id']]);}
 header('Location: '.((($story['story_kind']??'serial')==='short'?'truyen-ngan/':'truyen/').rawurlencode($slug).'.html'));exit;
}

/* Tặng Xu trực tiếp cho tác giả. */
if($story&&$memberId&&$_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='gift_author'){
 if(empty($_SESSION['member_csrf'])||!hash_equals($_SESSION['member_csrf'],(string)($_POST['csrf']??''))){http_response_code(400);exit('Phiên đã hết hạn.');}
 $authorId=(int)($story['member_id']??0);$amount=(int)($_POST['amount']??0);
 if($authorId<1||$authorId===$memberId||!in_array($amount,[5,10,20,50,100],true)){
  $_SESSION['story_gift_error']='Không thể tặng Xu cho tác giả của truyện này.';
 }else{
  try{
   $pdo->beginTransaction();
   $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$memberId]);
   $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$authorId]);
   $ids=[$memberId,$authorId];sort($ids);
   $lock=$pdo->prepare('SELECT member_id,balance FROM member_wallets WHERE member_id IN (?,?) ORDER BY member_id FOR UPDATE');$lock->execute($ids);
   $balances=$lock->fetchAll();$mine=0;foreach($balances as $row){if((int)$row['member_id']===$memberId)$mine=(int)$row['balance'];}
   if($mine<$amount){$pdo->rollBack();$_SESSION['story_gift_error']='Ví Xu của bạn không đủ.';}
   else{
    $pdo->prepare('UPDATE member_wallets SET balance=balance-? WHERE member_id=?')->execute([$amount,$memberId]);
    $pdo->prepare('UPDATE member_wallets SET balance=balance+? WHERE member_id=?')->execute([$amount,$authorId]);
    $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description,reference_id) VALUES(?,?,'gift_sent',?,?)")->execute([$memberId,-$amount,'Tặng Xu cho tác giả truyện: '.$story['title'],(int)$story['id']]);
    $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description,reference_id) VALUES(?,?,'gift_received',?,?)")->execute([$authorId,$amount,'Nhận Xu từ truyện: '.$story['title'],(int)$story['id']]);
    $pdo->commit();$_SESSION['story_gift_notice']='Đã gửi '.$amount.' Xu đến tác giả!';
   }
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['story_gift_error']='Giao dịch chưa thành công. Vui lòng thử lại.';}
 }
 header('Location: '.$storyUrl.'#story-gift');exit;
}

/* Bình luận truyện: chỉ thành viên đăng nhập mới được gửi hoặc thích. */
if ($story && $_SERVER['REQUEST_METHOD'] === 'POST' && in_array((string)($_POST['action'] ?? ''), ['add_comment','reply_comment','like_comment'], true)) {
 if (!$memberId) { header('Location: /login.php'); exit; }
 if (empty($_SESSION['member_csrf']) || !hash_equals($_SESSION['member_csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Phiên đã hết hạn. Vui lòng tải lại trang.'); }
 $action = (string)$_POST['action'];
 if ($action === 'like_comment') {
  $commentId = (int)($_POST['comment_id'] ?? 0);
  $check = $pdo->prepare('SELECT id FROM story_comments WHERE id=? AND story_id=? LIMIT 1');
  $check->execute([$commentId, (int)$story['id']]);
  if ($check->fetchColumn()) {
   $check = $pdo->prepare('SELECT 1 FROM story_comment_likes WHERE member_id=? AND comment_id=?');
   $check->execute([$memberId, $commentId]);
   if ($check->fetchColumn()) {
    $pdo->prepare('DELETE FROM story_comment_likes WHERE member_id=? AND comment_id=?')->execute([$memberId, $commentId]);
   } else {
    $insLike=$pdo->prepare('INSERT IGNORE INTO story_comment_likes(member_id,comment_id) VALUES(?,?)');
    $insLike->execute([$memberId, $commentId]);
    if($insLike->rowCount()){
     $owner=$pdo->prepare('SELECT member_id FROM story_comments WHERE id=?');$owner->execute([$commentId]);
     td_notify($pdo,(int)$owner->fetchColumn(),$memberId,'story_comment_like','Bình luận được yêu thích',($_SESSION['member_name']??'Một thành viên').' đã thích bình luận của bạn.',$storyUrl.'#story-comments');
    }
   }
  }
 } else {
  $content = trim((string)($_POST['content'] ?? ''));
  $parentId = $action === 'reply_comment' ? (int)($_POST['parent_id'] ?? 0) : 0;
  if ($content === '' || mb_strlen($content, 'UTF-8') > 3000) {
   $_SESSION['comment_error'] = 'Bình luận cần có nội dung và không vượt quá 3.000 ký tự.';
  } else {
   if ($parentId > 0) {
    $check = $pdo->prepare('SELECT id FROM story_comments WHERE id=? AND story_id=? AND parent_id IS NULL LIMIT 1');
    $check->execute([$parentId, (int)$story['id']]);
    if (!$check->fetchColumn()) { $parentId = 0; }
   }
   $insert = $pdo->prepare('INSERT INTO story_comments(story_id,member_id,parent_id,content) VALUES(?,?,?,?)');
   $insert->execute([(int)$story['id'], $memberId, $parentId ?: null, $content]);
   td_add_xp($pdo, $memberId, 'story_comment:' . (int)$pdo->lastInsertId(), 5, 'Bình luận truyện');
   if($parentId>0){
    $owner=$pdo->prepare('SELECT member_id FROM story_comments WHERE id=?');$owner->execute([$parentId]);
    td_notify($pdo,(int)$owner->fetchColumn(),$memberId,'story_comment_reply','Bạn có câu trả lời mới',($_SESSION['member_name']??'Một thành viên').' đã trả lời bình luận của bạn.',$storyUrl.'#story-comments');
   }
  }
 }
 header('Location: ' . $storyUrl . '#story-comments'); exit;
}
$topComments = []; $commentReplies = [];
if ($story) {
 $commentQuery = $pdo->prepare("SELECT c.id,c.parent_id,c.content,c.created_at,m.display_name,
  (SELECT COUNT(*) FROM story_comment_likes l WHERE l.comment_id=c.id) likes_count,
  (SELECT COUNT(*) FROM story_comment_likes l WHERE l.comment_id=c.id AND l.member_id=?) liked
  FROM story_comments c JOIN members m ON m.id=c.member_id
  WHERE c.story_id=? ORDER BY c.created_at ASC");
 $commentQuery->execute([$memberId, (int)$story['id']]);
 foreach ($commentQuery->fetchAll() as $comment) {
  if ($comment['parent_id'] === null) $topComments[] = $comment;
  else $commentReplies[(int)$comment['parent_id']][] = $comment;
 }
}

function st_h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function st_render_content($value): string {
 $value=trim((string)$value);
 if($value==='')return '';
 $blocks=preg_split('/\\R{2,}/u', $value) ?: [];
 $out=[];
 foreach($blocks as $block){
  $safe=st_h(trim($block));
  $safe=preg_replace_callback('~\\[img\\](/uploads/stories/[a-zA-Z0-9._-]+)\\[/img\\]~',function($m){return '<img class="story-inline-image" src="'.st_h($m[1]).'" alt="Ảnh minh họa truyện" loading="lazy">';},$safe);
  if(trim(strip_tags($safe))==='' && str_contains($safe,'<img'))$out[]=$safe;
  elseif(trim($safe)!=='')$out[]='<p>'.$safe.'</p>';
 }
 return implode("\n",$out);
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $story?st_h($story['title']).' | Truyện Đêm':'Không tìm thấy truyện' ?></title><link rel="stylesheet" href="/css/style.css?v=20261111-routes1"><link rel="stylesheet" href="/css/stories.css?v=20261112-author-gift"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
</head><body class="story-site">
<header class="site-header"><div class="header-inner"><a class="brand" href="/index.php"><span class="brand-mark">☾</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Thế giới truyện dành cho bạn</small></span></a><nav class="header-links"><a href="/index.php">Trang chủ</a><a href="/index.php#library">Thư viện</a><?php if($memberId):?><a href="/library.php">Tủ sách</a><a href="/submit_story.php">Đăng truyện</a><a href="/notifications.php">Thông báo</a><?php endif;?><a href="/community.php">Cộng đồng</a><a href="/friends.php">Bạn bè</a><a href="/shop.php">Cửa hàng</a><?php if($memberId):?><a href="/account.php">Tài khoản</a><?php else:?><a href="/login.php">Đăng nhập</a><?php endif;?></nav></div></header>
<main class="story-main"><?php if(!$story):?><section class="story-panel"><h1>Không tìm thấy truyện</h1><a class="story-btn" href="/index.php">Về thư viện truyện</a></section><?php else:?>
<a class="story-back" href="/index.php">← Thư viện truyện</a><section class="story-detail">
<?php if($story['cover_url']):?><img class="story-cover-large" src="<?=st_h($story['cover_url'])?>" alt="<?=st_h($story['title'])?>"><?php else:?><div class="story-cover-large cover-placeholder">☾</div><?php endif;?>
<div class="story-detail-copy"><span class="story-tag"><?=st_h($story['category'])?></span><h1><?=st_h($story['title'])?></h1><p class="story-author">Tác giả: <?=st_h($story['author'])?></p><p class="story-stats">◉ <?=number_format($viewCount)?> lượt xem <span>♥ <?=number_format($likeCount)?> lượt thích</span></p></div><section class="story-description-box" aria-label="Giới thiệu truyện"><h2 class="story-description-title">GIỚI THIỆU TRUYỆN</h2><p class="story-description"><?=nl2br(st_h($story['description']))?></p></section><div class="story-detail-actions"><p class="story-meta"><?=($story['story_kind']??'serial')==='short'?'Truyện ngắn':count($chapters).' chương'?> · <?= $story['story_type']==='audio'?'Truyện audio':($story['story_type']==='both'?'Đọc & nghe':'Truyện chữ') ?></p>
<?php if(($story['story_kind']??'serial')==='short'):?><a class="story-btn" href="#short-story-content">Đọc truyện</a><?php elseif($chapters):?><a class="story-btn" href="/read.php?id=<?=(int)$chapters[0]['id']?>">Đọc từ đầu</a><?php endif;?>
<?php if($memberId):?><form method="post" class="favorite-form"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="like"><button class="story-btn <?=$liked?'':'story-btn-quiet'?>"><?=$liked?'♥ Đã thích':'♡ Thích truyện'?></button></form><form method="post" class="favorite-form"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="favorite"><button class="story-btn story-btn-quiet"><?=$favorite?'♥ Bỏ khỏi yêu thích':'♡ Thêm vào yêu thích'?></button></form><?php else:?><a class="story-btn story-btn-quiet" href="/login.php">Đăng nhập để thích truyện</a><a class="story-btn story-btn-quiet" href="/login.php">Đăng nhập để lưu truyện</a><?php endif;?></div></section>
<?php if($memberId && (int)($story['member_id']??0)>0 && (int)$story['member_id']!==$memberId):?><section class="story-panel story-gift-panel" id="story-gift"><h2>✦ Ủng hộ tác giả</h2><p>Tặng một chút Xu để động viên người viết tiếp tục sáng tác.</p><?php if(!empty($_SESSION['story_gift_notice'])):?><p class="story-gift-notice"><?=st_h($_SESSION['story_gift_notice'])?></p><?php unset($_SESSION['story_gift_notice']);endif;?><?php if(!empty($_SESSION['story_gift_error'])):?><p class="story-gift-error"><?=st_h($_SESSION['story_gift_error'])?></p><?php unset($_SESSION['story_gift_error']);endif;?><form method="post"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="gift_author"><label for="story-gift-amount">Số Xu muốn tặng</label><select id="story-gift-amount" name="amount"><option value="5">5 Xu</option><option value="10">10 Xu</option><option value="20">20 Xu</option><option value="50">50 Xu</option><option value="100">100 Xu</option></select><button class="story-btn" type="submit">Tặng Xu</button></form></section><?php endif;?>
<?php if(($story['story_kind']??'serial')==='short'):?><article class="story-panel short-story-content" id="short-story-content"><h2>Nội dung truyện</h2><div class="short-story-text"><?=st_render_content($story['short_content']??'')?></div></article><?php else:?><section class="story-panel"><h2>Danh sách chương</h2><?php if(!$chapters):?><p>Truyện chưa có chương được xuất bản.</p><?php else:?><div class="chapter-list"><?php foreach($chapters as $c):?><a href="/read.php?id=<?=(int)$c['id']?>"><span>Chương <?= (int)$c['chapter_number'] ?> · <?=st_h($c['title'])?></span><small><?=st_h(date('d/m/Y',strtotime($c['created_at'])))?></small></a><?php endforeach;?></div><?php endif;?></section><?php endif;?><?php endif;?>
<section class="story-comments" id="story-comments">
 <div class="story-comments-head"><div><span class="story-tag">GÓC TRÒ CHUYỆN</span><h2>Bình luận <small><?=count($topComments) + array_sum(array_map('count',$commentReplies))?></small></h2></div><p>Cùng chia sẻ cảm nhận của bạn về câu chuyện này.</p></div>
 <?php if(!empty($_SESSION['comment_error'])): ?><p class="comment-error"><?=st_h($_SESSION['comment_error'])?></p><?php unset($_SESSION['comment_error']); endif; ?>
 <?php if($memberId): ?>
 <form class="comment-compose" method="post" action="<?=$storyUrl?>#story-comments">
  <input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="add_comment">
  <textarea name="content" rows="3" maxlength="3000" required placeholder="Viết cảm nghĩ của bạn..."></textarea>
  <div class="comment-compose-foot"><small>Tối đa 3.000 ký tự</small><button class="story-btn" type="submit">Gửi bình luận</button></div>
 </form>
 <?php else: ?>
 <div class="comment-login-note">Bạn cần <a href="/login.php">đăng nhập</a> để bình luận, trả lời và thích bình luận.</div>
 <?php endif; ?>
 <div class="comment-list">
 <?php if(!$topComments): ?><p class="comment-empty">Chưa có bình luận nào. Hãy là người đầu tiên chia sẻ nhé!</p><?php endif; ?>
 <?php foreach($topComments as $comment): ?>
  <article class="comment-item">
   <div class="comment-avatar"><?=st_h(mb_strtoupper(mb_substr((string)$comment['display_name'],0,1,'UTF-8'),'UTF-8'))?></div>
   <div class="comment-body">
    <div class="comment-meta"><strong><?=st_h($comment['display_name'])?></strong><time><?=st_h(date('d/m/Y H:i',strtotime($comment['created_at'])))?></time></div>
    <p class="comment-content"><?=nl2br(st_h($comment['content']))?></p>
    <div class="comment-actions">
     <?php if($memberId): ?><form method="post" action="<?=$storyUrl?>#story-comments"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="like_comment"><input type="hidden" name="comment_id" value="<?=(int)$comment['id']?>"><button class="comment-like <?=$comment['liked']?'is-liked':''?>" type="submit"><?=$comment['liked']?'♥ Đã thích':'♡ Thích'?> · <?=(int)$comment['likes_count']?></button></form><?php else: ?><span class="comment-like-static">♡ <?=(int)$comment['likes_count']?> thích</span><?php endif; ?>
    </div>
    <?php if($memberId): ?><button class="comment-reply-toggle" type="button" aria-expanded="false" aria-controls="reply-form-<?=(int)$comment['id']?>" data-reply-toggle>↩ Trả lời</button><form id="reply-form-<?=(int)$comment['id']?>" class="comment-reply-form" method="post" action="<?=$storyUrl?>#story-comments" hidden style="display:none"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="reply_comment"><input type="hidden" name="parent_id" value="<?=(int)$comment['id']?>"><textarea name="content" rows="2" maxlength="3000" required placeholder="Viết câu trả lời..."></textarea><div class="comment-reply-form-actions"><button type="submit">Gửi trả lời</button><button type="button" class="comment-reply-cancel" data-reply-cancel>Hủy</button></div></form><?php endif; ?>
    <?php if(!empty($commentReplies[(int)$comment['id']])): ?><div class="comment-replies">
     <?php foreach($commentReplies[(int)$comment['id']] as $reply): ?>
      <article class="comment-reply"><div class="comment-avatar reply-avatar"><?=st_h(mb_strtoupper(mb_substr((string)$reply['display_name'],0,1,'UTF-8'),'UTF-8'))?></div><div class="comment-body"><div class="comment-meta"><strong><?=st_h($reply['display_name'])?></strong><time><?=st_h(date('d/m/Y H:i',strtotime($reply['created_at'])))?></time></div><p class="comment-content"><?=nl2br(st_h($reply['content']))?></p><div class="comment-actions"><?php if($memberId): ?><form method="post" action="<?=$storyUrl?>#story-comments"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="like_comment"><input type="hidden" name="comment_id" value="<?=(int)$reply['id']?>"><button class="comment-like <?=$reply['liked']?'is-liked':''?>" type="submit"><?=$reply['liked']?'♥ Đã thích':'♡ Thích'?> · <?=(int)$reply['likes_count']?></button></form><?php else: ?><span class="comment-like-static">♡ <?=(int)$reply['likes_count']?> thích</span><?php endif; ?></div></div></article>
     <?php endforeach; ?>
    </div><?php endif; ?>
   </div>
  </article>
 <?php endforeach; ?>
 </div>
</section>

</main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm</div></footer>
<script>
document.querySelectorAll('[data-reply-toggle]').forEach(function(toggle){
 toggle.addEventListener('click',function(){
  var form=document.getElementById(toggle.getAttribute('aria-controls'));
  if(!form)return;
  var opening=form.hidden;
  form.hidden=!opening;
  form.style.display=opening?'flex':'none';
  toggle.setAttribute('aria-expanded',opening?'true':'false');
  toggle.textContent=opening?'↩ Đóng trả lời':'↩ Trả lời';
  if(opening){var field=form.querySelector('textarea');if(field)field.focus();}
 });
});
document.querySelectorAll('[data-reply-cancel]').forEach(function(cancel){
 cancel.addEventListener('click',function(){
  var form=cancel.closest('.comment-reply-form');
  if(!form)return;
  form.hidden=true;
  form.style.display='none';
  var toggle=document.querySelector('[aria-controls="'+form.id+'"]');
  if(toggle){toggle.setAttribute('aria-expanded','false');toggle.textContent='↩ Trả lời';}
 });
});
</script></body></html>