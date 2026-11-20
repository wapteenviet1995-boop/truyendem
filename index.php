<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
$category=trim((string)($_GET['category']??''));
function home_slug(string $value): string { $value=trim($value); if(function_exists('iconv')){$v=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);if($v!==false)$value=$v;} return strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/','-',$value),'-')); }
$stories=[];$categories=[];$history=[];
try{
 $categories=$pdo->query("SELECT DISTINCT category FROM stories WHERE status='published' AND category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
 $categorySlug=trim((string)($_GET['category_slug']??'')); if($categorySlug!==''){ $category=''; foreach($categories as $categoryName){if(home_slug((string)$categoryName)===$categorySlug){$category=(string)$categoryName;break;}} if($category===''){http_response_code(404);} }
 $sql="SELECT s.id,s.title,s.slug,s.author,s.description,s.cover_url,s.category,s.story_type,s.story_kind,s.views_count,s.likes_count,(SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id AND c.status='published') chapter_count,(SELECT MAX(c.updated_at) FROM chapters c WHERE c.story_id=s.id AND c.status='published') latest_chapter FROM stories s WHERE s.status='published'";
 $params=[];
 if($category!==''){$sql.=" AND s.category=?";$params[]=$category;}
 $sql.=" ORDER BY COALESCE((SELECT MAX(c.updated_at) FROM chapters c WHERE c.story_id=s.id AND c.status='published'),s.updated_at) DESC LIMIT 60";
 $stmt=$pdo->prepare($sql);$stmt->execute($params);$stories=$stmt->fetchAll();
 if(!empty($_SESSION['member_id'])){$h=$pdo->prepare("SELECT s.title,s.slug,c.id chapter_id,c.chapter_number,c.title chapter_title FROM story_reading_history r JOIN stories s ON s.id=r.story_id JOIN chapters c ON c.id=r.chapter_id WHERE r.member_id=? AND s.status='published' ORDER BY r.updated_at DESC LIMIT 6");$h->execute([(int)$_SESSION['member_id']]);$history=$h->fetchAll();}
}catch(PDOException $e){$setupError=true;}
function home_h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function home_story_url(array $s): string { return (($s['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/').rawurlencode((string)$s['slug']).'.html'; }
function home_story_card(array $s): void {
 $url=home_story_url($s); $cover=(string)($s['cover_url']??'');
 echo '<article class="td-home-card"><a class="td-home-card-cover" href="'.home_h($url).'">';
 if($cover!=='') echo '<img src="'.home_h($cover).'" alt="'.home_h($s['title']??'').'" loading="lazy">';
 else echo '<span>☾</span>';
 echo '</a><div class="td-home-card-body"><span class="td-home-card-tag">'.home_h($s['category']??'Truyện').'</span><h3><a href="'.home_h($url).'">'.home_h($s['title']??'').'</a></h3>';
 $desc=trim((string)($s['description']??'')); if($desc!=='') echo '<p>'.home_h(mb_strimwidth($desc,0,92,'…','UTF-8')).'</p>';
 echo '<div class="td-home-card-meta"><span>'.home_h($s['author']??'Truyện Đêm').'</span><span>'.(($s['story_kind']??'serial')==='short'?'Truyện ngắn':((int)($s['chapter_count']??0).' chương')).'</span></div></div></article>';
}
$shortStories=[];$longStories=[];$featuredAuthors=[];$communityActivity=[];
try {
 $typeSql="SELECT s.id,s.title,s.slug,s.author,s.description,s.cover_url,s.category,s.story_kind,s.views_count,s.likes_count,(SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id AND c.status='published') chapter_count FROM stories s WHERE s.status='published' AND s.story_kind=?";
 if($category!=='')$typeSql.=" AND s.category=?";
 $typeSql.=" ORDER BY COALESCE((SELECT MAX(c.updated_at) FROM chapters c WHERE c.story_id=s.id AND c.status='published'),s.updated_at) DESC LIMIT 6";
 $q=$pdo->prepare($typeSql);$args=['short'];if($category!=='')$args[]=$category;$q->execute($args);$shortStories=$q->fetchAll();
 $q=$pdo->prepare($typeSql);$args=['serial'];if($category!=='')$args[]=$category;$q->execute($args);$longStories=$q->fetchAll();
} catch(PDOException $e) { $shortStories=[];$longStories=[]; }
try {
 $featuredAuthors=$pdo->query("SELECT m.id,m.display_name,m.avatar_url,COUNT(DISTINCT s.id) AS story_count,COUNT(DISTINCT f.id) AS follower_count FROM members m INNER JOIN stories s ON s.member_id=m.id AND s.status='published' LEFT JOIN member_follows f ON f.following_id=m.id WHERE m.is_active=1 GROUP BY m.id,m.display_name,m.avatar_url ORDER BY story_count DESC,follower_count DESC,m.id DESC LIMIT 6")->fetchAll();
} catch(PDOException $e) { $featuredAuthors=[]; }
try {
 $communityActivity=$pdo->query("SELECT p.id,p.member_id,p.content,p.created_at,m.display_name,m.avatar_url,(SELECT COUNT(*) FROM community_likes l WHERE l.post_id=p.id) AS like_count,(SELECT COUNT(*) FROM community_comments c WHERE c.post_id=p.id) AS comment_count FROM community_posts p INNER JOIN members m ON m.id=p.member_id WHERE m.is_active=1 ORDER BY p.created_at DESC,p.id DESC LIMIT 4")->fetchAll();
} catch(PDOException $e) { $communityActivity=[]; }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#090a10"><title>Truyện Đêm - Thư viện truyện chữ và audio</title><meta name="description" content="Khám phá truyện kinh dị, tình cảm, huyền huyễn và nhiều câu chuyện hấp dẫn tại Truyện Đêm."><link rel="stylesheet" href="/css/style.css?v=20261111-routes1"><link rel="stylesheet" href="/css/stories.css?v=20261111-routes1"><link rel="stylesheet" href="/css/member-hub.css?v=20261112-leaderboard2"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
<link rel="stylesheet" href="/css/home-slider.css?v=20261118-5"><link rel="stylesheet" href="/css/home-sections.css?v=20261120-1"><link rel="stylesheet" href="/css/home-redesign.css?v=20261120-1"></head><body class="story-site" id="top">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="story-main">
<section class="td-home-slider" id="tdHomeSlider" aria-label="Truyện nổi bật">
 <div class="td-slider-topline"><span>☾ GÓC TRUYỆN ĐÊM</span><small>NHỮNG CÂU CHUYỆN DÀNH CHO BẠN</small></div>
 <div class="td-slider-track">
 <?php foreach(array_slice($stories,0,6) as $slideIndex=>$slide): $slideUrl=(($slide['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/').rawurlencode((string)$slide['slug']).'.html'; ?>
 <article class="td-slider-slide<?= $slideIndex===0?' is-active':'' ?>"<?= $slideIndex===0?'':' aria-hidden="true"' ?>>
  <?php if(!empty($slide['cover_url'])):?><img class="td-slider-image" src="<?=home_h($slide['cover_url'])?>" alt="" loading="<?= $slideIndex===0?'eager':'lazy' ?>"><?php else:?><div class="td-slider-image td-slider-no-cover">☾</div><?php endif;?>
  <div class="td-slider-shade"></div>
  <div class="td-slider-copy">
   <span class="td-slider-label"><?=home_h($slide['category']?:'TRUYỆN NỔI BẬT')?></span>
   <h2><?=home_h($slide['title'])?></h2>
   <p><?=home_h(mb_strimwidth((string)$slide['description'],0,150,'…','UTF-8'))?></p>
   <div class="td-slider-meta"><?=home_h($slide['author']?:'Truyện Đêm')?> <i>•</i> <?=($slide['story_kind']??'serial')==='short'?'Truyện ngắn':(int)$slide['chapter_count'].' chương'?></div>
   <a class="td-slider-cta" href="<?=$slideUrl?>">Đọc truyện <span>→</span></a>
  </div>
 </article>
 <?php endforeach;?>
 <?php if(!$stories):?><div class="td-slider-empty"><span>☾</span><h2>Mỗi đêm, một thế giới khác</h2><p>Những câu chuyện mới đang chờ bạn.</p></div><?php endif;?>
 <?php if(count($stories)>1):?><div class="td-slider-controls"><button type="button" class="td-slider-arrow td-slider-prev" aria-label="Truyện trước">‹</button><div class="td-slider-dots"><?php foreach(array_slice($stories,0,6) as $dotIndex=>$unused):?><button type="button" class="td-slider-dot<?= $dotIndex===0?' is-active':''?>" data-slide="<?=$dotIndex?>" aria-label="Đến truyện <?=$dotIndex+1?>"></button><?php endforeach;?></div><button type="button" class="td-slider-arrow td-slider-next" aria-label="Truyện tiếp">›</button></div><?php endif;?>
 </div>
</section>
<script>
(function(){
 const root=document.getElementById('tdHomeSlider');if(!root)return;
 const slides=[...root.querySelectorAll('.td-slider-slide')],dots=[...root.querySelectorAll('.td-slider-dot')];
 if(slides.length<2)return;let current=0,timer;
 function show(index){current=(index+slides.length)%slides.length;slides.forEach((s,i)=>{s.classList.toggle('is-active',i===current);s.setAttribute('aria-hidden',i===current?'false':'true');});dots.forEach((d,i)=>d.classList.toggle('is-active',i===current));}
 function start(){clearInterval(timer);timer=setInterval(()=>show(current+1),5500);}
 root.querySelector('.td-slider-prev')?.addEventListener('click',()=>{show(current-1);start();});
 root.querySelector('.td-slider-next')?.addEventListener('click',()=>{show(current+1);start();});
 dots.forEach((dot,i)=>dot.addEventListener('click',()=>{show(i);start();}));
 root.addEventListener('mouseenter',()=>clearInterval(timer));root.addEventListener('mouseleave',start);
 root.addEventListener('focusin',()=>clearInterval(timer));root.addEventListener('focusout',start);
 start();
})();
</script>
<?php if(!empty($setupError)):?><section class="story-empty"><h2>Thư viện đang chờ khởi tạo</h2><p>Hãy chạy file <strong>database/stories.sql</strong> trong phpMyAdmin để tạo dữ liệu truyện và chương.</p></section><?php else:?>
<?php if($history):?><section class="story-section"><div class="story-section-head"><h2>☾ Đọc tiếp</h2></div><div class="chapter-list"><?php foreach($history as $h):?><a href="/truyen/<?=rawurlencode($h['slug'])?>/chuong-<?= (int)$h['chapter_number']?>.html"><span><?=home_h($h['title'])?> · Chương <?= (int)$h['chapter_number']?> — <?=home_h($h['chapter_title'])?></span><small>Đọc tiếp →</small></a><?php endforeach;?></div></section><?php endif;?>
<section class="story-section" id="library"><div class="story-section-head"><h2>✧ Truyện mới cập nhật</h2></div>
<?php if($categories):?><div class="story-categories" id="categories"><a class="<?= $category===''?'active':''?>" href="/index.php#library">Tất cả</a><?php foreach($categories as $cat):?><a class="<?= $category===$cat?'active':''?>" href="/chuyen-muc/<?=home_slug((string)$cat)?>.html"><?=home_h($cat)?></a><?php endforeach;?></div><?php endif;?>
<?php if(!$stories):?><div class="story-empty"><h3>Chưa tìm thấy truyện phù hợp</h3><p>Thư viện sẽ sớm có thêm những câu chuyện mới.</p></div><?php else:?><div class="story-grid"><?php foreach(array_slice($stories,0,8) as $s):?><article class="story-card"><a class="story-card-cover" href="<?=($s['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/'?><?=rawurlencode($s['slug'])?>.html"><?php if($s['cover_url']):?><img src="<?=home_h($s['cover_url'])?>" alt="<?=home_h($s['title'])?>" loading="lazy"><?php else:?>☾<?php endif;?></a><div class="story-card-body"><span class="story-tag"><?=home_h($s['category'])?></span><h3><a href="<?=($s['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/'?><?=rawurlencode($s['slug'])?>.html"><?=home_h($s['title'])?></a></h3><p><?=home_h(mb_strimwidth((string)$s['description'],0,115,'…','UTF-8'))?></p><div class="story-card-meta"><?=home_h($s['author'])?> · <?=($s['story_kind']??'serial')==='short'?'Truyện ngắn':(int)$s['chapter_count'].' chương'?><br>◉ <?=number_format((int)$s['views_count'])?> · ♥ <?=number_format((int)$s['likes_count'])?></div></div></article><?php endforeach;?></div><div class="td-home-more"><a href="/library.php">Khám phá toàn bộ thư viện →</a></div><?php endif;?></section><?php endif;?>
<section class="td-home-section td-quick-read" id="short-stories">
 <div class="td-home-section-head"><div><span class="td-home-eyebrow">ĐỌC NHANH MỖI ĐÊM</span><h2>Truyện ngắn mới</h2><p>Những câu chuyện trọn vẹn trong một lần đọc.</p></div><a href="/library.php?kind=short">Xem thêm <span>→</span></a></div>
 <div class="td-quick-read-list"><?php if($shortStories):?><div class="td-home-cards"><?php foreach($shortStories as $s)home_story_card($s);?></div><?php else:?><div class="td-home-empty">Chưa có truyện ngắn phù hợp.</div><?php endif;?></div>
</section>
<section class="td-home-section" id="serial-stories">
 <div class="td-home-section-head"><div><span class="td-home-eyebrow">NHỮNG HÀNH TRÌNH DÀI</span><h2>Truyện dài đang phát hành</h2><p>Theo dõi những câu chuyện được cập nhật qua từng chương.</p></div><a href="/library.php?kind=serial">Xem thêm <span>→</span></a></div>
 <?php if($longStories):?><div class="td-home-cards"><?php foreach($longStories as $s)home_story_card($s);?></div><?php else:?><div class="td-home-empty">Chưa có truyện dài phù hợp.</div><?php endif;?>
</section>
<?php
$topXu=[];$topLv=[];
try { $topXu=$pdo->query("SELECT m.id,m.display_name,m.avatar_url,COALESCE(w.balance,0) AS balance FROM members m LEFT JOIN member_wallets w ON w.member_id=m.id WHERE m.is_active=1 ORDER BY COALESCE(w.balance,0) DESC,m.id ASC LIMIT 5")->fetchAll(); } catch(PDOException $e) { $topXu=[]; }
try { $topLv=$pdo->query("SELECT m.id,m.display_name,m.avatar_url,COALESCE(x.total_xp,0) AS total_xp FROM members m LEFT JOIN member_experience x ON x.member_id=m.id WHERE m.is_active=1 ORDER BY COALESCE(x.total_xp,0) DESC,m.id ASC LIMIT 5")->fetchAll(); } catch(PDOException $e) { $topLv=[]; }
function home_level_name(int $xp): string {
 if($xp>=5000)return 'Huyền Thoại';
 if($xp>=2500)return 'Tinh Tú';
 if($xp>=1000)return 'Người Kể Chuyện';
 if($xp>=300)return 'Nhà Khám Phá';
 if($xp>=100)return 'Độc Giả';
 return 'Tân Binh';
}
?>
<section class="leaderboard-section leaderboard-compact" id="leaderboard" aria-labelledby="leaderboard-title">
 <div class="leaderboard-heading"><h2 id="leaderboard-title">✦ BẢNG XẾP HẠNG</h2><div class="leaderboard-tabs" role="tablist" aria-label="Chọn bảng xếp hạng"><button type="button" class="leaderboard-tab is-active" id="tab-xu" role="tab" aria-selected="true" aria-controls="panel-xu" data-board="xu">TOP XU</button><button type="button" class="leaderboard-tab" id="tab-lv" role="tab" aria-selected="false" aria-controls="panel-lv" data-board="lv">TOP LV</button></div></div>
 <div class="leaderboard-panel" id="panel-xu" role="tabpanel" aria-labelledby="tab-xu">
 <?php if($topXu):?><ol class="leaderboard-list"><?php foreach($topXu as $i=>$person):?><li class="leaderboard-row rank-<?=($i+1)?>"><span class="leaderboard-rank"><?=($i+1)?></span><a class="leaderboard-avatar" href="/member.php?id=<?=(int)$person['id']?>"><?php if(!empty($person['avatar_url'])):?><img src="<?=home_h($person['avatar_url'])?>" alt=""><?php else:?><?=home_h(mb_substr((string)$person['display_name'],0,1,'UTF-8'))?><?php endif;?></a><div class="leaderboard-member"><a class="leaderboard-name name-rank-<?=($i+1)?>" href="/member.php?id=<?=(int)$person['id']?>"><?=home_h($person['display_name'])?></a></div><strong class="leaderboard-xu"><?=number_format((int)$person['balance'])?> <small>Xu</small></strong></li><?php endforeach;?></ol><?php else:?><div class="leaderboard-empty">Chưa có dữ liệu Xu.</div><?php endif;?>
 </div>
 <div class="leaderboard-panel" id="panel-lv" role="tabpanel" aria-labelledby="tab-lv" hidden>
 <?php if($topLv):?><ol class="leaderboard-list"><?php foreach($topLv as $i=>$person):$xp=(int)$person['total_xp'];?><li class="leaderboard-row rank-<?=($i+1)?>"><span class="leaderboard-rank"><?=($i+1)?></span><a class="leaderboard-avatar" href="/member.php?id=<?=(int)$person['id']?>"><?php if(!empty($person['avatar_url'])):?><img src="<?=home_h($person['avatar_url'])?>" alt=""><?php else:?><?=home_h(mb_substr((string)$person['display_name'],0,1,'UTF-8'))?><?php endif;?></a><div class="leaderboard-member"><a class="leaderboard-name name-rank-<?=($i+1)?>" href="/member.php?id=<?=(int)$person['id']?>"><?=home_h($person['display_name'])?></a><small><?=home_level_name($xp)?></small></div><strong class="leaderboard-xp">LV <?=1+(int)floor($xp/100)?> <small><?=number_format($xp)?> EXP</small></strong></li><?php endforeach;?></ol><?php else:?><div class="leaderboard-empty">Chưa có dữ liệu EXP.</div><?php endif;?>
 </div>
</section>
<script>
document.querySelectorAll('.leaderboard-tab').forEach(function(tab){
 tab.addEventListener('click',function(){
  document.querySelectorAll('.leaderboard-tab').forEach(function(item){item.classList.toggle('is-active',item===tab);item.setAttribute('aria-selected',item===tab?'true':'false');});
  document.querySelectorAll('.leaderboard-panel').forEach(function(panel){panel.hidden=panel.id!=='panel-'+tab.dataset.board;});
 });
});
</script>
<section class="td-home-section td-author-section" id="featured-authors">
 <div class="td-home-section-head"><div><span class="td-home-eyebrow">NGƯỜI ĐỨNG SAU TRANG VIẾT</span><h2>Tác giả nổi bật</h2><p>Những cây bút đang chia sẻ câu chuyện tại Truyện Đêm.</p></div><a href="/community.php">Gặp gỡ cộng đồng <span>→</span></a></div>
 <?php if($featuredAuthors):?><div class="td-author-grid"><?php foreach($featuredAuthors as $author):?><a class="td-author-card" href="/member.php?id=<?=(int)$author['id']?>"><span class="td-author-avatar"><?php if(!empty($author['avatar_url'])):?><img src="<?=home_h($author['avatar_url'])?>" alt=""><?php else:?><?=home_h(mb_substr((string)$author['display_name'],0,1,'UTF-8'))?><?php endif;?></span><strong><?=home_h($author['display_name'])?></strong><small><?=number_format((int)$author['story_count'])?> tác phẩm</small><small><?=number_format((int)$author['follower_count'])?> người theo dõi</small></a><?php endforeach;?></div><?php else:?><div class="td-home-empty">Tác giả thành viên sẽ xuất hiện tại đây khi có tác phẩm được xuất bản.</div><?php endif;?>
</section>
<section class="td-home-section td-community-section" id="community-activity">
 <div class="td-home-section-head"><div><span class="td-home-eyebrow">GÓC TRÒ CHUYỆN</span><h2>Hoạt động cộng đồng</h2><p>Cùng xem thành viên Truyện Đêm đang chia sẻ điều gì.</p></div><a href="/community.php">Vào cộng đồng <span>→</span></a></div>
 <?php if($communityActivity):?><div class="td-community-list"><?php foreach($communityActivity as $post):?><article class="td-community-item"><div class="td-community-avatar"><?php if(!empty($post['avatar_url'])):?><img src="<?=home_h($post['avatar_url'])?>" alt=""><?php else:?><?=home_h(mb_substr((string)$post['display_name'],0,1,'UTF-8'))?><?php endif;?></div><div class="td-community-copy"><div class="td-community-meta"><strong><?=home_h($post['display_name'])?></strong><time><?=home_h(date('d/m/Y · H:i',strtotime($post['created_at'])))?></time></div><p><?=nl2br(home_h(mb_strimwidth((string)$post['content'],0,260,'…','UTF-8')))?></p><small>♥ <?=number_format((int)$post['like_count'])?> · <?=number_format((int)$post['comment_count'])?> bình luận</small></div></article><?php endforeach;?></div><?php else:?><div class="td-home-empty">Chưa có hoạt động mới. Hãy là người đầu tiên chia sẻ câu chuyện của mình!</div><?php endif;?>
</section>
</main><?php require __DIR__ . '/includes/footer.php'; ?><script src="/js/presence.js"></script></body></html>