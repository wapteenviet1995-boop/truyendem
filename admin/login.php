<?php
require __DIR__ . '/../includes/db.php';
if (!empty($_SESSION['blog_admin_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q = $pdo->prepare('SELECT id,username,password_hash FROM blog_admins WHERE username=? LIMIT 1');
    $q->execute([trim((string)($_POST['username'] ?? ''))]);
    $admin = $q->fetch();
    if ($admin && password_verify((string)($_POST['password'] ?? ''), $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['blog_admin_id'] = (int)$admin['id'];
        $_SESSION['blog_admin_name'] = $admin['username'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: index.php'); exit;
    }
    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đăng nhập quản trị</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/admin-white.css?v=20261114-white1">
</head><body class="admin-theme"><main class="admin-wrap"><section class="admin-panel"><h1>Quản trị blog</h1><?php if($error): ?><p class="admin-error"><?= e($error) ?></p><?php endif; ?><form method="post"><label>Tên đăng nhập<input name="username" required autocomplete="username"></label><label>Mật khẩu<input type="password" name="password" required autocomplete="current-password"></label><button class="go" type="submit">Đăng nhập</button></form><p><a href="../blog.php">Về blog</a></p></section></main></body></html>
