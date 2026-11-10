<?php
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { http_response_code(403); exit('Bạn chưa đăng nhập.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Yêu cầu không hợp lệ.'); }
$id=(int)($_POST['id']??0); $title=trim((string)($_POST['title']??'')); $slug=trim((string)($_POST['slug']??'')); $excerpt=trim((string)($_POST['excerpt']??'')); $content=trim((string)($_POST['content']??'')); $image=trim((string)($_POST['image_url']??'')); $category=trim((string)($_POST['category']??'Góc Hoàng Đạo')); $status=($_POST['status']??'draft')==='published'?'published':'draft';
if($slug===''){ $slug=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title); $slug=strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-',$slug),'-')); }
$slug=strtolower(trim(preg_replace('/[^a-z0-9-]+/','-', $slug),'-'));
if($title===''||$content===''||$slug===''){ exit('Vui lòng nhập tiêu đề và nội dung hợp lệ.'); }
$published=$status==='published'?date('Y-m-d H:i:s'):null;
try {
 if($id){ $q=$pdo->prepare("UPDATE posts SET title=?,slug=?,excerpt=?,content=?,image_url=?,category=?,status=?,published_at=IF(?='published',COALESCE(published_at,NOW()),NULL) WHERE id=?"); $q->execute([$title,$slug,$excerpt,$content,$image,$category,$status,$status,$id]); }
 else { $q=$pdo->prepare('INSERT INTO posts(title,slug,excerpt,content,image_url,category,status,published_at) VALUES(?,?,?,?,?,?,?,?)'); $q->execute([$title,$slug,$excerpt,$content,$image,$category,$status,$published]); }
 header('Location: index.php'); exit;
} catch(PDOException $e){ http_response_code(400); exit('Không lưu được. Có thể slug đã được dùng cho bài viết khác.'); }
