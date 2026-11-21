<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notifications.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php?next=submit_story.php'); exit; }
$memberId=(int)$_SESSION['member_id'];
$postMode=(string)($_GET['post_mode']??$_POST['post_mode']??'hub');
if(!in_array($postMode,['hub','short','audio','serial'],true))$postMode='hub';
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['member_csrf'];$error='';$notice='';
function ss_slug(string $value):string{
 // Chuyen chu Tieng Viet co dau ve ASCII truoc khi loc ky tu.
 $map=[
 'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
 'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
 'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
 'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
 'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
 'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
 'À'=>'A','Á'=>'A','Ạ'=>'A','Ả'=>'A','Ã'=>'A','Â'=>'A','Ầ'=>'A','Ấ'=>'A','Ậ'=>'A','Ẩ'=>'A','Ẫ'=>'A','Ă'=>'A','Ằ'=>'A','Ắ'=>'A','Ặ'=>'A','Ẳ'=>'A','Ẵ'=>'A',
 'È'=>'E','É'=>'E','Ẹ'=>'E','Ẻ'=>'E','Ẽ'=>'E','Ê'=>'E','Ề'=>'E','Ế'=>'E','Ệ'=>'E','Ể'=>'E','Ễ'=>'E',
 'Ì'=>'I','Í'=>'I','Ị'=>'I','Ỉ'=>'I','Ĩ'=>'I',
 'Ò'=>'O','Ó'=>'O','Ọ'=>'O','Ỏ'=>'O','Õ'=>'O','Ô'=>'O','Ồ'=>'O','Ố'=>'O','Ộ'=>'O','Ổ'=>'O','Ỗ'=>'O','Ơ'=>'O','Ờ'=>'O','Ớ'=>'O','Ợ'=>'O','Ở'=>'O','Ỡ'=>'O',
 'Ù'=>'U','Ú'=>'U','Ụ'=>'U','Ủ'=>'U','Ũ'=>'U','Ư'=>'U','Ừ'=>'U','Ứ'=>'U','Ự'=>'U','Ử'=>'U','Ữ'=>'U',
 'Ỳ'=>'Y','Ý'=>'Y','Ỵ'=>'Y','Ỷ'=>'Y','Ỹ'=>'Y','Đ'=>'D'
 ];
 $value=strtr($value,$map);
 if(function_exists('iconv')){$v=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);if($v!==false)$value=$v;}
 return strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/','-',$value),'-'));
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn. Hãy tải lại trang.';}
 else{
  try{
   $action=(string)($_POST['action']??'');
   if($action==='create_story'){
    $title=trim((string)($_POST['title']??''));$kind=$postMode==='serial'?'serial':'short';
    $category=trim((string)($_POST['category']??''))?:'Truyện mới';$description=trim((string)($_POST['description']??''));
    $cover=trim((string)($_POST['cover_url']??''));$short=trim((string)($_POST['short_content']??''));
    $chapterTitle=trim((string)($_POST['chapter_title']??''));$chapterBody=trim((string)($_POST['chapter_content']??''));
    $storyType=$postMode==='audio'?'audio':'text';
    $audioUrl=trim((string)($_POST['audio_url']??''));$audioTitle=trim((string)($_POST['audio_title']??''));$audioFileUrl='';
    $uploadError='';
    if(isset($_FILES['cover_file']) && (int)($_FILES['cover_file']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
     $file=$_FILES['cover_file'];
     if((int)$file['error']!==UPLOAD_ERR_OK){$uploadError='Ảnh tải lên bị lỗi. Vui lòng chọn lại ảnh.';}
     elseif((int)$file['size']>5*1024*1024){$uploadError='Ảnh bìa không được vượt quá 5 MB.';}
     elseif(!is_uploaded_file((string)$file['tmp_name'])){$uploadError='Tệp tải lên không hợp lệ.';}
     else{
      $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file((string)$file['tmp_name']);
      $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
      $imageInfo=@getimagesize((string)$file['tmp_name']);
      if(!isset($allowed[$mime])||$imageInfo===false||($imageInfo['mime']??'')!==$mime){$uploadError='Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF hợp lệ.';}
      else{
       $uploadDir=__DIR__.'/uploads/stories';
       if(!is_dir($uploadDir)&&!mkdir($uploadDir,0755,true)&&!is_dir($uploadDir)){$uploadError='Không tạo được thư mục lưu ảnh. Vui lòng liên hệ quản trị viên.';}
       else{
        $fileName=bin2hex(random_bytes(16)).'.'.$allowed[$mime];
        if(move_uploaded_file((string)$file['tmp_name'],$uploadDir.'/'.$fileName)){$cover='/uploads/stories/'.$fileName;}
        else{$uploadError='Không lưu được ảnh. Vui lòng thử lại.';}
       }
      }
     }
    }
    if($postMode==='hub')$uploadError='Hãy chọn một khu đăng bài riêng trước khi gửi truyện.';
    if($uploadError==='' && in_array($storyType,['audio','both'],true) && isset($_FILES['audio_file']) && (int)($_FILES['audio_file']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
     $af=$_FILES['audio_file'];
     if((int)$af['error']!==UPLOAD_ERR_OK)$uploadError='Tệp audio tải lên bị lỗi.';
     elseif((int)$af['size']>25*1024*1024)$uploadError='Audio không được vượt quá 25 MB.';
     elseif(!is_uploaded_file((string)$af['tmp_name']))$uploadError='Tệp audio không hợp lệ.';
     else{
      $fi=new finfo(FILEINFO_MIME_TYPE);$mime=$fi->file((string)$af['tmp_name']);
      $audioTypes=['audio/mpeg'=>'mp3','audio/mp3'=>'mp3','audio/mp4'=>'m4a','audio/x-m4a'=>'m4a','audio/aac'=>'aac','audio/ogg'=>'ogg','audio/wav'=>'wav','audio/x-wav'=>'wav'];
      if(!isset($audioTypes[$mime]))$uploadError='Chỉ nhận MP3, M4A, AAC, OGG hoặc WAV hợp lệ.';
      else{$dir=__DIR__.'/uploads/audio';if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))$uploadError='Không tạo được thư mục lưu audio.';else{$fn=bin2hex(random_bytes(16)).'.'.$audioTypes[$mime];if(move_uploaded_file((string)$af['tmp_name'],$dir.'/'.$fn))$audioFileUrl='/uploads/audio/'.$fn;else $uploadError='Không lưu được file audio lên hosting.';}}
     }
    }
    if($uploadError!=='')$error=$uploadError;
    elseif($title===''||mb_strlen($title)>220)$error='Tên truyện không được để trống và tối đa 220 ký tự.';
    elseif($cover!==''&&!filter_var($cover,FILTER_VALIDATE_URL)&&!preg_match('~^/uploads/stories/[a-zA-Z0-9._-]+$~',$cover))$error='Link ảnh bìa không hợp lệ.';
    elseif(in_array($storyType,['audio','both'],true) && $audioFileUrl==='' && ($audioUrl==='' || !filter_var($audioUrl,FILTER_VALIDATE_URL)))$error='Nội dung audio cần tải file hoặc nhập link MP3 hợp lệ.';
    elseif(in_array($storyType,['audio','both'],true) && $audioUrl!=='' && !filter_var($audioUrl,FILTER_VALIDATE_URL))$error='Link audio không hợp lệ.';
    elseif($storyType!=='audio' && $kind==='short'&&$short==='')$error='Bạn cần nhập nội dung truyện ngắn.';
    elseif($storyType!=='audio' && $kind==='serial'&&($chapterTitle===''||$chapterBody===''))$error='Truyện nhiều chương cần có tên và nội dung chương đầu tiên.';
    else{
     $base=ss_slug($title);if($base==='')$base='truyen-moi';$slug=$base.'-'.bin2hex(random_bytes(3));
     $pdo->beginTransaction();
     $ins=$pdo->prepare("INSERT INTO stories(title,slug,author,member_id,description,cover_url,category,story_type,status,story_kind,short_content) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
     $ins->execute([$title,$slug,(string)($_SESSION['member_name']??'Thành viên'),$memberId,$description,$cover,$category,$storyType,'draft',$kind,$storyType==='audio'?null:($kind==='short'?$short:null)]);
     $storyId=(int)$pdo->lastInsertId();
     if(in_array($storyType,['audio','both'],true)){$trackUrl=$audioFileUrl!==''?$audioFileUrl:$audioUrl;$source=$audioFileUrl!==''?'upload':'link';$trackName=$audioTitle!==''?$audioTitle:$title;$pdo->prepare("INSERT INTO audio_tracks(story_id,chapter_id,title,audio_url,source_type,duration_seconds,track_order,status) VALUES(?,NULL,?,?,?,0,1,'draft')")->execute([$storyId,$trackName,$trackUrl,$source]);}
     elseif($kind==='serial'){$pdo->prepare("INSERT INTO chapters(story_id,chapter_number,title,content,status) VALUES(?,1,?,?,'draft')")->execute([$storyId,$chapterTitle,$chapterBody]);}
     td_notify_admins($pdo,'story_submission','Có truyện mới chờ duyệt',$title,'review_stories.php');
     $pdo->commit();header('Location: submit_story.php?submitted=1');exit;
    }
   }elseif($action==='add_chapter'){
    $storyId=(int)($_POST['story_id']??0);$title=trim((string)($_POST['chapter_title']??''));$body=trim((string)($_POST['chapter_content']??''));
    $check=$pdo->prepare("SELECT id FROM stories WHERE id=? AND member_id=? AND story_kind='serial'");$check->execute([$storyId,$memberId]);
    if(!$check->fetchColumn())$error='Không tìm thấy truyện của bạn.';
    elseif($title===''||$body==='')$error='Hãy nhập tên và nội dung chương.';
    else{
     $pdo->beginTransaction();$lock=$pdo->prepare('SELECT id FROM stories WHERE id=? AND member_id=? FOR UPDATE');$lock->execute([$storyId,$memberId]);if(!$lock->fetchColumn())throw new RuntimeException('Truyện không còn thuộc tài khoản của bạn.');$q=$pdo->prepare('SELECT COALESCE(MAX(chapter_number),0)+1 FROM chapters WHERE story_id=?');$q->execute([$storyId]);$number=(int)$q->fetchColumn();
     $pdo->prepare("INSERT INTO chapters(story_id,chapter_number,title,content,status) VALUES(?,?,?,?,'draft')")->execute([$storyId,$number,$title,$body]);
     td_notify_admins($pdo,'chapter_submission','Có chương mới chờ duyệt',$title,'review_stories.php');
     $pdo->commit();header('Location: submit_story.php?chapter_added=1');exit;
    }
   }
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Chưa lưu được nội dung. Vui lòng kiểm tra dữ liệu và thử lại.';}
 }
}
if(isset($_GET['submitted']))$notice='Đã gửi truyện! Truyện đang chờ quản trị viên duyệt trước khi xuất bản.';
if(isset($_GET['chapter_added']))$notice='Đã gửi chương mới. Chương đang chờ quản trị viên duyệt.';
$q=$pdo->prepare("SELECT s.id,s.title,s.slug,s.story_kind,s.story_type,s.status,s.created_at,r.decision AS review_decision,r.note AS review_note,(SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id) chapter_count FROM stories s LEFT JOIN story_moderation_reviews r ON r.target_type='story' AND r.target_id=s.id WHERE s.member_id=? ORDER BY s.id DESC");$q->execute([$memberId]);$allMine=$q->fetchAll();
$stateFilter=(string)($_GET['state']??'all');if(!in_array($stateFilter,['all','waiting','changes','published'],true))$stateFilter='all';
$storyCounts=['all'=>count($allMine),'waiting'=>0,'changes'=>0,'published'=>0];
foreach($allMine as $item){if($item['status']==='published')$storyCounts['published']++;elseif(($item['review_decision']??'')==='rejected')$storyCounts['changes']++;else $storyCounts['waiting']++;}
$mine=array_values(array_filter($allMine,static function($item)use($stateFilter){if($stateFilter==='waiting')return $item['status']!=='published'&&($item['review_decision']??'')!=='rejected';if($stateFilter==='changes')return ($item['review_decision']??'')==='rejected';if($stateFilter==='published')return $item['status']==='published';return true;}));
$chapterStates=[];
try{
 $cq=$pdo->prepare("SELECT c.story_id,c.chapter_number,c.title,c.status,r.decision,r.note FROM chapters c JOIN stories s ON s.id=c.story_id LEFT JOIN story_moderation_reviews r ON r.target_type='chapter' AND r.target_id=c.id WHERE s.member_id=? ORDER BY c.story_id,c.chapter_number");
 $cq->execute([$memberId]);
 foreach($cq->fetchAll() as $chapterState)$chapterStates[(int)$chapterState['story_id']][]=$chapterState;
}catch(Throwable $e){$chapterStates=[];}
function ss_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $postMode==='hub'?'Trung tâm bài viết':($postMode==='short'?'Đăng truyện ngắn':($postMode==='audio'?'Đăng truyện audio':'Đăng truyện dài')) ?> | Truyện Đêm</title><link rel="stylesheet" href="css/style.css?v=20261112-submitstory"><link rel="stylesheet" href="css/stories.css?v=20261112-submitstory"><style>
.submit-wrap{max-width:900px;margin:30px auto;padding:0 15px 70px}.submit-panel{background:#14131d;border:1px solid #332b43;border-radius:17px;padding:22px;margin:16px 0}.submit-panel h1,.submit-panel h2{margin:0 0 12px}.submit-panel p,.submit-muted{color:#aaa5b8}.submit-form label{display:block;margin:14px 0;color:#ddd5eb}.submit-form input,.submit-form textarea,.submit-form select{display:block;box-sizing:border-box;width:100%;margin-top:7px;padding:12px;border:1px solid #484052;border-radius:10px;background:#0c0b12;color:#fff;font:inherit}.submit-form textarea{min-height:180px;line-height:1.7}.submit-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}.submit-btn{border:0;border-radius:10px;padding:12px 17px;background:#ad8be6;color:#17111f;font-weight:800;cursor:pointer}.submit-flash{padding:12px;border-radius:10px;background:#203126;color:#c7f4d2}.submit-error{padding:12px;border-radius:10px;background:#3b2029;color:#ffc6d1}.submit-story-item{padding:14px;border:1px solid #373044;border-radius:12px;margin:12px 0}.submit-story-item form{margin-top:12px}.submit-story-item summary{color:#d6c2f5;cursor:pointer;font-weight:700}.submit-status-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px;margin:15px 0 18px}.submit-status-grid a{display:flex;flex-direction:column;gap:4px;padding:12px;border:1px solid #393044;border-radius:11px;background:#100f17;color:#aaa1b8;text-decoration:none}.submit-status-grid a strong{font-size:1.35rem;color:#f2eafa}.submit-status-grid a span{font-size:.75rem}.submit-status-grid a.active{border-color:#9d7ac8;background:#241b32}.submit-story-item>p:first-of-type{line-height:1.6}.submit-story-item .submit-muted{overflow-wrap:anywhere}
@media(max-width:600px){.submit-panel{padding:15px}.submit-wrap{padding-left:11px;padding-right:11px}.submit-status-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}.submit-status-grid a{padding:10px}.submit-status-grid a strong{font-size:1.15rem}}
</style><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1">
<link rel="stylesheet" href="css/story-editor.css?v=20261114-1"><link rel="stylesheet" href="/css/member-posts.css?v=20261121-v1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
</head><body class="story-site">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="submit-wrap member-posts-wrap">
<section class="member-posts-hero"><div><span class="member-posts-kicker">KHÔNG GIAN SÁNG TÁC</span><h1><?= $postMode==='hub'?'Trung tâm bài viết':($postMode==='short'?'Đăng truyện ngắn':($postMode==='audio'?'Đăng truyện audio':'Đăng truyện dài')) ?></h1><p><?= $postMode==='hub'?'Chọn đúng loại tác phẩm để bắt đầu sáng tác và quản lý bài viết.':'Biểu mẫu riêng được tối ưu cho từng loại nội dung.' ?></p></div><a href="<?= $postMode==='hub'?'#post-types':'/submit_story.php' ?>"><?= $postMode==='hub'?'＋ Chọn loại bài':'← Trung tâm bài viết' ?></a></section>
<nav class="member-posts-nav"><a class="<?= $postMode==='short'?'is-active':'' ?>" href="/post-short.php">✎ Truyện ngắn</a><a class="<?= $postMode==='audio'?'is-active':'' ?>" href="/post-audio.php">♫ Audio</a><a class="<?= $postMode==='serial'?'is-active':'' ?>" href="/post-serial.php">▤ Truyện dài</a><a class="<?= $postMode==='hub'?'is-active':'' ?>" href="/submit_story.php">▣ Bài viết của tôi</a><a href="/moderation-history.php">✓ Lịch sử kiểm duyệt</a><a href="/contributor-dashboard.php">▦ Tổng quan tác giả</a></nav>
<?php if($postMode==='hub'):?><section class="member-posts-stats"><div><strong><?=$storyCounts['all']?></strong><span>Tổng tác phẩm</span></div><div><strong><?=$storyCounts['waiting']?></strong><span>Đang chờ duyệt</span></div><div><strong><?=$storyCounts['changes']?></strong><span>Cần chỉnh sửa</span></div><div><strong><?=$storyCounts['published']?></strong><span>Đã xuất bản</span></div></section><?php endif;?>
<?php if($postMode==='hub'):?><section class="member-posts-type-grid" id="post-types"><a class="member-post-type-card" href="/post-short.php"><span>✎</span><strong>Truyện ngắn</strong><small>Viết và đăng một câu chuyện hoàn chỉnh.</small><b>Đăng truyện ngắn →</b></a><a class="member-post-type-card" href="/post-audio.php"><span>♫</span><strong>Truyện audio</strong><small>Tải file MP3 hoặc thêm liên kết nghe.</small><b>Đăng truyện audio →</b></a><a class="member-post-type-card" href="/post-serial.php"><span>▤</span><strong>Truyện dài</strong><small>Xây dựng tác phẩm theo từng chương.</small><b>Đăng truyện dài →</b></a></section><?php else:?><section class="submit-panel member-posts-create" id="create-story"><div class="member-posts-section-head"><div><span class="member-posts-kicker">TÁC PHẨM MỚI</span><h2><?= $postMode==='short'?'Đăng truyện ngắn':($postMode==='audio'?'Đăng truyện audio':'Đăng truyện dài') ?></h2><p><?= $postMode==='short'?'Viết trọn vẹn câu chuyện trong một bài.':($postMode==='audio'?'Tải file audio hoặc dán đường dẫn MP3.':'Tạo truyện nhiều chương và bổ sung chương sau.') ?> Nội dung sẽ được gửi kiểm duyệt.</p></div><span class="member-posts-step">01 / TẠO BÀI</span></div><p class="member-posts-back"><a href="/submit_story.php">← Về trung tâm bài viết</a></p><?php if($notice):?><p class="submit-flash"><?=ss_h($notice)?></p><?php endif;?><?php if($error):?><p class="submit-error"><?=ss_h($error)?></p><?php endif;?>
<form class="submit-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=ss_h($csrf)?>"><input type="hidden" name="action" value="create_story"><input type="hidden" name="post_mode" value="<?=ss_h($postMode)?>"><input type="hidden" name="story_type" value="<?= $postMode==='audio'?'audio':'text' ?>"><input type="hidden" name="story_kind" value="<?= $postMode==='serial'?'serial':'short' ?>">
<div class="submit-grid"><label>Tên truyện *<input name="title" maxlength="220" required></label><label>Thể loại<input name="category" maxlength="100" placeholder="Kinh dị, tình cảm, fantasy..."></label><label>Ảnh bìa (không bắt buộc)<input name="cover_file" id="cover-file" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><small class="submit-muted">Chọn ảnh từ điện thoại · JPG, PNG, WEBP hoặc GIF · tối đa 5 MB.</small><input name="cover_url" type="url" placeholder="Hoặc dán link ảnh https://..."><img id="cover-preview" alt="Xem trước ảnh bìa" hidden style="display:none;max-width:180px;max-height:240px;object-fit:cover;border-radius:10px;margin-top:10px"></label></div>
<label>Mô tả truyện<textarea name="description" rows="3" maxlength="3000" placeholder="Giới thiệu ngắn về câu chuyện..."></textarea></label>
<?php if($postMode==='audio'):?><div id="submit-audio"><div class="submit-grid"><label>Tên file / tập audio<input name="audio_title" placeholder="Ví dụ: Tiếng gõ từ căn phòng khóa kín"></label><label>Link MP3 (nếu có)<input name="audio_url" type="url" placeholder="https://.../truyen.mp3"></label></div><label>Tải file audio từ điện thoại<input type="file" name="audio_file" accept="audio/mpeg,audio/mp4,audio/aac,audio/ogg,audio/wav,.mp3,.m4a,.aac,.ogg,.wav"><small class="submit-muted">MP3, M4A, AAC, OGG hoặc WAV · tối đa 25 MB. Có thể dùng link MP3 thay thế.</small></label></div><?php elseif($postMode==='short'):?><label>Nội dung truyện ngắn *<textarea name="short_content" required placeholder="Viết toàn bộ truyện ngắn tại đây..."></textarea></label><?php elseif($postMode==='serial'):?><div id="submit-serial"><div class="submit-grid"><label>Tên chương đầu tiên *<input name="chapter_title" maxlength="220" placeholder="Chương 1"></label></div><label>Nội dung chương đầu tiên *<textarea name="chapter_content" placeholder="Viết nội dung chương đầu tiên..."></textarea></label></div>
<button class="submit-btn" type="submit"><?= $postMode==='audio'?'Gửi audio để duyệt':($postMode==='serial'?'Gửi truyện dài để duyệt':'Gửi truyện ngắn để duyệt') ?></button></form></section><?php endif;?>
<?php if($postMode==='hub'):?><section class="submit-panel member-posts-library" id="my-stories"><div class="member-posts-section-head"><div><span class="member-posts-kicker">THƯ VIỆN CÁ NHÂN</span><h2>Bài viết của tôi</h2><p>Theo dõi trạng thái, chỉnh sửa và bổ sung chương cho tác phẩm.</p></div><span class="member-posts-step">02 / QUẢN LÝ</span></div>
<div class="submit-status-grid"><a class="<?= $stateFilter==='all'?'active':''?>" href="submit_story.php#my-stories"><strong><?=$storyCounts['all']?></strong><span>Tất cả</span></a><a class="<?=$stateFilter==='waiting'?'active':''?>" href="?state=waiting#my-stories"><strong><?=$storyCounts['waiting']?></strong><span>Chờ duyệt</span></a><a class="<?=$stateFilter==='changes'?'active':''?>" href="?state=changes#my-stories"><strong><?=$storyCounts['changes']?></strong><span>Cần chỉnh sửa</span></a><a class="<?=$stateFilter==='published'?'active':''?>" href="?state=published#my-stories"><strong><?=$storyCounts['published']?></strong><span>Đã xuất bản</span></a></div>
<div class="member-posts-tools"><label class="member-posts-search">⌕ <input type="search" id="memberStorySearch" placeholder="Tìm theo tên truyện..." autocomplete="off"></label><select id="memberStoryType" aria-label="Lọc loại truyện"><option value="all">Tất cả loại truyện</option><option value="audio">Truyện audio</option><option value="short">Truyện ngắn</option><option value="serial">Truyện nhiều chương</option></select><span id="memberStoryResult"><?=count($mine)?> tác phẩm</span></div>
<?php if(!$mine):?><p class="submit-muted"><?=count($allMine)===0?'Bạn chưa đăng truyện nào.':'Không có bài viết nào thuộc trạng thái này.'?></p><?php else:?><?php foreach($mine as $s):?><article class="submit-story-item member-post-card" data-title="<?=ss_h(mb_strtolower($s['title']))?>" data-type="<?=ss_h(($s['story_type']==='audio'?'audio':($s['story_type']==='both'?'audio ':'').$s['story_kind']))?>"><div class="member-post-card-top"><div><strong><?=ss_h($s['title'])?></strong><span class="member-post-status <?=($s['status']==='published'?'is-published':(($s['review_decision']??'')==='rejected'?'is-revision':'is-pending'))?>"><?=($s['status']==='published'?'Đã xuất bản':(($s['review_decision']??'')==='rejected'?'Cần chỉnh sửa':(($s['review_decision']??'')==='approved'?'Đã duyệt':'Chờ kiểm duyệt')))?></span></div><span class="member-post-date"><?=ss_h(date('d/m/Y',strtotime($s['created_at'])))?></span></div><p class="submit-muted"><?=$s['story_type']==='audio'?'Truyện audio':($s['story_kind']==='short'?'Truyện ngắn':'Truyện nhiều chương · '.(int)$s['chapter_count'].' chương')?> · <?=$s['status']==='published'?'Đã duyệt · Đang xuất bản':(($s['review_decision']??'')==='rejected'?'Cần chỉnh sửa':(($s['review_decision']??'')==='approved'?'Đã duyệt':'Chờ kiểm duyệt'))?></p><?php if(!empty($s['review_note'])):?><p class="submit-muted">Góp ý kiểm duyệt: <?=ss_h($s['review_note'])?></p><?php endif;?>
<?php if($s['story_kind']==='serial'&&!empty($chapterStates[(int)$s['id']])):?><div class="submit-muted" style="margin:9px 0"><strong>Trạng thái từng chương:</strong><?php foreach($chapterStates[(int)$s['id']] as $chapterState):?><div style="padding:5px 0">Chương <?= (int)$chapterState['chapter_number']?> · <?=ss_h($chapterState['title'])?> — <?=($chapterState['status']==='published'?'Đã duyệt':(($chapterState['decision']??'')==='rejected'?'Cần chỉnh sửa':'Chờ duyệt'))?><?php if(!empty($chapterState['note'])):?> · Góp ý: <?=ss_h($chapterState['note'])?><?php endif;?></div><?php endforeach;?></div><?php endif;?>
<p><a class="submit-btn" style="display:inline-block;text-decoration:none;margin:4px 0" href="edit_story.php?id=<?=(int)$s['id']?>">✎ Sửa bài viết</a> <?php if($s['status']==='published'):?><a href="<?=($s['story_type']==='audio'?'audio/':($s['story_kind']==='short'?'truyen-ngan/':'truyen/')).rawurlencode($s['slug'])?>.html">Xem truyện</a><?php endif;?></p><?php if($s['story_kind']==='serial'):?><details><summary>＋ Gửi thêm chương</summary><form class="submit-form" method="post"><input type="hidden" name="csrf" value="<?=ss_h($csrf)?>"><input type="hidden" name="action" value="add_chapter"><input type="hidden" name="story_id" value="<?=(int)$s['id']?>"><label>Tên chương mới<input name="chapter_title" maxlength="220" required></label><label>Nội dung chương<textarea name="chapter_content" required></textarea></label><button class="submit-btn" type="submit">Gửi chương</button></form></details><?php endif;?></article><?php endforeach;?><?php endif;?></section><?php endif;?></main>
<script>
const storySearch=document.getElementById('memberStorySearch'),storyTypeFilter=document.getElementById('memberStoryType'),storyCards=[...document.querySelectorAll('.member-post-card')],storyResult=document.getElementById('memberStoryResult');
function filterMemberStories(){const term=(storySearch?.value||'').trim().toLocaleLowerCase('vi'),type=storyTypeFilter?.value||'all';let shown=0;storyCards.forEach(card=>{const matchName=(card.dataset.title||'').includes(term),matchType=type==='all'||(card.dataset.type||'').split(/\\s+/).includes(type);card.hidden=!(matchName&&matchType);if(!card.hidden)shown++;});if(storyResult)storyResult.textContent=shown+' / '+storyCards.length+' tác phẩm';}
storySearch?.addEventListener('input',filterMemberStories);storyTypeFilter?.addEventListener('change',filterMemberStories);
const coverInput=document.getElementById('cover-file'),coverPreview=document.getElementById('cover-preview');let coverPreviewUrl='';coverInput.addEventListener('change',function(){if(coverPreviewUrl)URL.revokeObjectURL(coverPreviewUrl);const file=this.files&&this.files[0];if(!file){coverPreview.hidden=true;coverPreview.style.display='none';return;}if(!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)||file.size>5*1024*1024){alert('Chọn ảnh JPG, PNG, WEBP hoặc GIF không quá 5 MB.');this.value='';coverPreview.hidden=true;coverPreview.style.display='none';return;}coverPreviewUrl=URL.createObjectURL(file);coverPreview.src=coverPreviewUrl;coverPreview.hidden=false;coverPreview.style.display='block';});</script><?php require __DIR__ . '/includes/footer.php'; ?></body></html>