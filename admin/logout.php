<?php
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { http_response_code(403); exit('Bạn chưa đăng nhập.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(400);
    exit('Yêu cầu đăng xuất không hợp lệ.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
