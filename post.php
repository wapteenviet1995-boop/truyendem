<?php
require __DIR__ . '/includes/db.php';
$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = $pdo->prepare("SELECT * FROM posts WHERE slug=? AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $post ? e($post['title']) . ' | Truyện Đêm' : 'Không tìm thấy bài viết' ?></title><link rel="stylesheet" href="css/style.css?v=socialdiary1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
</head><body class="blog-page">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="blog-main"><?php if (!$post): ?><h1>Không tìm thấy bài viết</h1><p><a href="blog.php">Quay lại blog</a></p><?php else: ?><article class="blog-article"><a class="blog-back" href="blog.php">← Tất cả bài viết</a><p class="blog-category"><?= e($post['category']) ?></p><h1><?= e($post['title']) ?></h1><p class="meta">Đăng ngày <?= e(date('d/m/Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></p>
<?php if ($post['image_url']): ?><img class="blog-hero" src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>"><?php endif; ?>
<div class="blog-content"><?= nl2br(e($post['content'])) ?></div></article><?php endif; ?></main><?php require __DIR__ . '/includes/footer.php'; ?><script src="js/presence.js"></script>
</body></html>
