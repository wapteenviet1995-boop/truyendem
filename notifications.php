<?php
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['member_id'])) {
    header('Location: /login.php?next=notifications.php');
    exit;
}

$memberId = (int) $_SESSION['member_id'];
$message = '';
$notifications = [];
$unreadCount = 0;

if (empty($_SESSION['member_csrf'])) {
    $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['member_csrf'];

/* Xu ly thao tac doc/xoa thong bao */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    if (!hash_equals($csrf, $postedToken)) {
        $message = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
    } else {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
        $noticeId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        try {
            if ($action === 'read_all') {
                $stmt = $pdo->prepare('UPDATE member_notifications SET is_read=1 WHERE recipient_id=?');
                $stmt->execute([$memberId]);
                header('Location: /notifications.php?done=read');
                exit;
            } elseif ($action === 'read_one' && $noticeId > 0) {
                $stmt = $pdo->prepare('UPDATE member_notifications SET is_read=1 WHERE id=? AND recipient_id=?');
                $stmt->execute([$noticeId, $memberId]);
                header('Location: /notifications.php');
                exit;
            } elseif ($action === 'delete' && $noticeId > 0) {
                $stmt = $pdo->prepare('DELETE FROM member_notifications WHERE id=? AND recipient_id=?');
                $stmt->execute([$noticeId, $memberId]);
                header('Location: /notifications.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('notifications.php POST: ' . $e->getMessage());
            $message = 'Không thể thực hiện thao tác. Vui lòng thử lại.';
        }
    }
}

if (isset($_GET['done']) && $_GET['done'] === 'read') {
    $message = 'Đã đánh dấu tất cả thông báo là đã đọc.';
}

/* Lay danh sach thong bao */
try {
    $stmt = $pdo->prepare('SELECT id, notification_type, title, message, target_url, is_read, created_at FROM member_notifications WHERE recipient_id=? ORDER BY created_at DESC, id DESC LIMIT 100');
    $stmt->execute([$memberId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM member_notifications WHERE recipient_id=? AND is_read=0');
    $stmt->execute([$memberId]);
    $unreadCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('notifications.php SELECT: ' . $e->getMessage());
    $message = 'Chưa thể tải thông báo. Vui lòng thử lại sau.';
}

function td_notification_escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function td_notification_type_label($type) {
    $labels = [
        'new_follower' => 'Người theo dõi mới',
        'friend_request' => 'Lời mời kết bạn',
        'friend_accepted' => 'Đã chấp nhận lời mời kết bạn',
        'message_new' => 'Tin nhắn mới',
        'community_comment' => 'Bình luận mới trong cộng đồng',
        'community_like' => 'Bài viết được yêu thích',
        'community_reply' => 'Có người trả lời bài viết',
        'story_comment' => 'Bình luận mới về truyện',
        'story_comment_like' => 'Bình luận được yêu thích',
        'story_comment_reply' => 'Có người trả lời bình luận',
        'story_like' => 'Truyện được yêu thích',
        'author_new_story' => 'Tác giả bạn theo dõi vừa đăng truyện',
        'moderation_submitted' => 'Truyện đã gửi kiểm duyệt',
        'moderation_approved' => 'Truyện đã được duyệt',
        'moderation_rejected' => 'Truyện cần chỉnh sửa',
        'contributor_reward' => 'Bạn có nhuận bút mới',
        'contributor_paid' => 'Nhuận bút đã được thanh toán'
    ];

    $type = (string) $type;
    if (isset($labels[$type])) {
        return $labels[$type];
    }
    if (strpos($type, 'moderation_') === 0 || strpos($type, 'author_') === 0) {
        return 'Thông báo về truyện và kiểm duyệt';
    }
    if (strpos($type, 'contributor_') === 0 || strpos($type, 'reward') !== false || strpos($type, 'paid') !== false) {
        return 'Thông báo nhuận bút và phần thưởng';
    }
    if (strpos($type, 'community_') === 0 || strpos($type, 'friend_') === 0 || strpos($type, 'message_') === 0 || strpos($type, 'new_follower') === 0) {
        return 'Hoạt động cộng đồng';
    }
    if (strpos($type, 'story_') === 0) {
        return 'Hoạt động liên quan đến truyện';
    }
    if ($type === '') {
        return 'Thông báo Truyện Đêm';
    }
    return 'Thông báo khác';
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Thông báo | Truyện Đêm</title>
<link rel="stylesheet" href="/css/style.css">
<link rel="stylesheet" href="/css/member-hub.css">
<link rel="stylesheet" href="/css/notifications.css">
<style>
.np-wrap{max-width:900px;margin:28px auto 60px;padding:0 16px;color:var(--text,#eee)}
.np-head{padding:24px;border-radius:18px;background:var(--card,#191923);margin-bottom:18px}
.np-head h1{margin:0 0 8px;font-size:26px}.np-head p{margin:0;opacity:.75}
.np-tools{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:16px 0}
.np-btn{border:0;border-radius:10px;padding:10px 14px;background:#8b65b5;color:#fff;font-weight:600;cursor:pointer}
.np-card{display:flex;gap:14px;align-items:flex-start;padding:17px;margin:12px 0;border:1px solid var(--line,#333);border-radius:15px;background:var(--card,#191923)}
.np-card.unread{border-color:#9870c5}.np-symbol{width:40px;height:40px;flex:0 0 40px;border-radius:50%;display:grid;place-items:center;background:rgba(151,111,193,.18);font-size:20px}
.np-body{min-width:0;flex:1}.np-body h3{font-size:16px;margin:0 0 7px}.np-body p{margin:0 0 9px;line-height:1.55;overflow-wrap:anywhere}
.np-meta{font-size:12px;opacity:.65;margin-bottom:8px}.np-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.np-actions button{background:none;border:0;color:#bd9ae4;padding:4px 0;cursor:pointer}.np-actions a{color:#bd9ae4;text-decoration:none}
.np-empty,.np-message{padding:22px;border-radius:14px;background:var(--card,#191923);margin:14px 0}
.np-message{border-left:3px solid #9870c5}
@media(max-width:560px){.np-head{padding:19px}.np-head h1{font-size:23px}.np-card{padding:13px;gap:10px}.np-symbol{width:34px;height:34px;flex-basis:34px}}

/* Sua cac khoi con nen toi khi bat che do sang */
html[data-theme="light"] body.story-site .np-wrap{color:#30263a!important}
html[data-theme="light"] body.story-site .np-head,
html[data-theme="light"] body.story-site .np-empty,
html[data-theme="light"] body.story-site .np-message{
  background:#fff!important;background-image:none!important;
  color:#30263a!important;border:1px solid #e5dfeb!important;
  box-shadow:0 5px 18px rgba(42,27,54,.045)!important;
}
html[data-theme="light"] body.story-site .np-head h1,
html[data-theme="light"] body.story-site .np-tools>strong,
html[data-theme="light"] body.story-site .np-body h3{color:#30263a!important}
html[data-theme="light"] body.story-site .np-head p,
html[data-theme="light"] body.story-site .np-meta,
html[data-theme="light"] body.story-site .np-body p{color:#62596c!important}
html[data-theme="light"] body.story-site .np-symbol{background:#f2eaf8!important;color:#7953a3!important}
html[data-theme="light"] body.story-site .np-card{background:#fff!important;color:#30263a!important;border-color:#e5dfeb!important}
html[data-theme="light"] body.story-site .np-card.unread{background:#fbf7ff!important;border-color:#d8c5e8!important}
html[data-theme="light"] body.story-site .np-actions button,
html[data-theme="light"] body.story-site .np-actions a{color:#7045a0!important}
html[data-theme="light"] body.story-site .np-btn{background:#7045a0!important;color:#fff!important}

</style>
</head>
<body class="story-site">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="np-wrap">
    <section class="np-head">
        <h1>Thông báo</h1>
        <p>Bạn có <strong><?php echo number_format($unreadCount); ?></strong> thông báo chưa đọc.</p>
    </section>

    <?php if ($message !== ''): ?>
        <div class="np-message"><?php echo td_notification_escape($message); ?></div>
    <?php endif; ?>

    <div class="np-tools">
        <strong>Hoạt động gần đây</strong>
        <?php if ($unreadCount > 0): ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?php echo td_notification_escape($csrf); ?>">
            <input type="hidden" name="action" value="read_all">
            <button class="np-btn" type="submit">Đánh dấu tất cả đã đọc</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (!$notifications): ?>
        <div class="np-empty">Bạn chưa có thông báo nào.</div>
    <?php else: ?>
        <?php foreach ($notifications as $notice): ?>
            <article class="np-card <?php echo ((int)$notice['is_read'] === 0) ? 'unread' : ''; ?>">
                <div class="np-symbol">☾</div>
                <div class="np-body">
                    <div class="np-meta">
                        <?php echo td_notification_escape(td_notification_type_label($notice['notification_type'])); ?>
                        · <?php echo td_notification_escape($notice['created_at']); ?>
                        <?php if ((int)$notice['is_read'] === 0): ?> · Chưa đọc<?php endif; ?>
                    </div>
                    <h3><?php echo td_notification_escape($notice['title']); ?></h3>
                    <p><?php echo nl2br(td_notification_escape($notice['message'])); ?></p>
                    <div class="np-actions">
                        <?php if (!empty($notice['target_url']) && strpos((string)$notice['target_url'], '/') === 0 && strpos((string)$notice['target_url'], '//') !== 0): ?>
                            <a href="<?php echo td_notification_escape($notice['target_url']); ?>">Mở nội dung →</a>
                        <?php endif; ?>
                        <?php if ((int)$notice['is_read'] === 0): ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?php echo td_notification_escape($csrf); ?>">
                            <input type="hidden" name="action" value="read_one">
                            <input type="hidden" name="id" value="<?php echo (int)$notice['id']; ?>">
                            <button type="submit">Đánh dấu đã đọc</button>
                        </form>
                        <?php endif; ?>
                        <form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa thông báo này?');">
                            <input type="hidden" name="csrf" value="<?php echo td_notification_escape($csrf); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$notice['id']; ?>">
                            <button type="submit">Xóa</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
