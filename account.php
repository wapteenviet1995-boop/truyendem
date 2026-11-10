<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['member_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
$stmt = $pdo->prepare('SELECT id,display_name,email,created_at,last_login_at FROM members WHERE id=? AND is_active=1 LIMIT 1');
$stmt->execute([(int)$_SESSION['member_id']]);
$member = $stmt->fetch();
if (!$member) {
    unset($_SESSION['member_id'], $_SESSION['member_name']);
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tài khoản của tôi | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=member1"></head>
<body class="member-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="blog.php">Blog</a><a href="account.php">Tài khoản</a><form class="member-nav-logout" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf'] ?? '') ?>"><button type="submit">Đăng xuất</button></form></nav></div></header>
<main class="member-wrap"><section class="member-panel account-panel"><div class="member-symbol">✦</div><h1>Tài khoản của tôi</h1>
<?php if (isset($_GET['welcome'])): ?><p class="member-success">Đăng ký thành công! Chào mừng bạn đến với Góc Hoàng Đạo.</p><?php endif; ?>
<p class="member-lead">Xin chào, <?= e($member['display_name']) ?>. Rất vui được gặp lại bạn!</p>
<div class="member-info"><div><span>Họ và tên</span><strong><?= e($member['display_name']) ?></strong></div><div><span>Email</span><strong><?= e($member['email']) ?></strong></div><div><span>Ngày tham gia</span><strong><?= e(date('d/m/Y', strtotime($member['created_at']))) ?></strong></div></div>
<div class="member-actions"><a class="member-secondary" href="index.php">Khám phá 12 cung</a><a class="member-secondary" href="blog.php">Đọc blog</a></div>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf']) ?>"><button class="member-submit member-logout" type="submit">Đăng xuất</button></form>
</section></main><footer class="member-footer">© 2026 Góc Hoàng Đạo</footer>
</body></html>
