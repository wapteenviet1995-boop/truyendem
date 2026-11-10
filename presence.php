<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['member_id'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false]);
    exit;
}
try {
    $q = $pdo->prepare('UPDATE members SET last_seen_at=NOW() WHERE id=? AND is_active=1');
    $q->execute([(int)$_SESSION['member_id']]);
    echo json_encode(['ok'=>true,'time'=>date('c')]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
