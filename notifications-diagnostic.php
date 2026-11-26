<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(200);
        echo "\nFATAL: " . $e['message'] . "\nFILE: " . basename($e['file']) . "\nLINE: " . $e['line'] . "\n";
    }
});

echo "STEP 1: PHP is running\n";
try {
    require __DIR__ . '/includes/db.php';
    echo "STEP 2: database connection OK\n";
    if (empty($_SESSION['member_id'])) {
        echo "STEP 3: no member session; please log in and open this URL again\n";
        exit;
    }
    echo "STEP 3: member session OK (ID hidden)\n";
    $q = $pdo->query("SHOW COLUMNS FROM member_notifications");
    $cols = $q->fetchAll(PDO::FETCH_COLUMN);
    echo "STEP 4: member_notifications columns: " . implode(', ', $cols) . "\n";
    $q = $pdo->prepare("SELECT id,notification_type,title,message,target_url,is_read,created_at FROM member_notifications WHERE recipient_id=? ORDER BY created_at DESC,id DESC LIMIT 3");
    $q->execute([(int)$_SESSION['member_id']]);
    echo "STEP 5: notification query OK; rows=" . $q->rowCount() . "\n";
    echo "DIAGNOSIS: core PHP, DB, session and notification query all passed.\n";
} catch (Throwable $e) {
    http_response_code(200);
    echo "CAUGHT ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . basename($e->getFile()) . "\n";
    echo "LINE: " . $e->getLine() . "\n";
}
