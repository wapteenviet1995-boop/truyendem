<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
if(empty($_SESSION['blog_admin_id'])){header('Location: login.php');exit;}
if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf'];$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals($csrf,(string)($_POST['csrf']??''))){$error='Phiên đã hết hạn.';}
 else{
  $action=(string)($_POST['action']??'');
  try{
   if($action==='story'){
    $title=trim((string)($_POST['title']??''));$slug=trim((string)($_POST['slug']??''));$author=trim((string)($_POST['author']??'Khuyết danh'));$desc=trim((string)($_POST['description']??''));$cover=trim((string)($_POST['cover_url']??''));$category=trim((string)($_POST['category']??'Truyện dài'));$status=($_POST['status']??'draft')==='published'?'published':'draft';$id=(int)($_POST['story_id']??0);
    if($slug===''){$slug=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title);$slug=strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-',$slug),'-'));}
    $slug=strtolower(trim(preg_replace('/[^a-z0-9-]+/','-',$slug),'-'));
    if($title===''||$slug==='')$error='Cần nhập tên truyện hợp lệ.';
    elseif($id){$q=$pdo->prepare('UPDATE stories SET title=?,slug=?,author=?,description=?,cover_url=?,category=?,status=? WHERE id=?');$q->execute([$title,$slug,$author,$desc,$cover,$category,$status,$id]);}
    else{$q=$pdo->prepare('INSERT INTO stories(title,slug,author,description,cover_url,category,status) VALUES(?,?,?,?,?,?,?)');$q->execute([$title,$slug,$author,$desc,$cover,$category,$status]);}
   }elseif($action==='chapter'){
    $storyId=(int)($_POST['story_id']??0);$num=max(1,(int)($_POST['chapter_number']??1));$title=trim((string)($_POST['chapter_title']??''));$content=trim((string)($_POST['content']??''));$audio=trim((string)($_POST['audio_url']??''));$status=($_POST['chapter_status']??'draft')==='published'?'published':'draft';
    if(!$storyId||$title===''||$content==='')$error='Chọn truyện và nhập tiêu đề, nội dung chương.';
    else{$q=$pdo->prepare('INSERT INTO chapters(story_id,chapter_number,title,content,audio_url,status) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content),audio_url=VALUES(audio_url),status=VALUES(status)');$q->execute([$storyId,$num,$title,$content,$audio,$status]);}
   }
   if(!$error){header('Location: stories.php?saved=1');exit;}
  }catch(PDOException $e){$error='Không lưu được. Kiểm tra slug và số chương đã tồn tại.';}
 }
}
$stories=$pdo->query("SELECT s.*,(SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id) chapter_count FROM stories s ORDER BY s.updated_at DESC")->fetchAll();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quản lý truyện | Truyện Đêm</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/stories.css"></head><body class="story-site"><main class="story-main"><section class="story-panel"><h1>Quản lý thư viện truyện</h1><p><a href="index.php">← Quản trị</a> · <a href="../index.php" target="_blank">Xem website</a></p><?php if($error):?><p><?=htmlspecialchars($error)?></p><?php endif;?><?php if(isset($_GET['saved'])):?><p>Đã lưu thành công.</p><?php endif;?></section>
<section class="story-panel" style="margin-top:18px"><h2>Thêm / cập nhật truyện</h2><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="story"><label>Tên truyện<input name="title" required></label><label>Slug (để trống tự tạo)<input name="slug"></label><label>Tác giả<input name="author" value="Khuyết danh"></label><label>Thể loại<input name="category" value="Truyện dài"></label><label>Ảnh bìa URL<input name="cover_url" type="url"></label><label>Mô tả<textarea name="description" rows="3"></textarea></label><label>Trạng thái<select name="status"><option value="draft">Bản nháp</option><option value="published">Xuất bản</option></select></label><button class="story-btn">Lưu truyện</button></form></section>
<section class="story-panel" style="margin-top:18px"><h2>Thêm chương</h2><form method="post"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="action" value="chapter"><label>Truyện<select name="story_id" required><?php foreach($stories as $s):?><option value="<?=(int)$s['id']?>"><?=htmlspecialchars($s['title'])?></option><?php endforeach;?></select></label><label>Số chương<input type="number" name="chapter_number" min="1" value="1" required></label><label>Tên chương<input name="chapter_title" required></label><label>Nội dung chương<textarea name="content" rows="12" required></textarea></label><label>Link audio (nếu có)<input name="audio_url" type="url"></label><label>Trạng thái<select name="chapter_status"><option value="draft">Bản nháp</option><option value="published">Xuất bản</option></select></label><button class="story-btn">Lưu chương</button></form></section>
<section class="story-panel" style="margin-top:18px"><h2>Truyện hiện có</h2><?php foreach($stories as $s):?><p>📖 <?=htmlspecialchars($s['title'])?> · <?= (int)$s['chapter_count']?> chương · <?=htmlspecialchars($s['status'])?> · <a href="../story.php?slug=<?=rawurlencode($s['slug'])?>" target="_blank">Xem</a></p><?php endforeach;?></section></main></body></html>