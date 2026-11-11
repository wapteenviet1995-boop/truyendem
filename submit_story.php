<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: login.php?next=submit_story.php'); exit; }
$memberId=(int)$_SESSION['member_id'];
if(empty($_SESSION['member_csrf']))$_SESSION['member_csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['member_csrf'];$error='';$notice='';
function ss_slug(string $value):string{
 if(function_exists('iconv')){$v=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);if($v!==false)$value=$v;}
 return strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/','-',$value),'-'));
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn. Hãy tải lại trang.';}
 else{
  try{
   $action=(string)($_POST['action']??'');
   if($action==='create_story'){
    $title=trim((string)($_POST['title']??''));$kind=($_POST['story_kind']??'short')==='serial'?'serial':'short';
    $category=trim((string)($_POST['category']??''))?:'Truyện mới';$description=trim((string)($_POST['description']??''));
    $cover=trim((string)($_POST['cover_url']??''));$short=trim((string)($_POST['short_content']??''));
    $chapterTitle=trim((string)($_POST['chapter_title']??''));$chapterBody=trim((string)($_POST['chapter_content']??''));
    if($title===''||mb_strlen($title)>220)$error='Tên truyện không được để trống và tối đa 220 ký tự.';
    elseif($cover!==''&&!filter_var($cover,FILTER_VALIDATE_URL)&&!preg_match('~^/uploads/stories/[a-zA-Z0-9._-]+$~',$cover))$error='Link ảnh bìa không hợp lệ.';
    elseif($kind==='short'&&$short==='')$error='Bạn cần nhập nội dung truyện ngắn.';
    elseif($kind==='serial'&&($chapterTitle===''||$chapterBody===''))$error='Truyện nhiều chương cần có tên và nội dung chương đầu tiên.';
    else{
     $base=ss_slug($title);if($base==='')$base='truyen-moi';$slug=$base.'-'.bin2hex(random_bytes(3));
     $pdo->beginTransaction();
     $ins=$pdo->prepare("INSERT INTO stories(title,slug,author,member_id,description,cover_url,category,story_type,status,story_kind,short_content) VALUES(?,?,?,?,?,?,?,'text','draft',?,?)");
     $ins->execute([$title,$slug,(string)($_SESSION['member_name']??'Thành viên'),$memberId,$description,$cover,$category,$kind,$kind==='short'?$short:null]);
     $storyId=(int)$pdo->lastInsertId();
     if($kind==='serial'){$pdo->prepare("INSERT INTO chapters(story_id,chapter_number,title,content,status) VALUES(?,1,?,?,'draft')")->execute([$storyId,$chapterTitle,$chapterBody]);}
     $pdo->commit();header('Location: submit_story.php?submitted=1');exit;
    }
   }elseif($action==='add_chapter'){
    $storyId=(int)($_POST['story_id']??0);$title=trim((string)($_POST['chapter_title']??''));$body=trim((string)($_POST['chapter_content']??''));
    $check=$pdo->prepare("SELECT id FROM stories WHERE id=? AND member_id=? AND story_kind='serial'");$check->execute([$storyId,$memberId]);
    if(!$check->fetchColumn())$error='Không tìm thấy truyện của bạn.';
    elseif($title===''||$body==='')$error='Hãy nhập tên và nội dung chương.';
    else{
     $pdo->beginTransaction();$q=$pdo->prepare('SELECT COALESCE(MAX(chapter_number),0)+1 FROM chapters WHERE story_id=? FOR UPDATE');$q->execute([$storyId]);$number=(int)$q->fetchColumn();
     $pdo->prepare("INSERT INTO chapters(story_id,chapter_number,title,content,status) VALUES(?,?,?,?,'draft')")->execute([$storyId,$number,$title,$body]);
     $pdo->commit();header('Location: submit_story.php?chapter_added=1');exit;
    }
   }
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Chưa lưu được nội dung. Vui lòng kiểm tra dữ liệu và thử lại.';}
 }
}
if(isset($_GET['submitted']))$notice='Đã gửi truyện! Truyện đang chờ quản trị viên duyệt trước khi xuất bản.';
if(isset($_GET['chapter_added']))$notice='Đã gửi chương mới. Chương đang chờ quản trị viên duyệt.';
$q=$pdo->prepare("SELECT s.id,s.title,s.slug,s.story_kind,s.status,s.created_at,(SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id) chapter_count FROM stories s WHERE s.member_id=? ORDER BY s.id DESC");$q->execute([$memberId]);$mine=$q->fetchAll();
function ss_h($v):string{return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đăng truyện | Truyện Đêm</title><link rel="stylesheet" href="css/style.css?v=20261112-submitstory"><link rel="stylesheet" href="css/stories.css?v=20261112-submitstory"><style>
.submit-wrap{max-width:900px;margin:30px auto;padding:0 15px 70px}.submit-panel{background:#14131d;border:1px solid #332b43;border-radius:17px;padding:22px;margin:16px 0}.submit-panel h1,.submit-panel h2{margin:0 0 12px}.submit-panel p,.submit-muted{color:#aaa5b8}.submit-form label{display:block;margin:14px 0;color:#ddd5eb}.submit-form input,.submit-form textarea,.submit-form select{display:block;box-sizing:border-box;width:100%;margin-top:7px;padding:12px;border:1px solid #484052;border-radius:10px;background:#0c0b12;color:#fff;font:inherit}.submit-form textarea{min-height:180px;line-height:1.7}.submit-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}.submit-btn{border:0;border-radius:10px;padding:12px 17px;background:#ad8be6;color:#17111f;font-weight:800;cursor:pointer}.submit-flash{padding:12px;border-radius:10px;background:#203126;color:#c7f4d2}.submit-error{padding:12px;border-radius:10px;background:#3b2029;color:#ffc6d1}.submit-story-item{padding:14px;border:1px solid #373044;border-radius:12px;margin:12px 0}.submit-story-item form{margin-top:12px}.submit-story-item summary{color:#d6c2f5;cursor:pointer;font-weight:700}@media(max-width:600px){.submit-panel{padding:15px}.submit-wrap{padding-left:11px;padding-right:11px}}
</style></head><body class="story-site">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">☾</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="library.php">Tủ sách</a><a href="community.php">Cộng đồng</a><a href="account.php">Tài khoản</a></nav></div></header>
<main class="submit-wrap"><section class="submit-panel"><h1>✦ Đăng truyện của bạn</h1><p>Chia sẻ câu chuyện của bạn với cộng đồng Truyện Đêm. Truyện và chương mới sẽ được quản trị viên duyệt trước khi hiển thị công khai.</p><?php if($notice):?><p class="submit-flash"><?=ss_h($notice)?></p><?php endif;?><?php if($error):?><p class="submit-error"><?=ss_h($error)?></p><?php endif;?>
<form class="submit-form" method="post"><input type="hidden" name="csrf" value="<?=ss_h($csrf)?>"><input type="hidden" name="action" value="create_story">
<div class="submit-grid"><label>Tên truyện *<input name="title" maxlength="220" required></label><label>Thể loại<input name="category" maxlength="100" placeholder="Kinh dị, tình cảm, fantasy..."></label><label>Hình thức<select name="story_kind" id="submit-kind"><option value="short">Truyện ngắn</option><option value="serial">Truyện nhiều chương</option></select></label><label>Ảnh bìa (không bắt buộc)<input name="cover_url" type="url" placeholder="https://..."></label></div>
<label>Mô tả truyện<textarea name="description" rows="3" maxlength="3000" placeholder="Giới thiệu ngắn về câu chuyện..."></textarea></label>
<div id="submit-short"><label>Nội dung truyện ngắn *<textarea name="short_content" placeholder="Viết toàn bộ truyện ngắn tại đây..."></textarea></label></div>
<div id="submit-serial" hidden><div class="submit-grid"><label>Tên chương đầu tiên *<input name="chapter_title" maxlength="220" placeholder="Chương 1"></label></div><label>Nội dung chương đầu tiên *<textarea name="chapter_content" placeholder="Viết nội dung chương đầu tiên..."></textarea></label></div>
<button class="submit-btn" type="submit">Gửi truyện để duyệt</button></form></section>
<section class="submit-panel"><h2>📚 Truyện của tôi</h2><?php if(!$mine):?><p class="submit-muted">Bạn chưa đăng truyện nào.</p><?php else:?><?php foreach($mine as $s):?><article class="submit-story-item"><strong><?=ss_h($s['title'])?></strong><p class="submit-muted"><?=($s['story_kind']==='short'?'Truyện ngắn':'Truyện nhiều chương · '.(int)$s['chapter_count'].' chương')?> · <?=$s['status']==='published'?'Đã xuất bản':'Đang chờ duyệt / bản nháp'?></p><?php if($s['status']==='published'):?><a href="<?=($s['story_kind']==='short'?'truyen-ngan/':'truyen/').rawurlencode($s['slug'])?>.html">Xem truyện</a><?php endif;?><?php if($s['story_kind']==='serial'):?><details><summary>＋ Gửi thêm chương</summary><form class="submit-form" method="post"><input type="hidden" name="csrf" value="<?=ss_h($csrf)?>"><input type="hidden" name="action" value="add_chapter"><input type="hidden" name="story_id" value="<?=(int)$s['id']?>"><label>Tên chương mới<input name="chapter_title" maxlength="220" required></label><label>Nội dung chương<textarea name="chapter_content" required></textarea></label><button class="submit-btn" type="submit">Gửi chương</button></form></details><?php endif;?></article><?php endforeach;?><?php endif;?></section></main>
<script>const kind=document.getElementById('submit-kind'),shortBox=document.getElementById('submit-short'),serialBox=document.getElementById('submit-serial');function syncKind(){const serial=kind.value==='serial';shortBox.hidden=serial;serialBox.hidden=!serial;}kind.addEventListener('change',syncKind);syncKind();</script></body></html>