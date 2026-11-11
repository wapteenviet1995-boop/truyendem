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
