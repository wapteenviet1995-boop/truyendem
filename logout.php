<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['member_id']) ||
    !hash_equals((string)($_SESSION['member_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
    http_response_code(403);
    exit('Yêu cầu không hợp lệ.');
}
unset($_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_csrf']);
session_regenerate_id(true);
header('Location: login.php?logged_out=1');
exit;
