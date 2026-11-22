<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/experience.php';
require_once __DIR__ . '/includes/story_rewards.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$slug=trim((string)($_GET['slug']??''));$chapterNumber=filter_input(INPUT_GET,'chapter',FILTER_VALIDATE_INT);
if($slug!==''&&$chapterNumber){$q=$pdo->prepare("SELECT c.*,s.id story_id,s.title story_title,s.slug,s.story_kind,s.member_id FROM chapters c JOIN stories s ON s.id=c.story_id WHERE s.slug=? AND c.chapter_number=? AND c.status='published' AND s.status='published' LIMIT 1");$q->execute([$slug,$chapterNumber]);}
else{$q=$pdo->prepare("SELECT c.*,s.id story_id,s.title story_title,s.slug,s.story_kind,s.member_id FROM chapters c JOIN stories s ON s.id=c.story_id WHERE c.id=? AND c.status='published' AND s.status='published' LIMIT 1");$q->execute([$id?:0]);}
$chapter=$q->fetch();
if(!$chapter){http_response_code(404);exit('Không tìm thấy chương truyện.');}
$memberId=(int)($_SESSION['member_id']??0);
if($memberId){td_reward_story_view($pdo,(int)$chapter['story_id'],$memberId,(int)($chapter['member_id']??0),'chapter',(int)$chapter['id']);td_add_xp($pdo,$memberId,'read_chapter:'.(int)$chapter['id'],10,'Đọc chương truyện');$q=$pdo->prepare('INSERT INTO story_reading_history(member_id,story_id,chapter_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE chapter_id=VALUES(chapter_id),updated_at=CURRENT_TIMESTAMP');$q->execute([$memberId,$chapter['story_id'],$chapter['id']]);}
$q=$pdo->prepare("SELECT c.id,c.chapter_number,c.title,s.slug FROM chapters c JOIN stories s ON s.id=c.story_id WHERE c.story_id=? AND c.status='published' AND s.status='published' ORDER BY c.chapter_number");$q->execute([$chapter['story_id']]);$all=$q->fetchAll();$prev=$next=null;
foreach($all as $i=>$c){if((int)$c['id']===(int)$chapter['id']){$prev=$all[$i-1]??null;$next=$all[$i+1]??null;break;}}
function rd_url(array $chapter): string { return '/truyen/'.rawurlencode((string)$chapter['slug']).'/chuong-'.(int)$chapter['chapter_number'].'.html'; }
function rd_h($v){return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function rd_paragraphs($value): string {
    $value = trim((string)$value);
    if ($value === '') return '';
    $blocks = preg_split('/\\R{2,}/u', $value) ?: [];
    $html = [];
    foreach ($blocks as $block) {
        $safe = rd_h(trim($block));
        $safe = preg_replace_callback('~\\[img\\](/uploads/stories/[a-zA-Z0-9._-]+)\\[/img\\]~', function($m) {
            return '<img class="reader-inline-image" src="' . rd_h($m[1]) . '" alt="Ảnh minh họa truyện" loading="lazy">';
        }, $safe);
        if (trim(strip_tags($safe)) === '' && str_contains($safe, '<img')) $html[] = $safe;
        elseif (trim($safe) !== '') $html[] = '<p>' . nl2br($safe, false) . '</p>';
    }
    return implode("\n", $html);
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=rd_h($chapter['story_title'])?> - Chương <?= (int)$chapter['chapter_number']?> | Truyện Đêm</title><link rel="stylesheet" href="/css/style.css?v=20261111-reader2"><link rel="stylesheet" href="/css/stories.css?v=20261111-reader2"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1"><link rel="stylesheet" href="/css/reader-redesign.css?v=20261122-2">
</head><body class="story-site reader-site"><?php require __DIR__ . '/includes/header.php'; ?>
<div class="reader-progress-track"><span id="readerProgress"></span></div>
<main class="reader-main reader-layout">
 <div class="reader-breadcrumb"><a href="<?=($chapter['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/'?><?=rawurlencode($chapter['slug'])?>.html">← <?=rd_h($chapter['story_title'])?></a><span>Đang đọc · Chương <?= (int)$chapter['chapter_number']?></span></div>
 <header class="reader-heading">
  <span class="reader-kicker">TRUYỆN ĐÊM <i></i> CHƯƠNG <?= (int)$chapter['chapter_number']?></span>
  <h1><?=rd_h($chapter['title'])?></h1>
  <p><?=rd_h($chapter['story_title'])?> <span>·</span> Chúc bạn có những phút giây thư giãn.</p>
 </header>
 <section class="reader-tools" aria-label="Tùy chỉnh giao diện đọc">
  <div class="reader-tools-label"><span>☷</span> Tùy chỉnh đọc</div>
  <div class="reader-tools-controls">
   <div class="reader-font-controls" aria-label="Cỡ chữ"><button type="button" id="readerFontDown" aria-label="Giảm cỡ chữ">A−</button><span id="readerFontValue">18</span><button type="button" id="readerFontUp" aria-label="Tăng cỡ chữ">A+</button></div>
   <div class="reader-theme-controls" aria-label="Màu nền">
    <button type="button" data-reader-theme="light" class="is-active" aria-label="Nền sáng" title="Sáng">☀</button>
    <button type="button" data-reader-theme="sepia" aria-label="Nền giấy" title="Giấy">▤</button>
    <button type="button" data-reader-theme="dark" aria-label="Nền tối" title="Tối">☾</button>
   </div>
   <button type="button" class="reader-reset" id="readerReset">Mặc định</button>
  </div>
 </section>
 <?php if($chapter['audio_url']):?><audio class="story-audio reader-audio" controls preload="none" src="<?=rd_h($chapter['audio_url'])?>">Trình duyệt không hỗ trợ audio.</audio><?php endif;?>
 <article class="reader-content" id="readerContent"><?=rd_paragraphs($chapter['content'])?></article>
 <div class="reader-reading-note"><span>✦</span> Bạn đang ở chương <?= (int)$chapter['chapter_number']?>. Tiến độ đọc được lưu trên thiết bị này.</div>
 <p class="reader-report-link"><a href="/report.php?story_id=<?=(int)$chapter['story_id']?>&chapter_id=<?=(int)$chapter['id']?>">⚑ Báo cáo vi phạm chương này</a></p>
 <nav class="reader-pagination reader-pagination-bottom">
  <?php if($prev):?><a class="reader-prev" href="<?=rd_url($prev)?>"><small>CHƯƠNG TRƯỚC</small><strong>← <?=rd_h($prev['title'])?></strong></a><?php else:?><span></span><?php endif;?>
  <?php if($next):?><a class="reader-next" href="<?=rd_url($next)?>"><small>CHƯƠNG TIẾP THEO</small><strong><?=rd_h($next['title'])?> →</strong></a><?php else:?><a class="reader-next" href="/truyen/<?=rawurlencode($chapter['slug'])?>.html"><small>ĐÃ ĐỌC HẾT</small><strong>Danh sách chương →</strong></a><?php endif;?>
 </nav>
</main><?php require __DIR__ . '/includes/footer.php'; ?>
<script>
(function(){
 var body=document.body,content=document.getElementById('readerContent'),fontValue=document.getElementById('readerFontValue');
 var key='td_reader_preferences_v1',progressKey='td_reader_progress_<?= (int)$chapter['id']?>';
 var prefs={font:18,theme:'light'};
 try{var saved=JSON.parse(localStorage.getItem(key)||'{}');if(saved.font)prefs.font=Math.max(16,Math.min(26,Number(saved.font)));if(['light','sepia','dark'].includes(saved.theme))prefs.theme=saved.theme;}catch(e){}
 function apply(){body.dataset.readerTheme=prefs.theme;content.style.setProperty('--reader-font-size',prefs.font+'px');fontValue.textContent=String(prefs.font);document.querySelectorAll('[data-reader-theme]').forEach(function(b){b.classList.toggle('is-active',b.dataset.readerTheme===prefs.theme)});try{localStorage.setItem(key,JSON.stringify(prefs))}catch(e){}}
 document.getElementById('readerFontDown').addEventListener('click',function(){prefs.font=Math.max(16,prefs.font-1);apply()});
 document.getElementById('readerFontUp').addEventListener('click',function(){prefs.font=Math.min(26,prefs.font+1);apply()});
 document.querySelectorAll('[data-reader-theme]').forEach(function(b){b.addEventListener('click',function(){prefs.theme=b.dataset.readerTheme;apply()})});
 document.getElementById('readerReset').addEventListener('click',function(){prefs={font:18,theme:'light'};apply()});
 apply();
 var bar=document.getElementById('readerProgress'),raf=false;
 function updateProgress(){var doc=document.documentElement,max=doc.scrollHeight-window.innerHeight,ratio=max>0?Math.min(1,Math.max(0,window.scrollY/max)):0;bar.style.width=(ratio*100)+'%';try{localStorage.setItem(progressKey,String(Math.round(ratio*1000)))}catch(e){}raf=false}
 window.addEventListener('scroll',function(){if(!raf){raf=true;requestAnimationFrame(updateProgress)}},{passive:true});window.addEventListener('resize',updateProgress);updateProgress();
})();
</script><script>
(function(){
 var storyId=<?= (int)$chapter['story_id'] ?>, targetType='chapter', targetId=<?= (int)$chapter['id'] ?>, audioOnly=false;
 if(!storyId||!targetId)return;
 var audio=document.getElementById('listen-audio');
 var endpoint='/track_read.php';
 function active(){return audioOnly ? !!(audio && !audio.paused && !audio.ended) : document.visibilityState==='visible';}
 function send(mode){
  if(!active())return;
  var body=new URLSearchParams({story_id:String(storyId),target_type:targetType,target_id:String(targetId),mode:mode});
  fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','X-Requested-With':'XMLHttpRequest'},body:body.toString(),keepalive:true}).catch(function(){});
 }
 send('start');
 window.setInterval(function(){if(active())send('ping');},15000);
})();
</script></body></html>