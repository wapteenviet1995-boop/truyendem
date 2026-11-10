<?php
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { http_response_code(403); exit('Bạn chưa đăng nhập.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Yêu cầu không hợp lệ.'); }
$id = (int)($_POST['id'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$slug = trim((string)($_POST['slug'] ?? ''));
$excerpt = trim((string)($_POST['excerpt'] ?? ''));
$content = trim((string)($_POST['content'] ?? ''));
$image = trim((string)($_POST['image_url'] ?? ''));
$category = trim((string)($_POST['category'] ?? '12 cung hoàng đạo'));
$status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
if ($slug === '') {
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug), '-'));
}
$slug = strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $slug), '-'));
if ($title === '' || $content === '' || $slug === '') { http_response_code(400); exit('Vui lòng nhập tiêu đề và nội dung hợp lệ.'); }
if ($image !== '' && !filter_var($image, FILTER_VALIDATE_URL)) { http_response_code(400); exit('Đường dẫn ảnh không hợp lệ.'); }

if (!empty($_FILES['cover_image']['name'])) {
    $file = $_FILES['cover_image'];
    if ($file['error'] !== UPLOAD_ERR_OK) { http_response_code(400); exit('Tải ảnh lên thất bại. Vui lòng thử lại.'); }
    if ($file['size'] > 3 * 1024 * 1024) { http_response_code(400); exit('Ảnh vượt quá giới hạn 3 MB.'); }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    if (!isset($types[$mime]) || !getimagesize($file['tmp_name'])) { http_response_code(400); exit('Định dạng ảnh không được hỗ trợ.'); }
    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) { http_response_code(500); exit('Không tạo được thư mục lưu ảnh.'); }
    $filename = bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) { http_response_code(500); exit('Không lưu được ảnh lên máy chủ.'); }
    $image = '/uploads/' . $filename;
}
$published = $status === 'published' ? date('Y-m-d H:i:s') : null;
try {
    if ($id) {
        $q = $pdo->prepare("UPDATE posts SET title=?,slug=?,excerpt=?,content=?,image_url=?,category=?,status=?,published_at=IF(?='published',COALESCE(published_at,NOW()),NULL) WHERE id=?");
        $q->execute([$title,$slug,$excerpt,$content,$image,$category,$status,$status,$id]);
    } else {
        $q = $pdo->prepare('INSERT INTO posts(title,slug,excerpt,content,image_url,category,status,published_at) VALUES(?,?,?,?,?,?,?,?)');
        $q->execute([$title,$slug,$excerpt,$content,$image,$category,$status,$published]);
    }
    header('Location: index.php?saved=1'); exit;
} catch (PDOException $e) {
    http_response_code(400);
    exit('Không lưu được. Có thể đường dẫn bài viết đã được dùng cho bài khác.');
}