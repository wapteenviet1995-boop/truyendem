<?php
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { http_response_code(403); exit('Bạn chưa đăng nhập.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Yêu cầu không hợp lệ.'); }
$q=$pdo->prepare('DELETE FROM posts WHERE id=?'); $q->execute([(int)($_POST['id']??0)]);
header('Location: index.php'); exit;
