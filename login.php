<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

if (!empty($_SESSION['member_id'])) { header('Location: account.php'); exit; }
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $csrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['member_csrf'], $csrf)) {
        $error = 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang và thử lại.';
    } else {
        $stmt = $pdo->prepare('SELECT id,display_name,email,password_hash FROM members WHERE email=? AND is_active=1 LIMIT 1');
        $stmt->execute([$email]);
        $member = $stmt->fetch();
        if ($member && password_verify($password, $member['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['member_id'] = (int)$member['id'];
            $_SESSION['member_name'] = $member['display_name'];
            $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
            $pdo->prepare('UPDATE members SET last_login_at=NOW() WHERE id=?')->execute([$member['id']]);
            header('Location: account.php');
            exit;
        }
        $error = 'Email hoặc mật khẩu không chính xác.';
    }
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đăng nhập | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=member1"></head>
<body class="member-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.html"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Góc Hoàng Đạo</strong><small>Khám phá câu chuyện của các vì sao</small></span></a><nav class="header-links"><a href="index.html">Trang chủ</a><a href="register.php">Đăng ký</a></nav></div></header>
<main class="member-wrap"><section class="member-panel"><div class="member-symbol">☾</div><h1>Chào mừng trở lại</h1><p class="member-lead">Đăng nhập để tiếp tục hành trình cùng những vì sao.</p>
<?php if ($error): ?><p class="member-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="member-form">
<input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf']) ?>">
<label>Email<input type="email" name="email" autocomplete="email" value="<?= e($email) ?>" required></label>
<label>Mật khẩu<input type="password" name="password" autocomplete="current-password" required></label>
<button class="member-submit" type="submit">Đăng nhập</button>
</form><p class="member-switch">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
</section></main><footer class="member-footer">© 2026 Góc Hoàng Đạo</footer>
</body></html>
