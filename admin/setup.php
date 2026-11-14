<?php
require __DIR__ . '/../includes/db.php';
$config = require __DIR__ . '/../includes/config.php';
$count = (int)$pdo->query("SELECT COUNT(*) FROM blog_admins")->fetchColumn();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $count === 0) {
    $key = (string)($_POST['setup_key'] ?? '');
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!hash_equals((string)$config['setup_key'], $key)) {
        $message = 'Mã thiết lập không đúng.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,40}$/', $username) || strlen($password) < 10) {
        $message = 'Tên đăng nhập 3-40 ký tự (chữ/số/_), mật khẩu tối thiểu 10 ký tự.';
    } else {
        $q = $pdo->prepare('INSERT INTO blog_admins(username,password_hash) VALUES(?,?)');
        $q->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $message = 'Đã tạo tài khoản quản trị. Hãy xóa file admin/setup.php khỏi hosting ngay.';
        $count = 1;
    }
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Thiết lập quản trị blog</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/admin-white.css?v=20261114-white1">
</head><body class="admin-theme"><main class="admin-wrap"><section class="admin-panel"><h1>Thiết lập quản trị blog</h1><?php if ($count): ?><p>Đã có tài khoản quản trị hoặc quá trình thiết lập đã hoàn tất. Nếu vừa tạo tài khoản, hãy xóa file setup.php trên hosting.</p><?php else: ?><p>Chỉ sử dụng một lần. Không chia sẻ mã thiết lập hoặc mật khẩu.</p><?php if ($message): ?><p class="admin-message"><?= e($message) ?></p><?php endif; ?><form method="post"><label>Mã thiết lập<input name="setup_key" required></label><label>Tên đăng nhập<input name="username" required minlength="3" maxlength="40"></label><label>Mật khẩu quản trị<input type="password" name="password" required minlength="10"></label><button class="go" type="submit">Tạo tài khoản quản trị</button></form><?php endif; ?></section></main></body></html>
