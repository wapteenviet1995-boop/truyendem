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
$favoriteSigns = [
 ['Bạch Dương','♈'],['Kim Ngưu','♉'],['Song Tử','♊'],['Cự Giải','♋'],
 ['Sư Tử','♌'],['Xử Nữ','♍'],['Thiên Bình','♎'],['Bọ Cạp','♏'],
 ['Nhân Mã','♐'],['Ma Kết','♑'],['Bảo Bình','♒'],['Song Ngư','♓']
];
$favoriteStmt = $pdo->prepare('SELECT sign_one,sign_two,score,created_at FROM member_compatibility_favorites WHERE member_id=? ORDER BY created_at DESC,id DESC');
$favoriteStmt->execute([(int)$_SESSION['member_id']]);
$favoritePairs = $favoriteStmt->fetchAll();
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tài khoản của tôi | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=socialdiary1"></head>
<body class="member-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="compatibility.php">Tương hợp tình yêu</a><a href="community.php">Cộng đồng</a><a href="friends.php">Kết bạn</a><a href="diary.php">Nhật ký</a><a href="messages.php">Tin nhắn</a><a href="blog.php">Blog</a><a href="account.php">Tài khoản</a><form class="member-nav-logout" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf'] ?? '') ?>"><button type="submit">Đăng xuất</button></form></nav></div></header>
<main class="member-wrap"><section class="member-panel account-panel"><div class="member-symbol">✦</div><h1>Tài khoản của tôi</h1>
<?php if (isset($_GET['welcome'])): ?><p class="member-success">Đăng ký thành công! Chào mừng bạn đến với Góc Hoàng Đạo.</p><?php endif; ?>
<p class="member-lead">Xin chào, <?= e($member['display_name']) ?>. Rất vui được gặp lại bạn!</p>
<div class="member-info"><div><span>Họ và tên</span><strong><?= e($member['display_name']) ?></strong></div><div><span>Email</span><strong><?= e($member['email']) ?></strong></div><div><span>Ngày tham gia</span><strong><?= e(date('d/m/Y', strtotime($member['created_at']))) ?></strong></div></div>
<div class="member-actions"><a class="member-secondary" href="index.php">Khám phá 12 cung</a><a class="member-secondary" href="compatibility.php">Thử độ tương hợp</a><a class="member-secondary" href="community.php">Cộng đồng</a><a class="member-secondary" href="friends.php">Kết bạn theo cung</a><a class="member-secondary" href="diary.php">Nhật ký vũ trụ</a><a class="member-secondary" href="messages.php">Tin nhắn riêng</a><a class="member-secondary" href="blog.php">Đọc blog</a></div>
<section class="member-favorites"><h2>♡ Cặp cung yêu thích</h2>
<?php if (!$favoritePairs): ?><p class="member-lead">Bạn chưa lưu cặp cung nào. Hãy thử tính độ tương hợp và lưu kết quả nhé.</p>
<?php else: ?><div class="member-favorite-list"><?php foreach ($favoritePairs as $pair): $one=(int)$pair['sign_one']; $two=(int)$pair['sign_two']; ?>
<div class="member-favorite-item"><div><strong><?= e($favoriteSigns[$one][1].' '.$favoriteSigns[$one][0]) ?> ♥ <?= e($favoriteSigns[$two][1].' '.$favoriteSigns[$two][0]) ?></strong><small>Đã lưu ngày <?= e(date('d/m/Y',strtotime($pair['created_at']))) ?></small></div><b><?= (int)$pair['score'] ?>%</b><a href="compatibility.php?me=<?= $one ?>&other=<?= $two ?>">Xem lại</a></div>
<?php endforeach; ?></div><?php endif; ?></section>
<form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf']) ?>"><button class="member-submit member-logout" type="submit">Đăng xuất</button></form>
</section></main><footer class="member-footer">© 2026 Góc Hoàng Đạo</footer>
<script src="js/presence.js"></script></body></html>
