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
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#090a10"><title>Truyện Đêm - Thư viện truyện chữ và audio</title><meta name="description" content="Khám phá truyện kinh dị, tình cảm, huyền huyễn và nhiều câu chuyện hấp dẫn tại Truyện Đêm."><link rel="stylesheet" href="/css/style.css?v=20261111-routes1"><link rel="stylesheet" href="/css/stories.css?v=20261111-routes1"><link rel="stylesheet" href="/css/member-hub.css?v=20261112-leaderboard2"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
<link rel="stylesheet" href="/css/home-slider.css?v=20261118-2"></head><body class="story-site" id="top">
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
 </div>
 <?php if(count($stories)>1):?><div class="td-slider-controls"><button type="button" class="td-slider-arrow td-slider-prev" aria-label="Truyện trước">‹</button><div class="td-slider-dots"><?php foreach(array_slice($stories,0,6) as $dotIndex=>$unused):?><button type="button" class="td-slider-dot<?= $dotIndex===0?' is-active':''?>" data-slide="<?=$dotIndex?>" aria-label="Đến truyện <?=$dotIndex+1?>"></button><?php endforeach;?></div><button type="button" class="td-slider-arrow td-slider-next" aria-label="Truyện tiếp">›</button></div><?php endif;?>
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
<section class="story-section" id="library"><div class="story-section-head"><h2>✧ Thư viện truyện</h2></div>
<?php if($categories):?><div class="story-categories" id="categories"><a class="<?= $category===''?'active':''?>" href="/index.php#library">Tất cả</a><?php foreach($categories as $cat):?><a class="<?= $category===$cat?'active':''?>" href="/chuyen-muc/<?=home_slug((string)$cat)?>.html"><?=home_h($cat)?></a><?php endforeach;?></div><?php endif;?>
<?php if(!$stories):?><div class="story-empty"><h3>Chưa tìm thấy truyện phù hợp</h3><p>Thư viện sẽ sớm có thêm những câu chuyện mới.</p></div><?php else:?><div class="story-grid"><?php foreach($stories as $s):?><article class="story-card"><a class="story-card-cover" href="<?=($s['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/'?><?=rawurlencode($s['slug'])?>.html"><?php if($s['cover_url']):?><img src="<?=home_h($s['cover_url'])?>" alt="<?=home_h($s['title'])?>" loading="lazy"><?php else:?>☾<?php endif;?></a><div class="story-card-body"><span class="story-tag"><?=home_h($s['category'])?></span><h3><a href="<?=($s['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/'?><?=rawurlencode($s['slug'])?>.html"><?=home_h($s['title'])?></a></h3><p><?=home_h(mb_strimwidth((string)$s['description'],0,115,'…','UTF-8'))?></p><div class="story-card-meta"><?=home_h($s['author'])?> · <?=($s['story_kind']??'serial')==='short'?'Truyện ngắn':(int)$s['chapter_count'].' chương'?><br>◉ <?=number_format((int)$s['views_count'])?> · ♥ <?=number_format((int)$s['likes_count'])?></div></div></article><?php endforeach;?></div><?php endif;?></section><?php endif;?>
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

<div class="td-launch-overlay" id="tdLaunchPopup" aria-hidden="true">
 <section class="td-launch-modal" role="dialog" aria-modal="true" aria-labelledby="tdLaunchTitle">
  <button class="td-launch-close" type="button" aria-label="Đóng thông báo">×</button>
  <div class="td-launch-top">
   <span class="td-launch-beta">BETA</span>
   <div class="td-launch-clock">
    <div class="td-launch-orbit"><strong id="tdLaunchDays">01</strong></div>
    <div class="td-launch-counts"><div class="td-launch-time"><strong id="tdLaunchHours">00</strong><small>GIỜ</small></div><i>:</i><div class="td-launch-time"><strong id="tdLaunchMinutes">00</strong><small>PHÚT</small></div><i>:</i><div class="td-launch-time"><strong id="tdLaunchSeconds">00</strong><small>GIÂY</small></div><p>Ra mắt chính thức<br><b>20/11/2026</b></p></div>
   </div>
  </div>
  <div class="td-launch-content">
   <h2 id="tdLaunchTitle">Truyện Đêm sắp ra mắt chính thức</h2>
   <p class="td-launch-intro">Cánh cửa thế giới truyện đêm đang dần mở. Chúng mình đang hoàn thiện trải nghiệm đọc truyện, sáng tác và kết nối cộng đồng dành cho bạn.</p>
   <div class="td-launch-benefit"><span class="td-launch-icon">♙</span><div><strong>Không gian dành cho tác giả</strong><p>Đăng truyện, chỉnh sửa tác phẩm và chia sẻ câu chuyện của bạn với độc giả.</p></div></div>
   <div class="td-launch-benefit"><span class="td-launch-icon">✧</span><div><strong>Cộng đồng người yêu truyện</strong><p>Kết nối độc giả, theo dõi tác giả và cùng khám phá những câu chuyện mới.</p></div></div>
   <div class="td-launch-benefit"><span class="td-launch-icon">☾</span><div><strong>Đêm nào cũng có chuyện hay</strong><p>Truyện kinh dị, tình cảm, huyền huyễn và nhiều thể loại đang chờ bạn khám phá.</p></div></div>
   <div class="td-launch-actions"><button type="button" class="td-launch-later">Để sau</button><a class="td-launch-feedback" href="/community.php">Tham gia ngay</a></div>
  </div>
 </section>
