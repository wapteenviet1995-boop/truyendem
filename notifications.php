<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
if (empty($_SESSION['member_id'])) { header('Location: /login.php?next=notifications.php'); exit; }
$me = (int)$_SESSION['member_id'];
if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
$csrf = (string)$_SESSION['member_csrf'];
$error = '';

function noti_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function noti_lower(string $value): string {
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}
function noti_category(string $type): string {
    if (str_starts_with($type, 'contributor_') || str_contains($type, 'reward') || str_contains($type, 'paid')) return 'rewards';
    if (str_starts_with($type, 'moderation_') || str_starts_with($type, 'author_') || str_starts_with($type, 'story_')) return 'stories';
    if (str_starts_with($type, 'community_') || $type === 'new_follower' || str_starts_with($type, 'friend_') || str_starts_with($type, 'message_')) return 'social';
    return 'system';
}
function noti_icon(string $category): string {
    return ['social'=>'♡','stories'=>'▤','rewards'=>'✧','system'=>'☾'][$category] ?? '☾';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $error = 'Phiên đã hết hạn. Hãy tải lại trang.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $id = (int)($_POST['notification_id'] ?? 0);
        try {
            if ($action === 'read_all') {
                $pdo->prepare('UPDATE member_notifications SET is_read=1 WHERE recipient_id=? AND is_read=0')->execute([$me]);
            } elseif ($action === 'read_one' && $id > 0) {
                $pdo->prepare('UPDATE member_notifications SET is_read=1 WHERE id=? AND recipient_id=?')->execute([$id, $me]);
            } elseif ($action === 'open' && $id > 0) {
                $q = $pdo->prepare('SELECT target_url FROM member_notifications WHERE id=? AND recipient_id=? LIMIT 1');
                $q->execute([$id, $me]);
                $target = (string)($q->fetchColumn() ?: '/notifications.php');
                $pdo->prepare('UPDATE member_notifications SET is_read=1 WHERE id=? AND recipient_id=?')->execute([$id, $me]);
                if ($target === '' || $target[0] !== '/' || str_starts_with($target, '//')) $target = '/notifications.php';
                header('Location: ' . $target); exit;
            } elseif ($action === 'delete_one' && $id > 0) {
                $pdo->prepare('DELETE FROM member_notifications WHERE id=? AND recipient_id=?')->execute([$id, $me]);
            } elseif ($action === 'clear_read') {
                $pdo->prepare('DELETE FROM member_notifications WHERE recipient_id=? AND is_read=1')->execute([$me]);
            }
            $keep = [];
            foreach (['filter','q'] as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                if ($value !== '') $keep[$key] = $value;
            }
            $redirect = '/notifications.php' . ($keep ? '?' . http_build_query($keep) : '');
            header('Location: ' . $redirect); exit;
        } catch (PDOException $e) {
            $error = 'Không thể cập nhật thông báo. Vui lòng thử lại.';
        }
    }
}

$filter = (string)($_GET['filter'] ?? 'all');
if (!in_array($filter, ['all','unread','social','stories','rewards','system'], true)) $filter = 'all';
$search = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
try {
    $q = $pdo->prepare("SELECT n.id,n.notification_type,n.title,n.message,n.target_url,n.is_read,n.created_at,m.display_name actor_name FROM member_notifications n LEFT JOIN members m ON m.id=n.actor_id WHERE n.recipient_id=? ORDER BY n.created_at DESC,n.id DESC LIMIT 200");
    $q->execute([$me]);
    $allItems = $q->fetchAll(PDO::FETCH_ASSOC);
    $q = $pdo->prepare('SELECT COUNT(*) FROM member_notifications WHERE recipient_id=? AND is_read=0');
    $q->execute([$me]);
    $unread = (int)$q->fetchColumn();
} catch (PDOException $e) {
    http_response_code(500);
    exit('Chưa cài đặt bảng thông báo. Hãy chạy database/member_hub.sql trong phpMyAdmin.');
}

