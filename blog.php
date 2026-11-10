<?php
require __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT id,title,slug,excerpt,image_url,category,published_at FROM posts WHERE status='published' ORDER BY published_at DESC, id DESC");
$posts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blog | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=20261111"></head>
<body class="blog-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.html"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.html">Trang chủ</a><a href="blog.php">Blog</a><a href="login.php">Đăng nhập</a><a href="register.php">Đăng ký</a></nav></div></header>
<main class="blog-main"><h1>Blog Góc Hoàng Đạo</h1><p class="lead">Những câu chuyện, góc nhìn và bài viết về 12 cung hoàng đạo.</p>
<?php if (!$posts): ?><section class="blog-empty"><h2>Chưa có bài viết</h2><p>Các bài viết mới sẽ xuất hiện tại đây.</p></section>
<?php else: ?><div class="blog-grid"><?php foreach ($posts as $post): ?><article class="blog-card">
<?php if (!empty($post['image_url'])): ?><a href="post.php?slug=<?= rawurlencode($post['slug']) ?>"><img src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></a><?php endif; ?>
<div class="blog-card-body"><p class="blog-category"><?= e($post['category']) ?></p><h2><a href="post.php?slug=<?= rawurlencode($post['slug']) ?>"><?= e($post['title']) ?></a></h2><p><?= e($post['excerpt']) ?></p><a class="blog-read" href="post.php?slug=<?= rawurlencode($post['slug']) ?>">Đọc bài viết →</a></div></article><?php endforeach; ?></div><?php endif; ?>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Góc Hoàng Đạo</div></footer>
</body></html>
