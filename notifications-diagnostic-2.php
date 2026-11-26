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
try {
    require __DIR__ . '/includes/db.php';
    if (empty($_SESSION['member_id'])) { echo "Please log in first."; exit; }
    $me = (int)$_SESSION['member_id'];
    if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
    $csrf = (string)$_SESSION['member_csrf'];
    echo "STEP 1: database and session OK\n";
    require __DIR__ . '/includes/header.php';
    echo "\nSTEP 2: header include OK\n";
    $q = $pdo->prepare("SELECT id,notification_type,title,message,target_url,is_read,created_at FROM member_notifications WHERE recipient_id=? ORDER BY created_at DESC,id DESC LIMIT 200");
    $q->execute([$me]);
    $allItems = $q->fetchAll(PDO::FETCH_ASSOC);
    $counts = ['all'=>count($allItems),'unread'=>0,'social'=>0,'stories'=>0,'rewards'=>0,'system'=>0];
    foreach ($allItems as $item) {
        $type = (string)$item['notification_type'];
        $category = 'system';
        if (strpos($type, 'contributor_') === 0 || strpos($type, 'reward') !== false || strpos($type, 'paid') !== false) $category = 'rewards';
        elseif (strpos($type, 'moderation_') === 0 || strpos($type, 'author_') === 0 || strpos($type, 'story_') === 0) $category = 'stories';
        elseif (strpos($type, 'community_') === 0 || $type === 'new_follower' || strpos($type, 'friend_') === 0 || strpos($type, 'message_') === 0) $category = 'social';
        if (!(int)$item['is_read']) $counts['unread']++;
        $counts[$category]++;
        htmlspecialchars((string)$item['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        htmlspecialchars((string)$item['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        date('c', strtotime((string)$item['created_at']));
    }
    echo "STEP 3: notification rendering data OK; rows=" . count($allItems) . "\n";
    require __DIR__ . '/includes/footer.php';
    echo "\nSTEP 4: footer include OK\n";
    echo "DIAGNOSIS: header, data preparation, formatting and footer all passed.\n";
} catch (Throwable $e) {
    http_response_code(200);
    echo "\nCAUGHT ERROR: " . $e->getMessage() . "\nFILE: " . basename($e->getFile()) . "\nLINE: " . $e->getLine() . "\n";
}