$counts = ['all'=>count($allItems),'unread'=>0,'social'=>0,'stories'=>0,'rewards'=>0,'system'=>0];
$filtered = [];
foreach ($allItems as $item) {
    $category = noti_category((string)$item['notification_type']);
    if (!(int)$item['is_read']) $counts['unread']++;
    $counts[$category]++;
    $item['_category'] = $category;
    $haystack = $search !== '' ? noti_lower((string)$item['title'].' '.(string)$item['message'].' '.(string)($item['actor_name'] ?? '')) : '';
    if ($filter === 'unread' && (int)$item['is_read']) continue;
    if (in_array($filter, ['social','stories','rewards','system'], true) && $category !== $filter) continue;
    if ($search !== '' && strpos($haystack, noti_lower($search)) === false) continue;
    $filtered[] = $item;
}
$perPage = 20;
$totalPages = max(1, (int)ceil(count($filtered) / $perPage));
$page = min($page, $totalPages);
$items = array_slice($filtered, ($page - 1) * $perPage, $perPage);
function noti_page_url(int $page, string $filter, string $search): string {
    $params = ['page'=>$page];
    if ($filter !== 'all') $params['filter'] = $filter;
    if ($search !== '') $params['q'] = $search;
    return '/notifications.php?' . http_build_query($params);
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Thông báo | Truyện Đêm</title>
<link rel="stylesheet" href="/css/style.css?v=memberhub1">
<link rel="stylesheet" href="/css/member-hub.css?v=20261122-light1">
<link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1">
<link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1">
<link rel="stylesheet" href="/css/notifications.css?v=20261126-upgrade1">
</head>
<body class="story-site">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="member-hub-main noti-main">
<section class="member-hub-hero noti-hero">
<span class="kicker">✦ TRUNG TÂM HOẠT ĐỘNG ✦</span>
<h1>Thông báo <small><?=number_format($unread)?> chưa đọc</small></h1>
<p>Mọi tương tác, cập nhật truyện và khoản xu/nhuận bút đều được gom tại đây.</p>
<div class="noti-summary"><span><b><?=number_format($counts['all'])?></b> thông báo gần đây</span><span><b><?=number_format($unread)?></b> chưa đọc</span></div>
</section>
<nav class="member-hub-tabs noti-shortcuts">
<a href="/library.php">▣ Tủ sách</a><a href="/notifications.php" aria-current="page">♧ Thông báo</a><a href="/moderation-history.php">✓ Lịch sử kiểm duyệt</a><a href="/submit_story.php#my-stories">▤ Bài viết của tôi</a><a href="/shop.php">✧ Điểm danh & Xu</a><a href="/account.php">Hồ sơ</a>
</nav>
<?php if ($error): ?><p class="member-hub-flash"><?=noti_h($error)?></p><?php endif; ?>
<section class="noti-toolbar">
<form class="noti-search" method="get" action="/notifications.php">
<input type="hidden" name="filter" value="<?=noti_h($filter)?>">
<input type="search" name="q" value="<?=noti_h($search)?>" placeholder="Tìm thông báo..." aria-label="Tìm thông báo">
<button type="submit">Tìm</button>
<?php if ($search !== ''): ?><a href="<?=noti_h('/notifications.php'.($filter!=='all'?'?filter='.$filter:''))?>">Xóa lọc</a><?php endif; ?>
</form>
<div class="noti-actions">
<?php if ($unread > 0): ?><form method="post"><input type="hidden" name="csrf" value="<?=noti_h($csrf)?>"><input type="hidden" name="action" value="read_all"><input type="hidden" name="filter" value="<?=noti_h($filter)?>"><input type="hidden" name="q" value="<?=noti_h($search)?>"><button type="submit" class="noti-action-primary">✓ Đọc tất cả</button></form><?php endif; ?>
<?php if ($counts['all'] > $counts['unread']): ?><form method="post" onsubmit="return confirm('Xóa toàn bộ thông báo đã đọc?')"><input type="hidden" name="csrf" value="<?=noti_h($csrf)?>"><input type="hidden" name="action" value="clear_read"><input type="hidden" name="filter" value="<?=noti_h($filter)?>"><button type="submit" class="noti-action-muted">Dọn thông báo đã đọc</button></form><?php endif; ?>
</div>
</section>
<nav class="noti-filters" aria-label="Lọc thông báo">
<?php $labels=['all'=>'Tất cả','unread'=>'Chưa đọc','social'=>'Cộng đồng','stories'=>'Truyện & kiểm duyệt','rewards'=>'Xu & nhuận bút','system'=>'Khác']; foreach($labels as $key=>$label): ?>
<a class="<?=($filter===$key?'active':'')?>" href="<?=noti_h('/notifications.php'.($key!=='all'?'?filter='.$key:''))?>"><?=noti_h($label)?> <b><?=number_format($counts[$key])?></b></a>
<?php endforeach; ?>
</nav>
<section class="noti-feed">
<div class="noti-feed-head"><h2><?=noti_h($labels[$filter])?></h2><span><?=number_format(count($filtered))?> kết quả · Trang <?=$page?>/<?=$totalPages?></span></div>
<?php if (!$items): ?>
<div class="member-hub-empty noti-empty"><span>☾</span><strong><?=($search!==''?'Không tìm thấy thông báo phù hợp':'Bạn chưa có thông báo ở mục này')?></strong><p>Khi có hoạt động mới, thông báo sẽ xuất hiện tại đây.</p></div>
<?php else: ?>
<div class="noti-list">
<?php foreach ($items as $n): $cat=$n['_category']; $isUnread=!(int)$n['is_read']; ?>
<article class="noti-card <?=($isUnread?'is-unread':'is-read')?>">
<div class="noti-icon noti-<?=noti_h($cat)?>"><?=noti_icon($cat)?></div>
<div class="noti-content">
<div class="noti-card-top"><span class="noti-category"><?=noti_h($labels[$cat] ?? 'Thông báo')}</span><time datetime="<?=noti_h(date('c',strtotime($n['created_at'])))?>"><?=noti_h(date('d/m/Y · H:i',strtotime($n['created_at'])))?></time></div>
<h3><?=noti_h($n['title'])?></h3><p><?=noti_h($n['message'])?></p>
<?php if (!empty($n['actor_name'])): ?><small class="noti-actor">Thành viên: <?=noti_h($n['actor_name'])?></small><?php endif; ?>
<div class="noti-card-actions">
<form method="post"><input type="hidden" name="csrf" value="<?=noti_h($csrf)?>"><input type="hidden" name="action" value="open"><input type="hidden" name="notification_id" value="<?=(int)$n['id']?>"><button type="submit" class="noti-open">Mở nội dung →</button></form>
<?php if ($isUnread): ?><form method="post"><input type="hidden" name="csrf" value="<?=noti_h($csrf)?>"><input type="hidden" name="action" value="read_one"><input type="hidden" name="notification_id" value="<?=(int)$n['id']?>"><input type="hidden" name="filter" value="<?=noti_h($filter)?>"><button type="submit" class="noti-secondary">Đánh dấu đã đọc</button></form><?php endif; ?>
<form method="post" onsubmit="return confirm('Xóa thông báo này?')"><input type="hidden" name="csrf" value="<?=noti_h($csrf)?>"><input type="hidden" name="action" value="delete_one"><input type="hidden" name="notification_id" value="<?=(int)$n['id']?>"><input type="hidden" name="filter" value="<?=noti_h($filter)?>"><button type="submit" class="noti-delete" aria-label="Xóa thông báo">Xóa</button></form>
</div>
</div>
<?php if ($isUnread): ?><span class="noti-unread-dot" aria-label="Chưa đọc"></span><?php endif; ?>
</article>
<?php endforeach; ?>
</div>
<?php if ($totalPages > 1): ?><nav class="noti-pagination" aria-label="Phân trang"><?php if($page>1):?><a href="<?=noti_h(noti_page_url($page-1,$filter,$search))?>">← Trước</a><?php endif; ?><span><?=$page?> / <?=$totalPages?></span><?php if($page<$totalPages):?><a href="<?=noti_h(noti_page_url($page+1,$filter,$search))?>">Sau →</a><?php endif; ?></nav><?php endif; ?>
<?php endif; ?>
</section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body></html>