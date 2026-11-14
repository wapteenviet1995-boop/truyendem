<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

if (!empty($_SESSION['member_id'])) { header('Location: account.php'); exit; }
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['display_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $csrf = (string)($_POST['csrf'] ?? '');

    if (!hash_equals($_SESSION['member_csrf'], $csrf)) {
        $error = 'Phiên đăng ký đã hết hạn. Hãy tải lại trang và thử lại.';
    } elseif ($name === '' || mb_strlen($name) > 100) {
        $error = 'Vui lòng nhập họ tên (tối đa 100 ký tự).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Địa chỉ email chưa hợp lệ.';
    } elseif (strlen($password) < 8) {
        $error = 'Mật khẩu cần có ít nhất 8 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Hai mật khẩu không khớp.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO members (display_name,email,password_hash) VALUES (?,?,?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['member_id'] = (int)$pdo->lastInsertId();
            $_SESSION['member_name'] = $name;
            $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
            header('Location: account.php?welcome=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $error = 'Email này đã được đăng ký. Hãy đăng nhập hoặc dùng email khác.';
            else throw $e;
        }
    }
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đăng ký thành viên | Truyện Đêm</title><link rel="stylesheet" href="css/style.css?v=member1"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
</head>
<body class="member-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="login.php">Đăng nhập</a></nav></div></header>
<main class="member-wrap"><section class="member-panel"><div class="member-symbol">✧</div><h1>Tạo tài khoản</h1><p class="member-lead">Tham gia Truyện Đêm và lưu lại hành trình khám phá của bạn.</p>
<?php if ($error): ?><p class="member-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="member-form">
<input type="hidden" name="csrf" value="<?= e($_SESSION['member_csrf']) ?>">
<label>Họ và tên<input name="display_name" maxlength="100" autocomplete="name" value="<?= e($name) ?>" required></label>
<label>Email<input type="email" name="email" maxlength="190" autocomplete="email" value="<?= e($email) ?>" required></label>
<label>Mật khẩu<input type="password" name="password" minlength="8" autocomplete="new-password" required><small>Ít nhất 8 ký tự.</small></label>
<label>Nhập lại mật khẩu<input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label>
<button class="member-submit" type="submit">Đăng ký</button>
</form><p class="member-switch">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
</section></main><footer class="member-footer">© 2026 Truyện Đêm</footer>
</body></html>
