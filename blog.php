<?php
require __DIR__ . '/includes/db.php';
$stmt = $pdo->query("SELECT id,title,slug,excerpt,image_url,category,published_at FROM posts WHERE status='published' ORDER BY published_at DESC, id DESC");
$posts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blog | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=20261111-4"></head>
<body class="blog-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="compatibility.php">Tương hợp tình yêu</a><a href="community.php">Cộng đồng</a><a href="friends.php">Kết bạn</a><a href="diary.php">Nhật ký</a><a href="messages.php">Tin nhắn</a><a href="blog.php">Blog</a><?php if (!empty($_SESSION['member_id'])): ?><a href="account.php">Tài khoản</a><form class="member-nav-logout" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf'] ?? '') ?>"><button type="submit">Đăng xuất</button></form><?php else: ?><a href="login.php">Đăng nhập</a><a href="register.php">Đăng ký</a><?php endif; ?></nav></div></header>
<main class="blog-main"><h1>Blog Góc Hoàng Đạo</h1><p class="lead">Những câu chuyện, góc nhìn và bài viết về 12 cung hoàng đạo.</p>
<?php if (!$posts): ?><section class="blog-empty"><h2>Chưa có bài viết</h2><p>Các bài viết mới sẽ xuất hiện tại đây.</p></section>
<?php else: ?><div class="blog-grid"><?php foreach ($posts as $post): ?><article class="blog-card">
<?php if (!empty($post['image_url'])): ?><a href="post.php?slug=<?= rawurlencode($post['slug']) ?>"><img src="<?= e($post['image_url']) ?>" alt="<?= e($post['title']) ?>" loading="lazy"></a><?php endif; ?>
<div class="blog-card-body"><p class="blog-category"><?= e($post['category']) ?></p><h2><a href="post.php?slug=<?= rawurlencode($post['slug']) ?>"><?= e($post['title']) ?></a></h2><p><?= e($post['excerpt']) ?></p><a class="blog-read" href="post.php?slug=<?= rawurlencode($post['slug']) ?>">Đọc bài viết →</a></div></article><?php endforeach; ?></div><?php endif; ?>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Góc Hoàng Đạo</div></footer>
<script src="js/presence.js"></script>
</body></html>
