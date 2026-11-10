<?php
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
$post = ['title'=>'','slug'=>'','excerpt'=>'','content'=>'','image_url'=>'','category'=>'12 cung hoàng đạo','status'=>'draft'];
if ($id) {
    $q = $pdo->prepare('SELECT * FROM posts WHERE id=?');
    $q->execute([$id]);
    $found = $q->fetch();
    if (!$found) { http_response_code(404); exit('Không tìm thấy bài viết.'); }
    $post = $found;
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id ? 'Sửa bài viết' : 'Đăng bài mới' ?> | Góc Hoàng Đạo</title><link rel="stylesheet" href="../css/style.css?v=20261110"></head>
<body><main class="admin-wrap"><section class="admin-panel admin-wide post-editor">
<div class="admin-top"><h1><?= $id ? 'Sửa bài viết' : 'Đăng bài mới' ?></h1><a href="index.php">← Quản lý bài viết</a></div>
<p class="editor-help">Soạn nội dung, thêm ảnh minh họa rồi chọn lưu nháp hoặc xuất bản.</p>
<form method="post" action="save.php" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= (int)$id ?>">
<label>Tiêu đề bài viết *<input id="post-title" name="title" required maxlength="220" value="<?= e($post['title']) ?>" placeholder="Ví dụ: 3 cung hoàng đạo có vận may hôm nay"></label>
<label>Đường dẫn bài viết<input id="post-slug" name="slug" maxlength="240" value="<?= e($post['slug']) ?>" placeholder="tu-dong-tao-tu-tieu-de"></label>
<label>Mô tả ngắn<textarea name="excerpt" rows="3" maxlength="500" placeholder="Tóm tắt nội dung để hiển thị ngoài trang blog..."><?= e($post['excerpt']) ?></textarea></label>
<label>Nội dung bài viết *<textarea name="content" rows="16" required placeholder="Viết nội dung bài viết tại đây..."><?= e($post['content']) ?></textarea></label>
<label>Ảnh đại diện bài viết
<input type="file" name="cover_image" id="cover-image" accept="image/jpeg,image/png,image/webp,image/gif">
<small>Chọn ảnh JPG, PNG, WEBP hoặc GIF, tối đa 3 MB. Có thể dùng đường dẫn ảnh bên dưới.</small></label>
<label>Hoặc đường dẫn ảnh<input name="image_url" type="url" value="<?= e($post['image_url']) ?>" placeholder="https://..."></label>
<?php if (!empty($post['image_url'])): ?><div class="editor-current-image"><span>Ảnh hiện tại</span><img src="<?= e($post['image_url']) ?>" alt="Ảnh bài viết"></div><?php endif; ?>
<div id="image-preview" class="editor-current-image" hidden><span>Xem trước ảnh mới</span><img id="preview-img" alt="Ảnh xem trước"></div>
<label>Chuyên mục<input name="category" maxlength="100" value="<?= e($post['category']) ?>" placeholder="12 cung hoàng đạo"></label>
<div class="editor-actions"><button class="go" type="submit" name="status" value="published">Xuất bản bài viết</button><button class="save-draft" type="submit" name="status" value="draft">Lưu bản nháp</button><a href="index.php">Hủy</a></div>
</form></section></main>
<script>
(() => {
 const title=document.getElementById('post-title'), slug=document.getElementById('post-slug'), file=document.getElementById('cover-image'), box=document.getElementById('image-preview'), img=document.getElementById('preview-img');
 let slugEdited=!!slug.value;
 slug.addEventListener('input',()=>slugEdited=true);
 title.addEventListener('input',()=>{if(!slugEdited){slug.value=title.value.normalize('NFD').replace(/[\\u0300-\\u036f]/g,'').replace(/đ/g,'d').replace(/Đ/g,'D').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');}});
 file.addEventListener('change',()=>{const f=file.files[0];if(!f){box.hidden=true;return;}if(!/^image\\/(jpeg|png|webp|gif)$/.test(f.type)||f.size>3*1024*1024){alert('Ảnh phải là JPG, PNG, WEBP hoặc GIF và không quá 3 MB.');file.value='';box.hidden=true;return;}img.src=URL.createObjectURL(f);box.hidden=false;});
})();
</script></body></html>