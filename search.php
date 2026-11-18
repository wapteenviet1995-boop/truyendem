<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
$q = trim((string)($_GET['q'] ?? ''));
$stories = [];
$error = false;
function search_h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
try {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare("SELECT s.id,s.title,s.slug,s.author,s.description,s.cover_url,s.category,s.story_kind,s.views_count,s.likes_count,(SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id AND c.status='published') chapter_count FROM stories s WHERE s.status='published' AND (s.title LIKE ? OR s.author LIKE ? OR s.description LIKE ?) ORDER BY COALESCE((SELECT MAX(c.updated_at) FROM chapters c WHERE c.story_id=s.id AND c.status='published'),s.updated_at) DESC LIMIT 60");
        $stmt->execute([$like,$like,$like]);
        $stories = $stmt->fetchAll();
    }
} catch (PDOException $e) { $error = true; }
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#090a10"><title>Tìm kiếm truyện<?= $q !== '' ? ' - ' . search_h($q) : '' ?> | Truyện Đêm</title><meta name="description" content="Tìm truyện và tác giả yêu thích tại Truyện Đêm."><link rel="stylesheet" href="/css/style.css?v=20261111-routes1"><link rel="stylesheet" href="/css/stories.css?v=20261111-routes1"><link rel="stylesheet" href="/css/member-hub.css?v=20261112-leaderboard2"><link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1"><link rel="stylesheet" href="/css/site-matte-black.css?v=20261114-matte1"></head>
<body class="story-site">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="story-main">
<section class="story-section" id="search-results">
 <div class="story-section-head"><h1>⌕ Tìm kiếm truyện</h1></div>
 <?php if ($q === ''): ?>
  <div class="story-empty"><p>Nhập tên truyện hoặc tác giả vào ô tìm kiếm phía trên.</p></div>
 <?php elseif ($error): ?>
  <div class="story-empty"><p>Chưa thể thực hiện tìm kiếm. Vui lòng thử lại sau.</p></div>
 <?php else: ?>
  <div class="story-section-head"><h2>Kết quả cho “<?=search_h($q)?>”</h2><span><?=count($stories)?> truyện</span></div>
  <?php if (!$stories): ?><div class="story-empty"><h3>Chưa tìm thấy truyện phù hợp</h3><p>Thử từ khóa khác nhé.</p></div>
  <?php else: ?><div class="story-grid"><?php foreach ($stories as $s): $url=(($s['story_kind']??'serial')==='short'?'/truyen-ngan/':'/truyen/').rawurlencode((string)$s['slug']).'.html'; ?>
   <article class="story-card"><a class="story-card-cover" href="<?=$url?>"><?php if(!empty($s['cover_url'])): ?><img src="<?=search_h($s['cover_url'])?>" alt="<?=search_h($s['title'])?>" loading="lazy"><?php else: ?>☾<?php endif; ?></a><div class="story-card-body"><span class="story-tag"><?=search_h($s['category'])?></span><h3><a href="<?=$url?>"><?=search_h($s['title'])?></a></h3><p><?=search_h(mb_strimwidth((string)$s['description'],0,115,'…','UTF-8'))?></p><div class="story-card-meta"><?=search_h($s['author'])?> · <?=($s['story_kind']??'serial')==='short'?'Truyện ngắn':(int)$s['chapter_count'].' chương'?><br>◉ <?=number_format((int)$s['views_count'])?> · ♥ <?=number_format((int)$s['likes_count'])?></div></div></article>
  <?php endforeach; ?></div><?php endif; ?>
 <?php endif; ?>
</section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="/js/presence.js"></script></body></html>