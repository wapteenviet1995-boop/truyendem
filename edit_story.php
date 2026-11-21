<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notifications.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php?next=submit_story.php'); exit; }
$memberId=(int)$_SESSION['member_id'];
$storyId=(int)($_GET['id']??$_POST['story_id']??0);
if($storyId<1){http_response_code(404);exit('Không tìm thấy truyện.');}
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['member_csrf'];$error='';$notice='';
function es_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function es_upload_cover(string $current):array{
 if(!isset($_FILES['cover_file'])||(int)($_FILES['cover_file']['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return [$current,''];
 $file=$_FILES['cover_file'];
 if((int)$file['error']!==UPLOAD_ERR_OK)return [$current,'Ảnh tải lên bị lỗi. Vui lòng chọn lại ảnh.'];
 if((int)$file['size']>5*1024*1024)return [$current,'Ảnh bìa không được vượt quá 5 MB.'];
 if(!is_uploaded_file((string)$file['tmp_name']))return [$current,'Tệp tải lên không hợp lệ.'];
 $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file((string)$file['tmp_name']);
 $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
 $info=@getimagesize((string)$file['tmp_name']);
 if(!isset($allowed[$mime])||$info===false||($info['mime']??'')!==$mime)return [$current,'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF hợp lệ.'];
 $dir=__DIR__.'/uploads/stories';
 if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))return [$current,'Không tạo được thư mục lưu ảnh.'];
 $name=bin2hex(random_bytes(16)).'.'.$allowed[$mime];
 if(!move_uploaded_file((string)$file['tmp_name'],$dir.'/'.$name))return [$current,'Không lưu được ảnh. Vui lòng thử lại.'];
 return ['/uploads/stories/'.$name,''];
}
$st=$pdo->prepare('SELECT * FROM stories WHERE id=? AND member_id=? LIMIT 1');$st->execute([$storyId,$memberId]);$story=$st->fetch(PDO::FETCH_ASSOC);
if(!$story){http_response_code(404);exit('Truyện không tồn tại hoặc không thuộc tài khoản của bạn.');}
$audioTrack=null;$audioTracks=[];$audioTableReady=true;
if(in_array((string)$story['story_type'],['audio','both'],true)){
 try{$aq=$pdo->prepare("SELECT * FROM audio_tracks WHERE story_id=? ORDER BY track_order ASC,id ASC");$aq->execute([$storyId]);$audioTracks=$aq->fetchAll(PDO::FETCH_ASSOC);$audioTrack=$audioTracks[0]??null;}
 catch(Throwable $e){$audioTableReady=false;}
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn. Hãy tải lại trang.';}
 else{
  try{
   $action=(string)($_POST['action']??'');
   if($action==='update_story'){
    $title=trim((string)($_POST['title']??''));$category=trim((string)($_POST['category']??''))?:'Truyện mới';$description=trim((string)($_POST['description']??''));$cover=trim((string)($_POST['cover_url']??''))?: (string)$story['cover_url'];$short=trim((string)($_POST['short_content']??''));
    [$cover,$uploadError]=es_upload_cover($cover);
    if($uploadError!=='')$error=$uploadError;
    elseif($title===''||mb_strlen($title)>220)$error='Tên truyện không được để trống và tối đa 220 ký tự.';
    elseif(mb_strlen($category)>100)$error='Thể loại tối đa 100 ký tự.';
    elseif($cover!==''&&!filter_var($cover,FILTER_VALIDATE_URL)&&!preg_match('~^/uploads/stories/[a-zA-Z0-9._-]+$~',$cover))$error='Link ảnh bìa không hợp lệ.';
    elseif($story['story_kind']==='short'&&$story['story_type']!=='audio'&&$short==='')$error='Bạn cần nhập nội dung truyện ngắn.';
    else{
     $pdo->beginTransaction();
     $pdo->prepare("UPDATE stories SET title=?,category=?,description=?,cover_url=?,short_content=?,status='draft',updated_at=NOW() WHERE id=? AND member_id=?")->execute([$title,$category,$description,$cover,($story['story_kind']==='short'&&$story['story_type']!=='audio')?$short:null,$storyId,$memberId]);
     $pdo->prepare("DELETE FROM story_moderation_reviews WHERE target_type='story' AND target_id=?")->execute([$storyId]);
     td_notify_admins($pdo,'story_resubmission','Truyện đã được chỉnh sửa và gửi duyệt lại',$title,'review_stories.php');
     $pdo->commit();
     header('Location: edit_story.php?id='.$storyId.'&saved=1');exit;
    }
   }elseif($action==='add_audio_track'&&in_array((string)$story['story_type'],['audio','both'],true)){
    if(!$audioTableReady)throw new RuntimeException('Thư viện audio chưa được thiết lập.');
    $trackTitle=trim((string)($_POST['audio_title']??''));$audioUrl=trim((string)($_POST['audio_url']??''));$source='link';
    if(isset($_FILES['audio_file'])&&(int)($_FILES['audio_file']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
     $af=$_FILES['audio_file'];if((int)$af['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Tệp audio tải lên bị lỗi.');
     if((int)$af['size']>25*1024*1024)throw new RuntimeException('Mỗi phần audio không được vượt quá 25 MB.');
     if(!is_uploaded_file((string)$af['tmp_name']))throw new RuntimeException('Tệp audio không hợp lệ.');
     $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$af['tmp_name']);$types=['audio/mpeg'=>'mp3','audio/mp3'=>'mp3','audio/mp4'=>'m4a','audio/x-m4a'=>'m4a','audio/aac'=>'aac','audio/ogg'=>'ogg','application/ogg'=>'ogg','audio/wav'=>'wav','audio/x-wav'=>'wav','audio/vnd.wave'=>'wav'];
     if(!isset($types[$mime]))throw new RuntimeException('Chỉ nhận MP3, M4A, AAC, OGG hoặc WAV hợp lệ.');
     $dir=__DIR__.'/uploads/audio';if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('Không tạo được thư mục lưu audio.');
     $fileName=bin2hex(random_bytes(18)).'.'.$types[$mime];if(!move_uploaded_file((string)$af['tmp_name'],$dir.'/'.$fileName))throw new RuntimeException('Không lưu được file audio lên hosting.');
     $audioUrl='/uploads/audio/'.$fileName;$source='upload';
    }elseif($audioUrl!==''){$source='link';}
    if($trackTitle==='')$trackTitle=(string)$story['title'].' · Phần '.(count($audioTracks)+1);
    if($audioUrl===''||($source==='link'&&!filter_var($audioUrl,FILTER_VALIDATE_URL))||($source==='upload'&&!preg_match('~^/uploads/audio/[a-zA-Z0-9._-]+$~',$audioUrl)))throw new RuntimeException('Hãy chọn file audio hoặc nhập đường dẫn MP3 hợp lệ.');
    $orderQ=$pdo->prepare('SELECT COALESCE(MAX(track_order),0)+1 FROM audio_tracks WHERE story_id=?');$orderQ->execute([$storyId]);$nextOrder=(int)$orderQ->fetchColumn();
    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO audio_tracks(story_id,chapter_id,title,audio_url,source_type,duration_seconds,track_order,status) VALUES(?,NULL,?,?,?,0,?,'draft')")->execute([$storyId,$trackTitle,$audioUrl,$source,$nextOrder]);
    $pdo->prepare("UPDATE stories SET status='draft',updated_at=NOW() WHERE id=? AND member_id=?")->execute([$storyId,$memberId]);
    $pdo->prepare("DELETE FROM story_moderation_reviews WHERE target_type='story' AND target_id=?")->execute([$storyId]);
    td_notify_admins($pdo,'audio_resubmission','Có phần audio mới được thêm và gửi duyệt',$trackTitle,'audio.php');
    $pdo->commit();header('Location: edit_story.php?id='.$storyId.'&audio_added=1#audio-information');exit;
   }elseif($action==='delete_audio_track'&&in_array((string)$story['story_type'],['audio','both'],true)){
    if(!$audioTableReady)throw new RuntimeException('Thư viện audio chưa được thiết lập.');
    $deleteId=(int)($_POST['audio_track_id']??0);$dq=$pdo->prepare('SELECT id,title FROM audio_tracks WHERE id=? AND story_id=? LIMIT 1');$dq->execute([$deleteId,$storyId]);$deleteTrack=$dq->fetch(PDO::FETCH_ASSOC);
    if(!$deleteTrack)throw new RuntimeException('Không tìm thấy phần audio cần xóa.');
    $pdo->beginTransaction();$pdo->prepare('DELETE FROM audio_tracks WHERE id=? AND story_id=?')->execute([$deleteId,$storyId]);
    $pdo->prepare("UPDATE stories SET status='draft',updated_at=NOW() WHERE id=? AND member_id=?")->execute([$storyId,$memberId]);
    $pdo->prepare("DELETE FROM story_moderation_reviews WHERE target_type='story' AND target_id=?")->execute([$storyId]);
    td_notify_admins($pdo,'audio_resubmission','Một phần audio đã được gỡ khỏi truyện',$deleteTrack['title'],'audio.php');
    $pdo->commit();header('Location: edit_story.php?id='.$storyId.'&audio_deleted=1#audio-information');exit;
   }elseif($action==='update_audio'&&in_array((string)$story['story_type'],['audio','both'],true)){
    if(!$audioTableReady)throw new RuntimeException('Thư viện audio chưa được thiết lập. Vui lòng báo quản trị viên.');
    $trackTitle=trim((string)($_POST['audio_title']??''));$audioUrl=trim((string)($_POST['audio_url']??''));$source='link';$oldTrackId=(int)($_POST['audio_track_id']??0);
    $currentTrack=null;if($oldTrackId){$tq=$pdo->prepare('SELECT id,audio_url,source_type FROM audio_tracks WHERE id=? AND story_id=? LIMIT 1');$tq->execute([$oldTrackId,$storyId]);$currentTrack=$tq->fetch(PDO::FETCH_ASSOC)?:null;}
    if($oldTrackId&&!$currentTrack)throw new RuntimeException('Không tìm thấy track audio thuộc truyện này.');
    if(isset($_FILES['audio_file'])&&(int)($_FILES['audio_file']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
     $af=$_FILES['audio_file'];if((int)$af['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Tệp audio tải lên bị lỗi.');
     if((int)$af['size']>25*1024*1024)throw new RuntimeException('Audio không được vượt quá 25 MB.');
     if(!is_uploaded_file((string)$af['tmp_name']))throw new RuntimeException('Tệp audio không hợp lệ.');
     $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$af['tmp_name']);$types=['audio/mpeg'=>'mp3','audio/mp3'=>'mp3','audio/mp4'=>'m4a','audio/x-m4a'=>'m4a','audio/aac'=>'aac','audio/ogg'=>'ogg','application/ogg'=>'ogg','audio/wav'=>'wav','audio/x-wav'=>'wav','audio/vnd.wave'=>'wav'];
     if(!isset($types[$mime]))throw new RuntimeException('Chỉ nhận MP3, M4A, AAC, OGG hoặc WAV hợp lệ.');
     $dir=__DIR__.'/uploads/audio';if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('Không tạo được thư mục lưu audio.');
     $fileName=bin2hex(random_bytes(18)).'.'.$types[$mime];if(!move_uploaded_file((string)$af['tmp_name'],$dir.'/'.$fileName))throw new RuntimeException('Không lưu được file audio lên hosting.');
     $audioUrl='/uploads/audio/'.$fileName;$source='upload';
    }elseif($audioUrl!==''){$source='link';}
    elseif($currentTrack){$audioUrl=(string)$currentTrack['audio_url'];$source=(string)$currentTrack['source_type'];}
    if($trackTitle==='')$trackTitle=(string)$story['title'];
    if($audioUrl===''||($source==='link'&&!filter_var($audioUrl,FILTER_VALIDATE_URL))||($source==='upload'&&!preg_match('~^/uploads/audio/[a-zA-Z0-9._-]+$~',$audioUrl)))throw new RuntimeException('Hãy tải file audio hoặc nhập đường dẫn MP3 hợp lệ.');
    $pdo->beginTransaction();
    if($currentTrack){$pdo->prepare("UPDATE audio_tracks SET title=?,audio_url=?,source_type=?,status='draft' WHERE id=? AND story_id=?")->execute([$trackTitle,$audioUrl,$source,$oldTrackId,$storyId]);}
    else{$pdo->prepare("INSERT INTO audio_tracks(story_id,chapter_id,title,audio_url,source_type,duration_seconds,track_order,status) VALUES(?,NULL,?,?,?,0,1,'draft')")->execute([$storyId,$trackTitle,$audioUrl,$source]);}
    $pdo->prepare("UPDATE stories SET status='draft',updated_at=NOW() WHERE id=? AND member_id=?")->execute([$storyId,$memberId]);
    $pdo->prepare("DELETE FROM story_moderation_reviews WHERE target_type='story' AND target_id=?")->execute([$storyId]);
    td_notify_admins($pdo,'audio_resubmission','Audio đã được cập nhật và gửi duyệt lại',$trackTitle,'audio.php');
    $pdo->commit();header('Location: edit_story.php?id='.$storyId.'&audio_saved=1#audio-information');exit;
   }elseif($action==='update_chapter'&&$story['story_kind']==='serial'){
    $chapterId=(int)($_POST['chapter_id']??0);$chapterTitle=trim((string)($_POST['chapter_title']??''));$body=trim((string)($_POST['chapter_content']??''));
    $check=$pdo->prepare('SELECT id FROM chapters WHERE id=? AND story_id=?');$check->execute([$chapterId,$storyId]);
    if(!$check->fetchColumn())$error='Không tìm thấy chương thuộc truyện này.';
    elseif($chapterTitle===''||$body==='')$error='Tên chương và nội dung không được để trống.';
    else{
     $pdo->beginTransaction();
     $pdo->prepare("UPDATE chapters SET title=?,content=?,status=CASE WHEN status='published' THEN 'draft' ELSE status END WHERE id=? AND story_id=?")->execute([$chapterTitle,$body,$chapterId,$storyId]);
     $pdo->prepare("UPDATE stories SET status='draft',updated_at=NOW() WHERE id=? AND member_id=?")->execute([$storyId,$memberId]);
     $pdo->prepare("DELETE FROM story_moderation_reviews WHERE target_type='chapter' AND target_id=?")->execute([$chapterId]);
     td_notify_admins($pdo,'chapter_resubmission','Chương đã được chỉnh sửa và gửi duyệt lại',$chapterTitle,'review_stories.php');
     $pdo->commit();header('Location: edit_story.php?id='.$storyId.'&chapter_saved=1#chapter-'.$chapterId);exit;
    }
   }
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Chưa lưu được nội dung. Vui lòng kiểm tra dữ liệu và thử lại.';}
 }
 $st->execute([$storyId,$memberId]);$story=$st->fetch(PDO::FETCH_ASSOC);
}
if(isset($_GET['saved']))$notice='Đã lưu thay đổi truyện. Nếu truyện đã xuất bản, nội dung được chuyển về bản nháp để quản trị viên duyệt lại.';
if(isset($_GET['chapter_saved']))$notice='Đã lưu chương. Chương/truyện đã xuất bản sẽ được chuyển về bản nháp để duyệt lại.';
if(isset($_GET['audio_saved']))$notice='Đã cập nhật phần audio. Truyện đang chờ quản trị viên duyệt lại.';
if(isset($_GET['audio_added']))$notice='Đã thêm phần audio mới. Phần này đang chờ quản trị viên duyệt.';
if(isset($_GET['audio_deleted']))$notice='Đã xóa phần audio. Truyện đã chuyển về bản nháp để kiểm duyệt lại.';
$chapters=[];
if($story['story_kind']==='serial'){$q=$pdo->prepare('SELECT id,chapter_number,title,content,status FROM chapters WHERE story_id=? ORDER BY chapter_number ASC,id ASC');$q->execute([$storyId]);$chapters=$q->fetchAll(PDO::FETCH_ASSOC);}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sửa truyện | Truyện Đêm</title><link rel="stylesheet" href="css/style.css"><link rel="stylesheet" href="css/stories.css"><link rel="stylesheet" href="css/story-editor.css"><link rel="stylesheet" href="/css/truyenhub-theme.css"><link rel="stylesheet" href="/css/site-matte-black.css"><link rel="stylesheet" href="/css/member-edit-story.css?v=20261121-audio-parts2"><style>
.edit-story-wrap{max-width:980px;margin:25px auto;padding:0 14px 65px}.edit-story-panel{background:#15131d;border:1px solid #393047;border-radius:16px;padding:20px;margin:15px 0}.edit-story-panel h1,.edit-story-panel h2{margin-top:0;color:#f5edff}.edit-story-form label{display:block;margin:13px 0;color:#ddd5e8}.edit-story-form input:not([type=file]),.edit-story-form textarea,.edit-story-form select{display:block;box-sizing:border-box;width:100%;margin-top:7px;padding:12px;border:1px solid #494052;border-radius:10px;background:#0c0b12;color:#fff;font:inherit}.edit-story-form textarea{min-height:260px;line-height:1.8}.edit-story-btn{display:inline-block;border:0;border-radius:10px;padding:12px 18px;background:#b18be8;color:#171020;font-weight:800;text-decoration:none;cursor:pointer}.edit-story-muted{color:#aaa2b5}.edit-story-chapter{border:1px solid #3d334b;border-radius:12px;padding:14px;margin:12px 0;background:#100e17}.edit-story-chapter summary{cursor:pointer;color:#decafa;font-weight:700}.edit-story-flash{padding:12px;background:#1c3027;color:#c9f2d8;border-radius:10px}.edit-story-error{padding:12px;background:#3b2029;color:#ffd1da;border-radius:10px}
@media(max-width:600px){.edit-story-panel{padding:14px}.edit-story-wrap{margin:12px auto}.edit-story-btn{width:100%;box-sizing:border-box;text-align:center}}
</style></head><body class="story-site"><?php require __DIR__ . '/includes/header.php'; ?>
<main class="edit-story-wrap member-edit-wrap"><section class="member-edit-hero"><div><span class="member-edit-kicker">KHÔNG GIAN SÁNG TÁC · CHỈNH SỬA</span><h1>Chỉnh sửa tác phẩm</h1><p>Cập nhật thông tin truyện và nội dung chương trong cùng một không gian.</p></div><a class="member-edit-back" href="submit_story.php#my-stories">← Quay lại thư viện bài viết</a></section><nav class="member-edit-nav"><a href="submit_story.php#my-stories">▤ Bài viết của tôi</a><a class="active" href="#story-information">✎ Thông tin truyện</a><?php if(in_array((string)$story['story_type'],['audio','both'],true)):?><a href="#audio-information">♫ Quản lý audio</a><?php endif;?><?php if($story['story_kind']==='serial'):?><a href="#story-chapters">▤ Quản lý chương</a><?php endif;?></nav><section class="edit-story-panel" id="story-information"><div class="member-edit-section-head"><div><span class="member-edit-kicker">THÔNG TIN TÁC PHẨM</span><h2><?=es_h($story['title'])?></h2><p class="edit-story-muted">Chỉ tài khoản đã đăng truyện mới có quyền chỉnh sửa tác phẩm này.</p></div><span class="member-edit-step">01 / THÔNG TIN</span></div><?php if($notice):?><p class="edit-story-flash"><?=es_h($notice)?></p><?php endif;?><?php if($error):?><p class="edit-story-error"><?=es_h($error)?></p><?php endif;?>
<form class="edit-story-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=es_h($csrf)?>"><input type="hidden" name="action" value="update_story"><input type="hidden" name="story_id" value="<?=$storyId?>">
<label>Tên truyện *<input name="title" maxlength="220" required value="<?=es_h($story['title'])?>"></label>
<label>Thể loại<input name="category" maxlength="100" value="<?=es_h($story['category'])?>"></label>
<label>Mô tả truyện<textarea name="description" maxlength="3000" rows="4"><?=es_h($story['description'])?></textarea></label>
<label>Ảnh bìa hiện tại<?php if(!empty($story['cover_url'])):?><br><img class="member-edit-cover" src="<?=es_h($story['cover_url'])?>" alt="Ảnh bìa hiện tại"><?php else:?><span class="edit-story-muted"> Chưa có ảnh bìa.</span><?php endif;?><input type="file" name="cover_file" accept="image/jpeg,image/png,image/webp,image/gif"><small class="edit-story-muted">Chọn ảnh mới để thay ảnh hiện tại (tối đa 5 MB), hoặc nhập link ảnh bên dưới.</small><input name="cover_url" type="url" placeholder="Link ảnh https://..." value="<?=filter_var((string)$story['cover_url'],FILTER_VALIDATE_URL)?es_h($story['cover_url']):''?>"></label>
<?php if($story['story_kind']==='short'&&$story['story_type']!=='audio'):?><label>Nội dung truyện ngắn *<textarea name="short_content" required><?=es_h($story['short_content'])?></textarea></label><?php endif;?>
<div class="member-edit-actions"><button class="edit-story-btn" type="submit">Lưu thay đổi truyện</button><a class="edit-story-btn secondary" href="submit_story.php#my-stories">Hủy và quay lại</a></div></form><p class="member-edit-note">Lưu ý: nếu truyện đã xuất bản, thay đổi sẽ đưa truyện về bản nháp để đội ngũ kiểm duyệt xem xét lại trước khi hiển thị phiên bản mới.</p></section>
<?php if(in_array((string)$story['story_type'],['audio','both'],true)):?><section class="edit-story-panel" id="audio-information"><div class="member-edit-section-head"><div><span class="member-edit-kicker">THƯ VIỆN ÂM THANH</span><h2>Quản lý audio</h2><p>Cập nhật file nghe hoặc đường dẫn MP3 cho tác phẩm.</p></div><span class="member-edit-step">AUDIO</span></div>
<?php if(!$audioTableReady):?><p class="member-edit-note">Bảng audio chưa sẵn sàng. Hãy liên hệ quản trị viên để kiểm tra thiết lập thư viện audio.</p><?php else:?>
<div class="member-audio-library">
<div class="member-audio-library-head"><strong>Các phần audio đã thêm</strong><span><?=count($audioTracks)?> phần</span></div>
<?php if(!$audioTracks):?><p class="member-edit-note">Chưa có phần audio nào. Hãy tải phần đầu tiên ở biểu mẫu bên dưới.</p><?php else:?>
<div class="member-audio-track-list"><?php foreach($audioTracks as $trackIndex=>$track):?>
<article class="member-audio-track">
<div class="member-audio-track-top"><span class="member-audio-track-number">PHẦN <?=str_pad((string)($trackIndex+1),2,'0',STR_PAD_LEFT)?></span><span class="member-audio-track-status <?=$track['status']==='published'?'is-published':'is-pending'?>"><?=$track['status']==='published'?'Đang phát':'Chờ duyệt / bản nháp'?></span></div>
<strong class="member-audio-track-title"><?=es_h($track['title'])?></strong>
<div class="member-audio-track-meta"><?=((string)$track['source_type']==='upload'?'File đã tải lên':'Đường dẫn bên ngoài')?> · Thứ tự <?= (int)$track['track_order']?> · <a href="<?=es_h($track['audio_url'])?>" target="_blank" rel="noopener">Mở file</a></div>
<form class="edit-story-form member-audio-edit-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=es_h($csrf)?>"><input type="hidden" name="action" value="update_audio"><input type="hidden" name="story_id" value="<?=$storyId?>"><input type="hidden" name="audio_track_id" value="<?=(int)$track['id']?>">
<label>Sửa tên phần<input name="audio_title" maxlength="220" required value="<?=es_h($track['title'])?>"></label>
<label>Thay bằng link MP3<input name="audio_url" type="url" placeholder="Để trống nếu không đổi link"></label>
<label>Hoặc tải file thay thế<input type="file" name="audio_file" accept=".mp3,.m4a,.aac,.ogg,.wav,audio/mpeg,audio/mp4,audio/aac,audio/ogg,audio/wav"><small class="edit-story-muted">Tối đa 25 MB. File mới sẽ thay file của phần này.</small></label>
<div class="member-edit-actions"><button class="edit-story-btn" type="submit">Lưu phần này</button></div></form>
<form method="post" class="member-audio-delete-form" onsubmit="return confirm('Bạn chắc chắn muốn xóa phần audio này?')"><input type="hidden" name="csrf" value="<?=es_h($csrf)?>"><input type="hidden" name="action" value="delete_audio_track"><input type="hidden" name="story_id" value="<?=$storyId?>"><input type="hidden" name="audio_track_id" value="<?=(int)$track['id']?>"><button type="submit" class="member-audio-delete">Xóa phần audio</button></form>
</article><?php endforeach;?></div><?php endif;?>
</div>
<div class="member-audio-add">
<div class="member-edit-section-head"><div><span class="member-edit-kicker">THÊM BẢN GHI MỚI</span><h3>＋ Tải thêm phần audio</h3><p>Mỗi lần tải một phần. Có thể quay lại thêm các phần tiếp theo bất cứ lúc nào.</p></div></div>
<form class="edit-story-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=es_h($csrf)?>"><input type="hidden" name="action" value="add_audio_track"><input type="hidden" name="story_id" value="<?=$storyId?>">
<label>Tên phần audio *<input name="audio_title" maxlength="220" placeholder="Ví dụ: Phần 1 – Tiếng động lúc nửa đêm"></label>
<label>Hoặc dán link MP3<input name="audio_url" type="url" placeholder="https://.../phan-1.mp3"></label>
<label>Chọn file từ điện thoại<input type="file" name="audio_file" accept=".mp3,.m4a,.aac,.ogg,.wav,audio/mpeg,audio/mp4,audio/aac,audio/ogg,audio/wav"><small class="edit-story-muted">MP3, M4A, AAC, OGG hoặc WAV · tối đa 25 MB cho mỗi phần. Chọn file hoặc nhập link.</small></label>
<div class="member-edit-actions"><button class="edit-story-btn" type="submit">＋ Thêm phần và gửi duyệt</button></div></form>
</div>
<?php endif;?></section><?php endif;?>
<?php if($story['story_kind']==='serial'):?><section class="edit-story-panel" id="story-chapters"><div class="member-edit-section-head"><div><span class="member-edit-kicker">NỘI DUNG NHIỀU CHƯƠNG</span><h2>Quản lý chương truyện</h2><p>Chỉnh sửa từng chương; nội dung thay đổi sẽ được gửi duyệt lại.</p></div><span class="member-edit-step">02 / <?=count($chapters)?> CHƯƠNG</span></div><?php if(!$chapters):?><p class="edit-story-muted">Truyện chưa có chương nào.</p><?php else:foreach($chapters as $ch):?><details class="edit-story-chapter" id="chapter-<?= (int)$ch['id']?>"><summary>Chương <?= (int)$ch['chapter_number']?>: <?=es_h($ch['title'])?> · <?=$ch['status']==='published'?'Đã xuất bản':'Bản nháp / chờ duyệt'?></summary><form class="edit-story-form" method="post"><input type="hidden" name="csrf" value="<?=es_h($csrf)?>"><input type="hidden" name="action" value="update_chapter"><input type="hidden" name="story_id" value="<?=$storyId?>"><input type="hidden" name="chapter_id" value="<?=(int)$ch['id']?>"><label>Tên chương *<input name="chapter_title" maxlength="220" required value="<?=es_h($ch['title'])?>"></label><label>Nội dung chương *<textarea name="chapter_content" required><?=es_h($ch['content'])?></textarea></label><button class="edit-story-btn" type="submit">Lưu chương này</button></form></details><?php endforeach;endif;?></section><?php endif;?></main><?php require __DIR__ . '/includes/footer.php'; ?></body></html>