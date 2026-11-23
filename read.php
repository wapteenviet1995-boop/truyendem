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
$memberId=(int)($_SESSION['member_id']??0);$authorId=(int)($chapter['member_id']??0);
$isLocked=(int)($chapter['is_locked']??0)===1 && $memberId!==$authorId;$isUnlocked=false;$unlockError='';$unlockNotice='';
if($memberId){
 if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
 if($isLocked){$uq=$pdo->prepare('SELECT 1 FROM chapter_unlocks WHERE member_id=? AND chapter_id=? LIMIT 1');$uq->execute([$memberId,(int)$chapter['id']]);$isUnlocked=(bool)$uq->fetchColumn();}
}
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='unlock_chapter'){
 if(!$memberId){header('Location: /login.php?next='.rawurlencode('/read.php?id='.(int)$chapter['id']));exit;}
 if(!hash_equals((string)($_SESSION['member_csrf']??''),(string)($_POST['csrf']??''))){$unlockError='Phiên đã hết hạn. Hãy tải lại trang.';}
 else{
  try{
   $pdo->beginTransaction();
   $lockQ=$pdo->prepare('SELECT c.is_locked,c.unlock_price,s.member_id,s.title FROM chapters c JOIN stories s ON s.id=c.story_id WHERE c.id=? FOR UPDATE');$lockQ->execute([(int)$chapter['id']]);$lockedRow=$lockQ->fetch();
   if(!$lockedRow)throw new RuntimeException('Không tìm thấy chương.');
   $payAuthor=(int)$lockedRow['member_id'];$price=(int)$lockedRow['unlock_price'];
   if($payAuthor===$memberId||(int)$lockedRow['is_locked']!==1){$pdo->commit();header('Location: /read.php?id='.(int)$chapter['id']);exit;}
   $already=$pdo->prepare('SELECT id FROM chapter_unlocks WHERE member_id=? AND chapter_id=? LIMIT 1 FOR UPDATE');$already->execute([$memberId,(int)$chapter['id']]);
   if($already->fetchColumn()){$pdo->commit();header('Location: /read.php?id='.(int)$chapter['id']);exit;}
   if($payAuthor<1||$price<1||$price>100000)throw new RuntimeException('Thiết lập giá mở khóa không hợp lệ.');
   $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$memberId]);
   $pdo->prepare('INSERT IGNORE INTO member_wallets(member_id,balance) VALUES(?,0)')->execute([$payAuthor]);
   $walletIds=[$memberId,$payAuthor];sort($walletIds);$wq=$pdo->prepare('SELECT member_id,balance FROM member_wallets WHERE member_id IN (?,?) ORDER BY member_id FOR UPDATE');$wq->execute($walletIds);$walletRows=$wq->fetchAll();$buyerBalance=0;foreach($walletRows as $wr){if((int)$wr['member_id']===$memberId)$buyerBalance=(int)$wr['balance'];}
   if($buyerBalance<$price)throw new RuntimeException('Bạn không đủ Xu để mở khóa chương này.');
   $pdo->prepare('INSERT INTO chapter_unlocks(member_id,chapter_id,author_id,price_paid) VALUES(?,?,?,?)')->execute([$memberId,(int)$chapter['id'],$payAuthor,$price]);
   $pdo->prepare('UPDATE member_wallets SET balance=balance-? WHERE member_id=?')->execute([$price,$memberId]);
   $pdo->prepare('UPDATE member_wallets SET balance=balance+? WHERE member_id=?')->execute([$price,$payAuthor]);
   $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description,reference_id) VALUES(?,?,'chapter_unlock',?,?)")->execute([$memberId,-$price,'Mở khóa chương: '.$chapter['title'],(int)$chapter['id']]);
   $pdo->prepare("INSERT INTO wallet_transactions(member_id,amount,type,description,reference_id) VALUES(?,?,'chapter_earning',?,?)")->execute([$payAuthor,$price,'Xu nhận từ mở khóa chương: '.$chapter['title'],(int)$chapter['id']]);
   $pdo->commit();header('Location: /read.php?id='.(int)$chapter['id'].'&unlocked=1');exit;
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$unlockError=$e instanceof RuntimeException?$e->getMessage():'Giao dịch chưa thành công. Vui lòng thử lại.';}
 }
}
if($isLocked&&$memberId&&!$isUnlocked){$uq=$pdo->prepare('SELECT 1 FROM chapter_unlocks WHERE member_id=? AND chapter_id=? LIMIT 1');$uq->execute([$memberId,(int)$chapter['id']]);$isUnlocked=(bool)$uq->fetchColumn();}
$canRead=!$isLocked||$isUnlocked;
if(isset($_GET['unlocked']))$unlockNotice='Mở khóa thành công! Bạn có thể đọc chương này bất cứ lúc nào.';
$savedProgress=0.0;$readerCsrf=(string)($_SESSION['member_csrf']??'');
if($memberId&&$canRead){
 if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
 $readerCsrf=(string)$_SESSION['member_csrf'];
 td_reward_story_view($pdo,(int)$chapter['story_id'],$memberId,(int)($chapter['member_id']??0),'chapter',(int)$chapter['id']);
 td_add_xp($pdo,$memberId,'read_chapter:'.(int)$chapter['id'],10,'Đọc chương truyện');
 $q=$pdo->prepare('INSERT INTO story_reading_history(member_id,story_id,chapter_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE progress_percent=IF(chapter_id<>VALUES(chapter_id),0,progress_percent),chapter_id=VALUES(chapter_id),updated_at=CURRENT_TIMESTAMP');
 $q->execute([$memberId,$chapter['story_id'],$chapter['id']]);
 $q=$pdo->prepare('SELECT progress_percent FROM story_reading_history WHERE member_id=? AND story_id=? LIMIT 1');
 $q->execute([$memberId,$chapter['story_id']]);$savedProgress=(float)$q->fetchColumn();
}
$q=$pdo->prepare("SELECT c.id,c.chapter_number,c.title,c.is_locked,c.unlock_price,s.slug FROM chapters c JOIN stories s ON s.id=c.story_id WHERE c.story_id=? AND c.status='published' AND s.status='published' ORDER BY c.chapter_number");$q->execute([$chapter['story_id']]);$all=$q->fetchAll();$prev=$next=null;
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
<style>.reader-locked-panel{max-width:720px;margin:28px auto;padding:30px 22px;text-align:center;border:1px solid #b99adf;border-radius:18px;background:rgba(130,95,170,.10)}.reader-locked-icon{font-size:42px;margin-bottom:10px}.reader-locked-panel h2{margin:10px 0}.reader-locked-panel p{color:var(--reader-muted,#888);line-height:1.7}.reader-locked-price{display:block;margin:18px 0;font-size:26px;color:#c29af2}.reader-unlock-button{display:inline-block;border:0;border-radius:12px;padding:13px 22px;background:#a980df;color:#1c1426;font-weight:800;text-decoration:none;cursor:pointer}.reader-wallet-link{display:block;margin-top:12px;color:inherit}.reader-locked-error{color:#d65b70!important;font-weight:700}</style></head><body class="story-site reader-site"><?php require __DIR__ . '/includes/header.php'; ?>
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
 <?php if($canRead): ?><article class="reader-content" id="readerContent"><?=rd_paragraphs($chapter['content'])?></article><?php else: ?><section class="reader-locked-panel"><div class="reader-locked-icon">🔒</div><span class="reader-kicker">CHƯƠNG CÓ PHÍ</span><h2>Chương này đang được tác giả khóa</h2><p>Nội dung sẽ được mở ngay sau khi bạn xác nhận thanh toán.</p><strong class="reader-locked-price">✦ <?=number_format((int)$chapter['unlock_price'])?> Xu</strong><?php if($unlockError): ?><p class="reader-locked-error"><?=rd_h($unlockError)?></p><?php endif; ?><?php if(!$memberId): ?><a class="reader-unlock-button" href="/login.php?next=<?=rawurlencode('/read.php?id='.(int)$chapter['id'])?>">Đăng nhập để mở khóa</a><?php else: ?><form method="post" onsubmit="return confirm('Mở khóa chương này với <?=number_format((int)$chapter['unlock_price'])?> Xu?');"><input type="hidden" name="csrf" value="<?=rd_h($readerCsrf)?>"><input type="hidden" name="action" value="unlock_chapter"><button class="reader-unlock-button" type="submit">Mở khóa bằng <?=number_format((int)$chapter['unlock_price'])?> Xu</button></form><a class="reader-wallet-link" href="/shop.php">Nạp / nhận thêm Xu</a><?php endif; ?></section><?php endif; ?>
 <?php if($canRead): ?><div class="reader-reading-note"><span>✦</span> Bạn đang ở chương <?= (int)$chapter['chapter_number']?>. Tiến độ đọc được lưu trên thiết bị này.</div><?php else: ?><div class="reader-reading-note">🔒 Mở khóa để xem nội dung đầy đủ.</div><?php endif; ?>
 <div class="reader-report-link"><a href="/report.php?story_id=<?=(int)$chapter['story_id']?>&chapter_id=<?=(int)$chapter['id']?>">⚑ Báo cáo vi phạm chương này</a><button type="button" class="td-reader-share" data-share-page>↗ Chia sẻ</button></div>
 <nav class="reader-pagination reader-pagination-bottom">
  <?php if($prev):?><a class="reader-prev" href="<?=rd_url($prev)?>"><small>CHƯƠNG TRƯỚC</small><strong>← <?=rd_h($prev['title'])?></strong></a><?php else:?><span></span><?php endif;?>
  <?php if($next):?><a class="reader-next" href="<?=rd_url($next)?>"><small>CHƯƠNG TIẾP THEO</small><strong><?=rd_h($next['title'])?> →</strong></a><?php else:?><a class="reader-next" href="/truyen/<?=rawurlencode($chapter['slug'])?>.html"><small>ĐÃ ĐỌC HẾT</small><strong>Danh sách chương →</strong></a><?php endif;?>
 </nav>
</main><?php require __DIR__ . '/includes/footer.php'; ?>
<?php if($canRead): ?><script>
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
 var bar=document.getElementById('readerProgress'),raf=false,saveTimer=null,lastSaved=-1;
 var memberProgress=<?=json_encode($savedProgress)?>,memberCsrf=<?=json_encode($readerCsrf)?>,isMember=<?= $memberId? 'true':'false' ?>;
 function currentRatio(){var doc=document.documentElement,max=doc.scrollHeight-window.innerHeight;return max>0?Math.min(1,Math.max(0,window.scrollY/max)):0}
 function saveToAccount(ratio){
  if(!isMember)return;
  var percent=Math.round(ratio*10000)/100;
  if(Math.abs(percent-lastSaved)<0.75)return;
  lastSaved=percent;
  var body=new URLSearchParams({chapter_id:String(<?= (int)$chapter['id'] ?>),progress_percent:String(percent),csrf:memberCsrf});
  fetch('/save_reading_progress.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','X-Requested-With':'XMLHttpRequest'},body:body.toString(),keepalive:true}).catch(function(){lastSaved=-1});
 }
 function updateProgress(){
  var ratio=currentRatio();bar.style.width=(ratio*100)+'%';
  try{localStorage.setItem(progressKey,String(Math.round(ratio*1000)))}catch(e){}
  if(saveTimer)window.clearTimeout(saveTimer);
  saveTimer=window.setTimeout(function(){saveToAccount(ratio)},700);
  raf=false;
 }
 function restoreProgress(){
  var ratio=isMember?Math.max(0,Math.min(1,memberProgress/100)):0;
  if(!isMember){try{var local=localStorage.getItem(progressKey);if(local!==null)ratio=Math.max(0,Math.min(1,Number(local)/1000))}catch(e){}}
  if(ratio>0.005&&ratio<0.995){var max=document.documentElement.scrollHeight-window.innerHeight;window.scrollTo(0,Math.max(0,max*ratio));}
  updateProgress();
 }
 window.addEventListener('scroll',function(){if(!raf){raf=true;requestAnimationFrame(updateProgress)}},{passive:true});
 window.addEventListener('resize',updateProgress);
 function flushProgress(){if(isMember){if(saveTimer){window.clearTimeout(saveTimer);saveTimer=null;}saveToAccount(currentRatio());}}
 window.addEventListener('pagehide',flushProgress);
 document.addEventListener('visibilitychange',function(){if(document.visibilityState==='hidden')flushProgress();});
 window.addEventListener('load',function(){window.setTimeout(restoreProgress,250)},{once:true});
 window.setTimeout(restoreProgress,350);
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
</script><?php endif; ?><script>
(function(){
 function copyLink(){
  var url=window.location.href;
  if(navigator.clipboard&&window.isSecureContext)return navigator.clipboard.writeText(url);
  return new Promise(function(resolve,reject){
   var field=document.createElement('textarea');field.value=url;field.style.position='fixed';field.style.opacity='0';document.body.appendChild(field);field.select();
   var ok=document.execCommand('copy');field.remove();ok?resolve():reject(new Error('copy'));
  });
 }
 document.querySelectorAll('[data-share-page]').forEach(function(button){
  button.addEventListener('click',async function(){
   var data={title:document.title,text:'Mời bạn đọc truyện này trên Truyện Đêm:',url:window.location.href};
   try{if(navigator.share){await navigator.share(data);return;}await copyLink();button.textContent='✓ Đã sao chép';window.setTimeout(function(){button.textContent='↗ Chia sẻ';},2200);}
   catch(error){if(error&&error.name==='AbortError')return;button.textContent='Thử lại';window.setTimeout(function(){button.textContent='↗ Chia sẻ';},2200);}
  });
 });
})();
</script></body></html>