</div>
<style>
.td-launch-overlay{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:14px;background:rgba(0,0,0,.78);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px)}
.td-launch-overlay.is-visible{display:flex}
.td-launch-modal{position:relative;width:min(100%,650px);max-height:min(92vh,900px);overflow:auto;border-radius:27px;background:#fbfafc;color:#211d2c;box-shadow:0 24px 90px #0009;animation:tdLaunchIn .28s ease both}
@keyframes tdLaunchIn{from{opacity:0;transform:translateY(18px) scale(.98)}to{opacity:1;transform:none}}
.td-launch-close{position:absolute;z-index:2;right:18px;top:15px;width:34px;height:34px;border:0;background:transparent;color:#ddd;font-size:32px;line-height:1;cursor:pointer}
.td-launch-top{position:relative;overflow:hidden;padding:30px 34px 26px;background:radial-gradient(ellipse at 27% 60%,#63331e 0,#251923 43%,#141222 78%);color:#fff}
.td-launch-top:after{content:"";position:absolute;inset:0;background:radial-gradient(circle at 25% 50%,#f0783928,transparent 40%);pointer-events:none}
.td-launch-beta{position:relative;z-index:1;display:inline-block;padding:7px 17px;border:1px solid #e47b43;border-radius:30px;color:#ffb17d;font-weight:850;letter-spacing:2px}
.td-launch-clock{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-around;gap:20px;padding:20px 0 4px}
.td-launch-orbit{width:190px;height:190px;flex:none;border:1px solid #8c583f;border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative;background:radial-gradient(circle at 50% 45%,#4a2b2c 0,#211923 62%,#171522 100%);box-shadow:inset 0 0 0 5px #f0a06b0b,inset 0 0 0 13px #f0a06b0a,0 0 28px #e77b392b}
.td-launch-orbit:before{content:"";position:absolute;inset:13px;border:1px solid #b97750;border-radius:50%;box-shadow:inset 0 0 0 5px #d68b5810}
.td-launch-orbit:after{content:"";position:absolute;inset:-7px;border-radius:50%;background:conic-gradient(from -42deg,#ffd0a1 0deg,#ed854a 105deg,#bd613c 245deg,transparent 246deg 360deg);-webkit-mask:radial-gradient(farthest-side,transparent calc(100% - 2px),#000 calc(100% - 1px));mask:radial-gradient(farthest-side,transparent calc(100% - 2px),#000 calc(100% - 1px));filter:drop-shadow(0 0 5px #e9824c88)}
.td-launch-orbit>*{position:relative;z-index:1}
.td-launch-orbit-small{font-size:9px;letter-spacing:2.2px;color:#efb48f;text-transform:uppercase}
.td-launch-orbit strong{font:700 72px/.95 Georgia,serif;color:#fff5ec;margin:9px 0 6px;text-shadow:0 0 20px #f2a16c66;letter-spacing:-3px}
.td-launch-orbit b{font-size:13px;letter-spacing:5px;color:#ffb17f;text-shadow:0 0 12px #e77b3966}
.td-launch-counts{display:grid;grid-template-columns:repeat(3,minmax(54px,1fr));align-items:center;gap:8px;text-align:center}
.td-launch-time{display:flex;flex-direction:column;gap:7px}
.td-launch-time strong{padding:12px 8px;border:1px solid #55424d;border-radius:13px;background:#ffffff12;font-size:30px;font-variant-numeric:tabular-nums}
.td-launch-time small{color:#b9adbd;font-size:12px}
.td-launch-counts>i{display:none}
.td-launch-counts p{grid-column:1/-1;margin:13px 0 0;color:#c9bdcb;line-height:1.55}
.td-launch-counts p b{font-size:20px;color:#fff}
.td-launch-content{padding:29px 36px 30px}
.td-launch-content h2{margin:0 0 10px;color:#201d2c;font-size:clamp(22px,4vw,30px);line-height:1.25}
.td-launch-intro{margin:0 0 20px;color:#686372;font-size:17px;line-height:1.6}
.td-launch-benefit{display:flex;align-items:flex-start;gap:15px;margin:18px 0}
.td-launch-icon{display:grid;place-items:center;flex:none;width:54px;height:54px;border-radius:16px;background:#f7eee9;color:#d96b37;font-size:26px}
.td-launch-benefit:nth-of-type(3) .td-launch-icon{background:#f1eafa;color:#8750c4}
.td-launch-benefit strong{display:block;margin:1px 0 4px;color:#242032;font-size:17px}
.td-launch-benefit p{margin:0;color:#6c6675;font-size:15px;line-height:1.5}
.td-launch-actions{display:flex;align-items:center;justify-content:flex-end;gap:14px;margin-top:25px}
.td-launch-later{border:0;background:transparent;color:#292536;font-weight:700;font-size:16px;padding:13px 18px;cursor:pointer}
.td-launch-feedback{display:inline-flex;align-items:center;justify-content:center;min-height:52px;padding:0 25px;border-radius:15px;background:#e9672d;color:white;text-decoration:none;font-weight:800;box-shadow:0 4px 0 #b94d20}
@media(max-width:560px){.td-launch-overlay{padding:10px}.td-launch-modal{border-radius:22px;max-height:94vh}.td-launch-top{padding:23px 18px 19px}.td-launch-clock{gap:10px}.td-launch-orbit{width:132px;height:132px}.td-launch-orbit strong{font-size:49px}.td-launch-orbit-small{font-size:7px;letter-spacing:1px}.td-launch-orbit b{font-size:10px;letter-spacing:3px}.td-launch-counts{gap:5px}.td-launch-time strong{font-size:23px;padding:10px 4px}.td-launch-time small{font-size:10px}.td-launch-counts p{font-size:12px}.td-launch-counts p b{font-size:17px}.td-launch-content{padding:22px 20px 24px}.td-launch-intro{font-size:15px}.td-launch-benefit{gap:11px;margin:15px 0}.td-launch-icon{width:46px;height:46px}.td-launch-benefit strong{font-size:15px}.td-launch-benefit p{font-size:13px}.td-launch-actions{gap:8px}.td-launch-feedback{padding:0 17px;min-height:48px}.td-launch-later{padding:12px}}
@media(max-width:360px){.td-launch-clock{flex-direction:column}.td-launch-orbit{width:112px;height:112px}.td-launch-orbit strong{font-size:42px}.td-launch-counts{width:100%;max-width:260px}}
</style>
<script>
(function(){
 const popup=document.getElementById('tdLaunchPopup');if(!popup)return;
 const key='truyendem_launch_popup_dismissed';
 try{if(localStorage.getItem(key)==='1')return;}catch(e){}
 const close=()=>{popup.classList.remove('is-visible');popup.setAttribute('aria-hidden','true');try{localStorage.setItem(key,'1');}catch(e){}};
 popup.querySelector('.td-launch-close').addEventListener('click',close);
 popup.querySelector('.td-launch-later').addEventListener('click',close);
 popup.addEventListener('click',e=>{if(e.target===popup)close();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&popup.classList.contains('is-visible'))close();});
 const target=new Date(2026,10,20,0,0,0).getTime();
 const pad=n=>String(n).padStart(2,'0');
 function tick(){let left=Math.max(0,target-Date.now());const days=Math.floor(left/86400000);left%=86400000;const hours=Math.floor(left/3600000);left%=3600000;const mins=Math.floor(left/60000);const secs=Math.floor((left%60000)/1000);document.getElementById('tdLaunchDays').textContent=pad(days);document.getElementById('tdLaunchHours').textContent=pad(hours);document.getElementById('tdLaunchMinutes').textContent=pad(mins);document.getElementById('tdLaunchSeconds').textContent=pad(secs);if(target<=Date.now())document.querySelector('.td-launch-counts p').innerHTML='Truyện Đêm đã chính thức ra mắt!';}
 tick();setInterval(tick,1000);
 setTimeout(()=>{popup.classList.add('is-visible');popup.setAttribute('aria-hidden','false');},700);
})();
</script>
</main><?php require __DIR__ . '/includes/footer.php'; ?><script src="/js/presence.js"></script></body></html>