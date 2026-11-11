<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
$slug=trim((string)($_GET['slug']??''));
$q=$pdo->prepare("SELECT s.*, (SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id AND c.status='published') chapter_count FROM stories s WHERE s.slug=? AND s.status='published' LIMIT 1");
$q->execute([$slug]);$story=$q->fetch();
if(!$story){http_response_code(404);}
$chapters=[];
if($story){$q=$pdo->prepare("SELECT id,chapter_number,title,created_at FROM chapters WHERE story_id=? AND status='published' ORDER BY chapter_number");$q->execute([$story['id']]);$chapters=$q->fetchAll();}
$memberId=(int)($_SESSION['member_id']??0);$favorite=false;$liked=false;$likeCount=0;$viewCount=0;
if($story){
 $viewKey='story_viewed_'.(int)$story['id'];
 if(empty($_SESSION[$viewKey])){
  $pdo->prepare('UPDATE stories SET views_count=views_count+1 WHERE id=?')->execute([(int)$story['id']]);
  $_SESSION[$viewKey]=1;
 }
 $q=$pdo->prepare('SELECT views_count FROM stories WHERE id=?');$q->execute([(int)$story['id']]);$viewCount=(int)$q->fetchColumn();
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
 header('Location: story.php?slug='.rawurlencode($slug));exit;
}
if($story&&$memberId&&$_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='favorite'){
 if(empty($_SESSION['member_csrf'])||!hash_equals($_SESSION['member_csrf'],(string)($_POST['csrf']??''))){http_response_code(400);exit('Phiên đã hết hạn.');}
 if($favorite){$pdo->prepare('DELETE FROM story_favorites WHERE member_id=? AND story_id=?')->execute([$memberId,$story['id']]);}
 else{$pdo->prepare('INSERT IGNORE INTO story_favorites(member_id,story_id) VALUES(?,?)')->execute([$memberId,$story['id']]);}
 header('Location: story.php?slug='.rawurlencode($slug));exit;
}
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
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
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $story?st_h($story['title']).' | Truyện Đêm':'Không tìm thấy truyện' ?></title><link rel="stylesheet" href="css/style.css?v=20261111-story1"><link rel="stylesheet" href="css/stories.css?v=4"></head><body class="story-site">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">☾</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Thế giới truyện dành cho bạn</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="index.php#library">Thư viện</a><a href="community.php">Cộng đồng</a><a href="friends.php">Bạn bè</a><a href="shop.php">Cửa hàng</a><?php if($memberId):?><a href="account.php">Tài khoản</a><?php else:?><a href="login.php">Đăng nhập</a><?php endif;?></nav></div></header>
<main class="story-main"><?php if(!$story):?><section class="story-panel"><h1>Không tìm thấy truyện</h1><a class="story-btn" href="index.php">Về thư viện truyện</a></section><?php else:?>
<a class="story-back" href="index.php">← Thư viện truyện</a><section class="story-detail">
<?php if($story['cover_url']):?><img class="story-cover-large" src="<?=st_h($story['cover_url'])?>" alt="<?=st_h($story['title'])?>"><?php else:?><div class="story-cover-large cover-placeholder">☾</div><?php endif;?>
<div class="story-detail-copy"><span class="story-tag"><?=st_h($story['category'])?></span><h1><?=st_h($story['title'])?></h1><p class="story-author">Tác giả: <?=st_h($story['author'])?></p><p class="story-stats">◉ <?=number_format($viewCount)?> lượt xem <span>♥ <?=number_format($likeCount)?> lượt thích</span></p></div><section class="story-description-box" aria-label="Giới thiệu truyện"><h2 class="story-description-title">GIỚI THIỆU TRUYỆN</h2><p class="story-description"><?=nl2br(st_h($story['description']))?></p></section><div class="story-detail-actions"><p class="story-meta"><?=($story['story_kind']??'serial')==='short'?'Truyện ngắn':count($chapters).' chương'?> · <?= $story['story_type']==='audio'?'Truyện audio':($story['story_type']==='both'?'Đọc & nghe':'Truyện chữ') ?></p>
<?php if(($story['story_kind']??'serial')==='short'):?><a class="story-btn" href="#short-story-content">Đọc truyện</a><?php elseif($chapters):?><a class="story-btn" href="read.php?id=<?=(int)$chapters[0]['id']?>">Đọc từ đầu</a><?php endif;?>
<?php if($memberId):?><form method="post" class="favorite-form"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="like"><button class="story-btn <?=$liked?'':'story-btn-quiet'?>"><?=$liked?'♥ Đã thích':'♡ Thích truyện'?></button></form><form method="post" class="favorite-form"><input type="hidden" name="csrf" value="<?=st_h($_SESSION['member_csrf'])?>"><input type="hidden" name="action" value="favorite"><button class="story-btn story-btn-quiet"><?=$favorite?'♥ Bỏ khỏi yêu thích':'♡ Thêm vào yêu thích'?></button></form><?php else:?><a class="story-btn story-btn-quiet" href="login.php">Đăng nhập để thích truyện</a><a class="story-btn story-btn-quiet" href="login.php">Đăng nhập để lưu truyện</a><?php endif;?></div></section>
<?php if(($story['story_kind']??'serial')==='short'):?><article class="story-panel short-story-content" id="short-story-content"><h2>Nội dung truyện</h2><div class="short-story-text"><?=st_render_content($story['short_content']??'')?></div></article><?php else:?><section class="story-panel"><h2>Danh sách chương</h2><?php if(!$chapters):?><p>Truyện chưa có chương được xuất bản.</p><?php else:?><div class="chapter-list"><?php foreach($chapters as $c):?><a href="read.php?id=<?=(int)$c['id']?>"><span>Chương <?= (int)$c['chapter_number'] ?> · <?=st_h($c['title'])?></span><small><?=st_h(date('d/m/Y',strtotime($c['created_at'])))?></small></a><?php endforeach;?></div><?php endif;?></section><?php endif;?><?php endif;?></main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm</div></footer></body></html>