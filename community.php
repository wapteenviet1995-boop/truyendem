<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

if (empty($_SESSION['member_csrf'])) $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
$currentId = (int)($_SESSION['member_id'] ?? 0);
$message = '';
$error = '';
$commentsEnabled = true;
try { $setting = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='comments_enabled' LIMIT 1")->fetchColumn(); if ($setting !== false) $commentsEnabled = ($setting === '1'); } catch (PDOException $ex) { $commentsEnabled = true; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentId < 1) {
        header('Location: login.php?next=community.php'); exit;
    }
    if (!hash_equals($_SESSION['member_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $error = 'Phiên đã hết hạn. Hãy tải lại trang rồi thử lại.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
        try {
            if ($action === 'create_post') {
                $content = trim((string)($_POST['content'] ?? ''));
                if ($content === '' || mb_strlen($content) > 2000) {
                    $error = 'Bài viết cần có nội dung từ 1 đến 2.000 ký tự.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO community_posts (member_id, content) VALUES (?, ?)');
                    $stmt->execute([$currentId, $content]);
                    header('Location: community.php?posted=1'); exit;
                }
            } elseif ($action === 'add_comment' && $postId && $postId > 0) {
                if (!$commentsEnabled) { $error = 'Bình luận hiện đang được quản trị viên tạm khóa.'; }
                else {
                $content = trim((string)($_POST['content'] ?? ''));
                if ($content === '' || mb_strlen($content) > 1000) {
                    $error = 'Bình luận cần có nội dung từ 1 đến 1.000 ký tự.';
                } else {
                    $check = $pdo->prepare('SELECT id FROM community_posts WHERE id=? LIMIT 1');
                    $check->execute([$postId]);
                    if (!$check->fetch()) $error = 'Bài viết không còn tồn tại.';
                    else {
                        $stmt = $pdo->prepare('INSERT INTO community_comments (post_id, member_id, content) VALUES (?, ?, ?)');
                        $stmt->execute([$postId, $currentId, $content]);
                        header('Location: community.php#post-' . $postId); exit;
                    }
                }
                }
            } elseif ($action === 'toggle_like' && $postId && $postId > 0) {
                $check = $pdo->prepare('SELECT id FROM community_posts WHERE id=? LIMIT 1');
                $check->execute([$postId]);
                if ($check->fetch()) {
                    $like = $pdo->prepare('SELECT id FROM community_likes WHERE post_id=? AND member_id=? LIMIT 1');
                    $like->execute([$postId, $currentId]);
                    if ($like->fetch()) {
                        $pdo->prepare('DELETE FROM community_likes WHERE post_id=? AND member_id=?')->execute([$postId, $currentId]);
                    } else {
                        $pdo->prepare('INSERT IGNORE INTO community_likes (post_id, member_id) VALUES (?, ?)')->execute([$postId, $currentId]);
                    }
                }
                header('Location: community.php#post-' . $postId); exit;
            } elseif ($action === 'toggle_follow') {
                $targetId = filter_var($_POST['member_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$targetId || $targetId < 1 || $targetId === $currentId) {
                    $error = 'Không thể theo dõi tài khoản này.';
                } else {
                    $check = $pdo->prepare('SELECT id FROM members WHERE id=? AND is_active=1 LIMIT 1');
                    $check->execute([$targetId]);
                    if (!$check->fetch()) $error = 'Thành viên không tồn tại.';
                    else {
                        $follow = $pdo->prepare('SELECT id FROM member_follows WHERE follower_id=? AND following_id=? LIMIT 1');
                        $follow->execute([$currentId, $targetId]);
                        if ($follow->fetch()) {
                            $pdo->prepare('DELETE FROM member_follows WHERE follower_id=? AND following_id=?')->execute([$currentId, $targetId]);
                        } else {
                            $pdo->prepare('INSERT IGNORE INTO member_follows (follower_id, following_id) VALUES (?, ?)')->execute([$currentId, $targetId]);
                        }
                    }
                    header('Location: community.php'); exit;
                }
            } else {
                $error = 'Thao tác không hợp lệ.';
            }
        } catch (PDOException $ex) {
            $error = 'Chưa cài đặt đầy đủ dữ liệu cộng đồng. Hãy chạy file database/community.sql trong phpMyAdmin.';
        }
    }
}

if (isset($_GET['posted'])) $message = 'Bài viết của bạn đã được đăng.';
$setupNeeded = false;
$posts = [];
$stats = ['members'=>0, 'posts'=>0, 'follows'=>0];
$commentStmt = null;
try {
    $posts = $pdo->query(
        'SELECT p.id,p.member_id,p.content,p.created_at,m.display_name,
          (SELECT COUNT(*) FROM community_likes l WHERE l.post_id=p.id) AS like_count,
          (SELECT COUNT(*) FROM community_comments c WHERE c.post_id=p.id) AS comment_count' .
        ($currentId ? ', (SELECT COUNT(*) FROM community_likes mine WHERE mine.post_id=p.id AND mine.member_id=' . $currentId . ') AS liked_by_me,
          (SELECT COUNT(*) FROM member_follows f WHERE f.follower_id=' . $currentId . ' AND f.following_id=p.member_id) AS following_author' : ', 0 AS liked_by_me, 0 AS following_author') .
        ' FROM community_posts p INNER JOIN members m ON m.id=p.member_id WHERE m.is_active=1 ORDER BY p.created_at DESC,p.id DESC LIMIT 40'
    )->fetchAll();
    $commentStmt = $pdo->prepare('SELECT c.id,c.content,c.created_at,m.display_name FROM community_comments c INNER JOIN members m ON m.id=c.member_id WHERE c.post_id=? AND m.is_active=1 ORDER BY c.created_at DESC,c.id DESC LIMIT 3');
    $stats = $pdo->query('SELECT (SELECT COUNT(*) FROM members WHERE is_active=1) AS members, (SELECT COUNT(*) FROM community_posts) AS posts, (SELECT COUNT(*) FROM member_follows) AS follows')->fetch();
} catch (PDOException $ex) {
    $setupNeeded = true;
}
function community_h(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cộng đồng | Góc Hoàng Đạo</title><link rel="stylesheet" href="css/style.css?v=socialdiary1"></head>
<body class="blog-page community-page">
<header class="site-header"><div class="header-inner"><a class="brand" href="index.php"><span class="brand-mark">✦</span><span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span></a><nav class="header-links"><a href="index.php">Trang chủ</a><a href="compatibility.php">Tương hợp</a><a href="friends.php">Kết bạn</a><a href="diary.php">Nhật ký</a><a href="messages.php">Tin nhắn</a><a href="blog.php">Blog</a><?php if ($currentId): ?><a href="account.php">Tài khoản</a><form class="member-nav-logout" method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= community_h($_SESSION['member_csrf']) ?>"><button type="submit">Đăng xuất</button></form><?php else: ?><a href="login.php">Đăng nhập</a><a href="register.php">Đăng ký</a><?php endif; ?></nav></div></header>
<main class="community-main">
 <section class="community-hero"><span class="compat-kicker">✦ KẾT NỐI NHỮNG NGƯỜI YÊU TRUYỆN ✦</span><h1>Cộng đồng <em>Truyện Đêm</em></h1><p>Nơi độc giả giao lưu, chia sẻ cảm nhận và giới thiệu những câu chuyện yêu thích.</p><div class="community-stats"><span><b><?= (int)$stats['members'] ?></b> thành viên</span><span><b><?= (int)$stats['posts'] ?></b> bài viết</span><span><b><?= (int)$stats['follows'] ?></b> lượt theo dõi</span></div></section>
 <?php if ($setupNeeded): ?><p class="community-error" role="alert">Cộng đồng chưa được cài đặt cơ sở dữ liệu. Hãy chạy file <strong>database/community.sql</strong> trong phpMyAdmin trước khi sử dụng.</p><?php endif; ?>
 <?php if ($message): ?><p class="community-flash"><?= community_h($message) ?></p><?php endif; ?>
 <?php if ($error): ?><p class="community-error" role="alert"><?= community_h($error) ?></p><?php endif; ?>
 <?php if ($currentId): ?><section class="community-compose"><h2>Chia sẻ điều bạn đang nghĩ</h2><form method="post"><input type="hidden" name="csrf" value="<?= community_h($_SESSION['member_csrf']) ?>"><input type="hidden" name="action" value="create_post"><textarea name="content" maxlength="2000" rows="4" placeholder="Bạn đang đọc câu chuyện nào?" required></textarea><div class="community-compose-foot"><small>Tối đa 2.000 ký tự</small><button type="submit">Đăng bài ✦</button></div></form></section><?php else: ?><section class="community-join"><h2>Cùng tham gia cuộc trò chuyện</h2><p>Đăng nhập để đăng bài, bình luận, thả tim và theo dõi thành viên khác.</p><a href="login.php">Đăng nhập</a><a href="register.php">Tạo tài khoản</a></section><?php endif; ?>
 <section class="community-feed"><div class="community-feed-head"><h2>Bài viết mới nhất</h2><span><?= count($posts) ?> bài gần đây</span></div>
 <?php if (!$posts): ?><div class="community-empty"><span>☾</span><h3>Chưa có lời nhắn nào</h3><p>Hãy là người đầu tiên chia sẻ cùng cộng đồng.</p></div><?php endif; ?>
 <?php foreach ($posts as $post): ?><article class="community-post" id="post-<?= (int)$post['id'] ?>"><div class="community-post-head"><div class="community-avatar">✦</div><div class="community-author"><strong><?= community_h($post['display_name']) ?></strong><small><?= community_h(date('d/m/Y · H:i', strtotime($post['created_at']))) ?></small></div>
 <?php if ($currentId && $currentId !== (int)$post['member_id']): ?><form method="post" class="community-follow"><input type="hidden" name="csrf" value="<?= community_h($_SESSION['member_csrf']) ?>"><input type="hidden" name="action" value="toggle_follow"><input type="hidden" name="member_id" value="<?= (int)$post['member_id'] ?>"><button type="submit"><?= (int)$post['following_author'] ? 'Đang theo dõi' : '+ Theo dõi' ?></button></form><?php endif; ?></div>
 <div class="community-post-content"><?= nl2br(community_h($post['content'])) ?></div>
 <div class="community-post-actions"><?php if ($currentId): ?><form method="post"><input type="hidden" name="csrf" value="<?= community_h($_SESSION['member_csrf']) ?>"><input type="hidden" name="action" value="toggle_like"><input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>"><button class="<?= (int)$post['liked_by_me'] ? 'is-liked' : '' ?>" type="submit"><?= (int)$post['liked_by_me'] ? '♥ Đã thích' : '♡ Thích' ?> · <?= (int)$post['like_count'] ?></button></form><?php else: ?><span>♡ <?= (int)$post['like_count'] ?> lượt thích</span><?php endif; ?><span>☾ <?= (int)$post['comment_count'] ?> bình luận</span></div>
 <?php $comments=[]; if ($commentStmt) { $commentStmt->execute([(int)$post['id']]); $comments=$commentStmt->fetchAll(); } ?>
 <?php if ($comments): ?><div class="community-comments"><?php foreach (array_reverse($comments) as $comment): ?><p><strong><?= community_h($comment['display_name']) ?></strong> <?= community_h($comment['content']) ?></p><?php endforeach; ?></div><?php endif; ?>
 <?php if (!$commentsEnabled): ?><p class="community-login-hint">Bình luận đang tạm thời được tắt.</p><?php elseif ($currentId): ?><form method="post" class="community-comment-form"><input type="hidden" name="csrf" value="<?= community_h($_SESSION['member_csrf']) ?>"><input type="hidden" name="action" value="add_comment"><input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>"><input type="text" name="content" maxlength="1000" placeholder="Viết bình luận..." required><button type="submit">Gửi</button></form><?php else: ?><p class="community-login-hint"><a href="login.php">Đăng nhập</a> để bình luận.</p><?php endif; ?>
 </article><?php endforeach; ?></section>
</main><footer class="site-footer"><div class="footer-bottom">© 2026 Truyện Đêm · Cùng nhau kết nối</div></footer>
<script src="js/presence.js"></script>
</body></html>
