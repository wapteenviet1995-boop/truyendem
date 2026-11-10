<?php
require __DIR__ . '/includes/db.php';
$slug = trim((string)($_GET['slug'] ?? ''));
$stmt = $pdo->prepare("SELECT * FROM posts WHERE slug=? AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); }
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $post ? e($post['title']) . ' | Góc Hoàng Đạo' : 'Không tìm thấy bài viết' ?></title><link rel="stylesheet" href="css/style.css?v=community1"></head><body class="blog-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="compatibility.php">Tương hợp tình yêu</a><a href="community.php">Cộng đồng</a><a href="blog.php">Blog</a><?php if (!empty($_SESSION['member_id'])): ?><a href="account.php">Tài khoản</a><form class="member-nav-logout" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf'] ?? '') ?>"><button type="submit">Đăng xuất</button></form><?php else: ?><a href="login.php">Đăng nhập</a><a href="register.php">Đăng ký</a><?php endif; ?></nav></div></header>
<main class="blog-main"><?php if (!$post): ?><h1>Không tìm thấy bài viết</h1><p><a href="blog.php">Quay lại blog</a></p><?php else: ?><article class="blog-article"><a class="blog-back" href="blog.php">← Tất cả bài viết</a><p class="blog-category"><?= e($post['category']) ?></p><h1><?= e($post['title']) ?></h1><p class="meta">Đăng ngày <?= e(date('d/m/Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></p>
<?php if ($post['image_url']): ?><img class="blog-hero" src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>"><?php endif; ?>
<div class="blog-content"><?= nl2br(e($post['content'])) ?></div></article><?php endif; ?></main><footer class="site-footer"><div class="footer-bottom">© 2026 Góc Hoàng Đạo</div></footer></body></html>
