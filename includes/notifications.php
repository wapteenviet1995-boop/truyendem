<?php
declare(strict_types=1);

/** Ghi thông báo an toàn; không để lỗi thông báo làm hỏng thao tác chính. */
function td_notify(PDO $pdo, int $recipientId, int $actorId, string $type, string $title, string $message, string $url): void {
    if ($recipientId < 1 || $recipientId === $actorId) return;
    try {
        $stmt = $pdo->prepare('INSERT INTO member_notifications(recipient_id,actor_id,notification_type,title,message,target_url) VALUES(?,?,?,?,?,?)');
        $stmt->execute([$recipientId, $actorId > 0 ? $actorId : null, $type, mb_substr($title, 0, 160), mb_substr($message, 0, 255), $url]);
    } catch (Throwable $e) {
        // Bảng thông báo chưa cài đặt: vẫn giữ thao tác gốc hoạt động.
    }
}

/* Thong bao cho thanh vien khi bai viet duoc duyet hoac can sua. */
function td_notify_member(PDO $pdo, int $memberId, string $type, string $title, string $message, string $url='/notifications.php'): void {
    if ($memberId < 1) return;
    try {
        $q=$pdo->prepare('INSERT INTO member_notifications(recipient_id,actor_id,notification_type,title,message,target_url) VALUES(?,NULL,?,?,?,?)');
        $q->execute([$memberId,$type,mb_substr($title,0,160,'UTF-8'),mb_substr($message,0,255,'UTF-8'),$url]);
    } catch (Throwable $e) {
        // Khong de loi hop thu thong bao lam hong thao tac kiem duyet.
    }
}
/* Gui thong bao den cac tai khoan quan tri/KDV dang hoat dong. */
function td_notify_admins(PDO $pdo, string $type, string $title, string $message, string $url='notifications.php'): void {
    try {
        $admins=$pdo->query('SELECT id FROM blog_admins WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN);
        if (!$admins) return;
        $q=$pdo->prepare('INSERT INTO admin_notifications(recipient_admin_id,notification_type,title,message,target_url) VALUES(?,?,?,?,?)');
        foreach($admins as $adminId) $q->execute([(int)$adminId,$type,mb_substr($title,0,160,'UTF-8'),mb_substr($message,0,255,'UTF-8'),$url]);
    } catch (Throwable $e) {
        // Bang thong bao CMS chua duoc cai dat: giu thao tac chinh.
    }
}
