<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf']; $error='';
function audio_h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function audio_back(int $story=0): void { header('Location: audio.php'.($story?'?story='.$story:'')); exit; }
try {
 $pdo->query('SELECT 1 FROM audio_tracks LIMIT 1');
} catch(Throwable $e) {
 http_response_code(503);
 ?><!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Thiết lập thư viện audio</title><link rel="stylesheet" href="../css/admin-white.css"><body class="admin-theme"><main class="admin-wrap"><section class="admin-panel admin-wide"><h1>Thiết lập thư viện audio</h1><p>Cần tạo bảng lưu danh sách audio trước khi sử dụng.</p><p>Mở phpMyAdmin, chọn cơ sở dữ liệu Truyện Đêm và chạy file <b>database/audio_tracks.sql</b> trong GitHub.</p><a href="index.php">← Quay lại quản trị</a></section></main></body></html><?php exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên làm việc hết hạn. Vui lòng tải lại trang.';}
 else {
  $action=(string)($_POST['action']??''); $storyId=(int)($_POST['story_id']??0);
  try {
   if($action==='save_track'){
    $id=(int)($_POST['track_id']??0); $title=trim((string)($_POST['title']??'')); $url=trim((string)($_POST['audio_url']??'')); $source=($_POST['source_type']??'link')==='upload'?'upload':'link';
    $duration=max(0,(int)($_POST['duration_seconds']??0)); $order=max(0,(int)($_POST['track_order']??0)); $status=($_POST['status']??'published')==='draft'?'draft':'published'; $chapterId=(int)($_POST['chapter_id']??0) ?: null;
    $check=$pdo->prepare('SELECT id FROM stories WHERE id=?');$check->execute([$storyId]);if(!$check->fetchColumn())throw new RuntimeException('Vui lòng chọn truyện hợp lệ.');
    if($chapterId){$check=$pdo->prepare('SELECT id FROM chapters WHERE id=? AND story_id=?');$check->execute([$chapterId,$storyId]);if(!$check->fetchColumn())throw new RuntimeException('Chương không thuộc truyện đã chọn.');}
    if($title==='')throw new RuntimeException('Vui lòng nhập tên audio.');
    if(!empty($_FILES['audio_file']['name'])){
     $f=$_FILES['audio_file'];
     if(($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Tải file thất bại. Kiểm tra giới hạn upload của hosting.');
     if((int)$f['size']>25*1024*1024)throw new RuntimeException('File vượt quá 25 MB. Hãy dùng link MP3 hoặc nén file.');
     $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
     $allowed=['audio/mpeg'=>'mp3','audio/mp3'=>'mp3','audio/mp4'=>'m4a','audio/x-m4a'=>'m4a','audio/aac'=>'aac','audio/ogg'=>'ogg','application/ogg'=>'ogg','audio/wav'=>'wav','audio/x-wav'=>'wav','audio/vnd.wave'=>'wav'];
     if(!isset($allowed[$mime]))throw new RuntimeException('Chỉ nhận MP3, M4A, AAC, OGG hoặc WAV.');
     $dir=__DIR__.'/../uploads/audio';if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('Không tạo được thư mục uploads/audio.');
     $name=bin2hex(random_bytes(18)).'.'.$allowed[$mime];if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Không lưu được file audio lên hosting.');
     $url='/uploads/audio/'.$name;$source='upload';
    } elseif($url===''){
     if($id){$q=$pdo->prepare('SELECT audio_url,source_type FROM audio_tracks WHERE id=?');$q->execute([$id]);$old=$q->fetch();if($old){$url=$old['audio_url'];$source=$old['source_type'];}}
    }
    if($url===''||($source==='link'&&!filter_var($url,FILTER_VALIDATE_URL)))throw new RuntimeException('Hãy tải file lên hoặc nhập đường dẫn MP3 hợp lệ.');
    if($id){$q=$pdo->prepare('UPDATE audio_tracks SET story_id=?,chapter_id=?,title=?,audio_url=?,source_type=?,duration_seconds=?,track_order=?,status=? WHERE id=?');$q->execute([$storyId,$chapterId,$title,$url,$source,$duration,$order,$status,$id]);}
    else{$q=$pdo->prepare('INSERT INTO audio_tracks(story_id,chapter_id,title,audio_url,source_type,duration_seconds,track_order,status) VALUES(?,?,?,?,?,?,?,?)');$q->execute([$storyId,$chapterId,$title,$url,$source,$duration,$order,$status]);}
    audio_back($storyId);
   }elseif($action==='delete_track'){
    $id=(int)($_POST['track_id']??0);$q=$pdo->prepare('DELETE FROM audio_tracks WHERE id=?');$q->execute([$id]);audio_back($storyId);
   }
  }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'Không thể lưu audio. Kiểm tra dữ liệu và cấu hình cơ sở dữ liệu.';}
 }
}
$stories=$pdo->query("SELECT id,title,story_type,story_kind FROM stories WHERE story_type IN ('audio','both') ORDER BY title")->fetchAll();
$selected=(int)($_GET['story']??$_POST['story_id']??0);$editId=(int)($_GET['edit']??0);$edit=null;
if($editId){$q=$pdo->prepare('SELECT * FROM audio_tracks WHERE id=?');$q->execute([$editId]);$edit=$q->fetch()?:null;if($edit)$selected=(int)$edit['story_id'];}
if(!$selected&&$stories)$selected=(int)$stories[0]['id'];
$chapters=[];if($selected){$q=$pdo->prepare('SELECT id,chapter_number,title FROM chapters WHERE story_id=? ORDER BY chapter_number');$q->execute([$selected]);$chapters=$q->fetchAll();}
$tracks=$pdo->query("SELECT a.*,s.title AS story_title,s.slug AS story_slug,c.chapter_number,c.title AS chapter_title FROM audio_tracks a JOIN stories s ON s.id=a.story_id LEFT JOIN chapters c ON c.id=a.chapter_id ORDER BY s.title,a.track_order,a.id")->fetchAll();
$form=$edit?:['id'=>0,'story_id'=>$selected,'chapter_id'=>null,'title'=>'','audio_url'=>'','source_type'=>'link','duration_seconds'=>0,'track_order'=>count($tracks)+1,'status'=>'published'];
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quản lý truyện audio | Truyện Đêm</title><link rel="stylesheet" href="../css/admin-white.css?v=20261120-audio1"><link rel="stylesheet" href="../css/admin-audio.css?v=20261120-1"></head><body class="admin-theme"><main class="audio-admin">
<header class="audio-admin-head"><div><span class="audio-eyebrow">TRUYỆN ĐÊM / STUDIO</span><h1>Quản lý truyện audio</h1><p>Thêm file âm thanh hoặc liên kết MP3, sắp xếp từng track thành danh sách phát.</p></div><nav><a href="index.php">← Quản trị</a><a href="../index.php" target="_blank">Xem thư viện ↗</a></nav></header>
<?php if($error):?><div class="audio-alert"><?=audio_h($error)?></div><?php endif;?>
<?php if(!$stories):?><section class="audio-panel"><h2>Chưa có truyện audio</h2><p>Trước tiên hãy tạo truyện và chọn loại nội dung “Truyện audio” hoặc “Chữ + audio”.</p><a class="audio-primary" href="stories.php">Tạo truyện audio</a></section><?php else:?>
<div class="audio-layout"><section class="audio-panel audio-editor"><div class="audio-panel-title"><div><span class="audio-kicker">THƯ VIỆN ÂM THANH</span><h2><?=$edit?'Chỉnh sửa track':'Thêm audio mới'?></h2></div><span class="audio-icon">♫</span></div>
<form method="post" enctype="multipart/form-data" id="audio-form"><input type="hidden" name="csrf" value="<?=audio_h($csrf)?>"><input type="hidden" name="action" value="save_track"><input type="hidden" name="track_id" value="<?=(int)$form['id']?>">
<label>Truyện<select name="story_id" id="audio-story" required><?php foreach($stories as $s):?><option value="<?=(int)$s['id']?>" <?=(int)$s['id']===(int)$form['story_id']?'selected':''?>><?=audio_h($s['title'])?></option><?php endforeach;?></select></label>
<label>Gắn vào chương <span>(không bắt buộc)</span><select name="chapter_id" id="audio-chapter"><option value="">Audio toàn truyện / không gắn chương</option><?php foreach($chapters as $c):?><option value="<?=(int)$c['id']?>" <?=(int)$c['id']===(int)$form['chapter_id']?'selected':''?>>Chương <?=(int)$c['chapter_number']?> · <?=audio_h($c['title'])?></option><?php endforeach;?></select></label>
<label>Tên track / tập audio<input name="title" required maxlength="220" value="<?=audio_h($form['title'])?>" placeholder="Ví dụ: Tập 01 – Tiếng gõ lúc nửa đêm"></label>
<div class="audio-source-switch"><label><input type="radio" name="source_type" value="upload" <?=$form['source_type']==='upload'?'checked':''?>> Tải file từ điện thoại</label><label><input type="radio" name="source_type" value="link" <?=$form['source_type']==='link'?'checked':''?>> Dán link MP3</label></div>
<div id="audio-upload-box"><label>Chọn file âm thanh<input type="file" name="audio_file" accept=".mp3,.m4a,.aac,.ogg,.wav,audio/*"><small>MP3, M4A, AAC, OGG, WAV · tối đa 25 MB mỗi file. Giới hạn thực tế còn phụ thuộc hosting.</small></label></div>
<div id="audio-link-box"><label>Đường dẫn file MP3<input type="url" name="audio_url" value="<?=audio_h($form['source_type']==='link'?$form['audio_url']:'')?>" placeholder="https://ten-may-chu.com/audio/tap-01.mp3"></label></div>
<div class="audio-fields"><label>Thời lượng (giây)<input type="number" min="0" name="duration_seconds" value="<?=(int)$form['duration_seconds']?>"></label><label>Thứ tự phát<input type="number" min="0" name="track_order" value="<?=(int)$form['track_order']?>"></label><label>Trạng thái<select name="status"><option value="published" <?=$form['status']==='published'?'selected':''?>>Hiển thị</option><option value="draft" <?=$form['status']==='draft'?'selected':''?>>Ẩn / bản nháp</option></select></label></div>
<div class="audio-form-actions"><button class="audio-primary" type="submit"><?=$edit?'Lưu thay đổi':'＋ Lưu track audio'?></button><?php if($edit):?><a class="audio-secondary" href="audio.php?story=<?=$selected?>">Hủy chỉnh sửa</a><?php endif;?></div></form></section>
<section class="audio-panel audio-help"><span class="audio-kicker">QUY TRÌNH ĐĂNG</span><h2>Một truyện, nhiều track</h2><ol><li>Chọn truyện audio.</li><li>Chọn tải file trực tiếp hoặc dán link MP3.</li><li>Nhập tên và thứ tự phát.</li><li>Lưu để track xuất hiện trong danh sách bên dưới.</li></ol><div class="audio-tip"><b>Mẹo:</b> File dung lượng lớn nên lưu ở dịch vụ lưu trữ audio và dán link MP3 để tránh đầy hosting.</div></section></div>
<section class="audio-panel audio-list-panel"><div class="audio-panel-title"><div><span class="audio-kicker">PLAYLIST MANAGER</span><h2>Danh sách audio đã đăng <small><?=count($tracks)?> track</small></h2></div><select class="audio-filter" id="audio-filter"><option value="all">Tất cả truyện</option><?php foreach($stories as $s):?><option value="<?=$s['id']?>"><?=audio_h($s['title'])?></option><?php endforeach;?></select></div>
<?php if(!$tracks):?><p class="audio-empty">Chưa có track audio nào. Hãy thêm track đầu tiên ở phía trên.</p><?php else:?><div class="audio-track-list"><?php foreach($tracks as $t):?><article class="audio-track-row" data-story="<?=$t['story_id']?>"><div class="audio-track-number"><?=str_pad((string)$t['track_order'],2,'0',STR_PAD_LEFT)?></div><div class="audio-track-info"><strong><?=audio_h($t['title'])?></strong><small><?=audio_h($t['story_title'])?><?=$t['chapter_id']?' · Chương '.(int)$t['chapter_number'].' – '.audio_h($t['chapter_title']):' · Audio toàn truyện'?></small><audio controls preload="none" src="<?=audio_h($t['audio_url'])?>"></audio></div><div class="audio-track-meta"><span class="audio-pill <?=$t['status']==='published'?'is-live':'is-draft'?>"><?=$t['status']==='published'?'Đang hiển thị':'Bản nháp'?></span><small><?=$t['source_type']==='upload'?'File đã tải lên':'Liên kết ngoài'?></small></div><div class="audio-track-actions"><a href="../audio/<?=rawurlencode((string)$t['story_slug'])?>.html" target="_blank">Xem</a><a href="audio.php?edit=<?=(int)$t['id']?>">Sửa</a><form method="post" onsubmit="return confirm('Xóa track audio này?')"><input type="hidden" name="csrf" value="<?=audio_h($csrf)?>"><input type="hidden" name="action" value="delete_track"><input type="hidden" name="track_id" value="<?=(int)$t['id']?>"><input type="hidden" name="story_id" value="<?=(int)$t['story_id']?>"><button type="submit" class="audio-delete">Xóa</button></form></div></article><?php endforeach;?></div><?php endif;?></section>
<?php endif;?></main><script>
(()=>{const radios=[...document.querySelectorAll('[name="source_type"]')],up=document.getElementById('audio-upload-box'),link=document.getElementById('audio-link-box');function sync(){const v=radios.find(x=>x.checked)?.value;up.hidden=v!=='upload';link.hidden=v!=='link';}radios.forEach(x=>x.addEventListener('change',sync));sync();
const filter=document.getElementById('audio-filter');filter?.addEventListener('change',()=>document.querySelectorAll('.audio-track-row').forEach(row=>row.hidden=filter.value!=='all'&&row.dataset.story!==filter.value));
document.getElementById('audio-story')?.addEventListener('change',e=>{const url=new URL(location.href);url.searchParams.set('story',e.target.value);url.searchParams.delete('edit');location.href=url.toString();});
})();
</script></body></html>