<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Chưa cấu hình database. Hãy tạo includes/config.php theo includes/config.example.php.');
}
$config = require $configFile;

try {
    $pdo = new PDO(
        'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không kết nối được database. Hãy kiểm tra cấu hình MySQL trong includes/config.php.');
}

/* Buoc tai khoan bi khoa ra khoi phien dang nhap dang ton tai. */
if (!empty($_SESSION['member_id'])) {
    $checkMember = $pdo->prepare('SELECT is_active FROM members WHERE id=? LIMIT 1');
    $checkMember->execute([(int)$_SESSION['member_id']]);
    if ((int)$checkMember->fetchColumn() !== 1) {
        unset($_SESSION['member_id'], $_SESSION['member_name']);
        session_regenerate_id(true);
        header('Location: ' . (str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/') ? '../login.php?locked=1' : 'login.php?locked=1'));
        exit;
    }
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
