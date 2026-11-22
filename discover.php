<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';

function discover_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function discover_slug_url(array $story): string {
    if (in_array((string)($story['story_type'] ?? 'text'), ['audio', 'both'], true)) {
        $base = '/audio/';
    } else {
        $base = (($story['story_kind'] ?? 'serial') === 'short') ? '/truyen-ngan/' : '/truyen/';
    }
    return $base . rawurlencode((string)$story['slug']) . '.html';
}
function discover_query_url(array $changes = []): string {
    $params = $_GET;
    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') unset($params[$key]);
        else $params[$key] = $value;
    }
    return '/discover.php' . ($params ? '?' . http_build_query($params) : '');
}

$q = trim((string)($_GET['q'] ?? ''));
$kind = (string)($_GET['kind'] ?? 'all');
$category = trim((string)($_GET['category'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$allowedKinds = ['all', 'short', 'serial', 'audio'];
if (!in_array($kind, $allowedKinds, true)) $kind = 'all';
$q = mb_substr($q, 0, 100, 'UTF-8');

$where = ["s.status='published'"];
$params = [];
if ($q !== '') {
    $where[] = "(s.title LIKE ? OR s.author LIKE ? OR s.description LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($category !== '') {
    $where[] = 's.category=?';
    $params[] = $category;
}
if ($kind === 'short') {
    $where[] = "s.story_kind='short' AND s.story_type NOT IN ('audio','both')";
} elseif ($kind === 'serial') {
    $where[] = "s.story_kind='serial' AND s.story_type NOT IN ('audio','both')";
} elseif ($kind === 'audio') {
    $where[] = "s.story_type IN ('audio','both')";
}
$whereSql = implode(' AND ', $where);
$stories = [];
$total = 0;
$categories = [];
try {
    $categories = $pdo->query("SELECT DISTINCT category FROM stories WHERE status='published' AND category IS NOT NULL AND category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    $count = $pdo->prepare("SELECT COUNT(*) FROM stories s WHERE {$whereSql}");
    $count->execute($params);
    $total = (int)$count->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    if ($page > $pages) $page = $pages;
    $sql = "SELECT s.id,s.title,s.slug,s.author,s.description,s.cover_url,s.category,s.story_kind,s.story_type,s.views_count,s.likes_count,s.updated_at,
        (SELECT COUNT(*) FROM chapters c WHERE c.story_id=s.id AND c.status='published') AS chapter_count
        FROM stories s WHERE {$whereSql}
        ORDER BY COALESCE((SELECT MAX(c.updated_at) FROM chapters c WHERE c.story_id=s.id AND c.status='published'),s.updated_at) DESC,s.id DESC
        LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stories = $stmt->fetchAll();
} catch (PDOException $e) {
    http_response_code(500);
    $setupError = true;
    $pages = 1;
}
if (!isset($pages)) $pages = 1;
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Khám phá thư viện | Truyện Đêm</title>
<meta name="description" content="Khám phá truyện ngắn, truyện dài và truyện audio tại thư viện Truyện Đêm.">
<link rel="stylesheet" href="/css/style.css?v=20261122-discover1">
<link rel="stylesheet" href="/css/stories.css?v=20261122-discover1">
<link rel="stylesheet" href="/css/truyenhub-theme.css?v=20261113-1">
<link rel="stylesheet" href="/css/site-matte-black.css?v=20261122-discover1">
<link rel="stylesheet" href="/css/discover.css?v=20261122-light-category-fix1">
</head>
<body class="story-site discover-page">
<?php require __DIR__ . '/includes/header.php'; ?>
<main class="discover-main">
 <section class="discover-hero">
  <span class="discover-kicker">✦ THƯ VIỆN TRUYỆN ĐÊM ✦</span>
  <h1>Khám phá câu chuyện tiếp theo</h1>
  <p>Chọn một thế giới để bước vào — từ truyện ngắn, truyện dài đến những thanh âm audio.</p>
  <form class="discover-search" method="get" action="/discover.php">
   <input type="hidden" name="kind" value="<?=discover_h($kind)?>">
   <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?=discover_h($category)?>"><?php endif; ?>
   <input type="search" name="q" value="<?=discover_h($q)?>" placeholder="Tìm tên truyện, tác giả hoặc nội dung..." aria-label="Tìm truyện">
   <button type="submit">Tìm truyện</button>
  </form>
 </section>
 <?php if (!empty($setupError)): ?>
  <section class="discover-empty"><h2>Thư viện chưa sẵn sàng</h2><p>Hãy kiểm tra cơ sở dữ liệu truyện.</p></section>
 <?php else: ?>
 <nav class="discover-filters" aria-label="Lọc loại truyện">
  <a class="<?=$kind==='all'?'is-active':''?>" href="<?=discover_h(discover_query_url(['kind'=>'all','page'=>null]))?>">Tất cả</a>
  <a class="<?=$kind==='short'?'is-active':''?>" href="<?=discover_h(discover_query_url(['kind'=>'short','page'=>null]))?>">Truyện ngắn</a>
  <a class="<?=$kind==='serial'?'is-active':''?>" href="<?=discover_h(discover_query_url(['kind'=>'serial','page'=>null]))?>">Truyện dài</a>
  <a class="<?=$kind==='audio'?'is-active':''?>" href="<?=discover_h(discover_query_url(['kind'=>'audio','page'=>null]))?>">Truyện audio</a>
 </nav>
 <form class="discover-category" method="get" action="/discover.php">
  <input type="hidden" name="kind" value="<?=discover_h($kind)?>">
  <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?=discover_h($q)?>"><?php endif; ?>
  <label for="discoverCategory">Thể loại</label>
  <select id="discoverCategory" name="category" onchange="this.form.submit()">
   <option value="">Tất cả thể loại</option>
   <?php foreach ($categories as $item): ?><option value="<?=discover_h($item)?>" <?=$category===$item?'selected':''?>><?=discover_h($item)?></option><?php endforeach; ?>
  </select>
  <?php if ($category !== ''): ?><a href="<?=discover_h(discover_query_url(['category'=>null,'page'=>null]))?>">Xóa lọc</a><?php endif; ?>
 </form>
 <div class="discover-result-head"><h2><?= $kind==='short'?'Truyện ngắn':($kind==='serial'?'Truyện dài':($kind==='audio'?'Truyện audio':'Tất cả câu chuyện')) ?></h2><span><?=number_format($total)?> kết quả · Trang <?=$page?>/<?=$pages?></span></div>
 <?php if (!$stories): ?>
  <section class="discover-empty"><span>☾</span><h3>Chưa tìm thấy câu chuyện phù hợp</h3><p>Thử đổi từ khóa hoặc bộ lọc nhé.</p><a href="/discover.php">Xem toàn bộ thư viện</a></section>
 <?php else: ?>
  <div class="discover-grid">
   <?php foreach ($stories as $story): ?>
    <article class="discover-card">
     <a class="discover-cover" href="<?=discover_h(discover_slug_url($story))?>">
      <?php if (!empty($story['cover_url'])): ?><img src="<?=discover_h($story['cover_url'])?>" alt="<?=discover_h($story['title'])?>" loading="lazy"><?php else: ?><span>☾</span><?php endif; ?>
      <i><?=in_array((string)$story['story_type'],['audio','both'],true)?'AUDIO':(($story['story_kind']??'serial')==='short'?'TRUYỆN NGẮN':'TRUYỆN DÀI')?></i>
     </a>
     <div class="discover-card-body">
      <span class="discover-category-tag"><?=discover_h($story['category'] ?: 'Truyện Đêm')?></span>
      <h3><a href="<?=discover_h(discover_slug_url($story))?>"><?=discover_h($story['title'])?></a></h3>
      <p><?=discover_h(mb_strimwidth((string)$story['description'],0,118,'…','UTF-8'))?></p>
      <div class="discover-meta"><span><?=discover_h($story['author'] ?: 'Truyện Đêm')?></span><span><?=in_array((string)$story['story_type'],['audio','both'],true)?'Nghe audio':(($story['story_kind']??'serial')==='short'?'Trọn bộ':(int)$story['chapter_count'].' chương')?></span></div>
      <div class="discover-stats"><span>◉ <?=number_format((int)$story['views_count'])?></span><span>♥ <?=number_format((int)$story['likes_count'])?></span><a href="<?=discover_h(discover_slug_url($story))?>">Khám phá →</a></div>
     </div>
    </article>
   <?php endforeach; ?>
  </div>
  <?php if ($pages > 1): ?><nav class="discover-pagination" aria-label="Phân trang">
   <?php if ($page > 1): ?><a href="<?=discover_h(discover_query_url(['page'=>$page-1]))?>">← Trước</a><?php endif; ?>
   <?php
    $start=max(1,$page-2);$end=min($pages,$page+2);
    for($p=$start;$p<=$end;$p++):
   ?><a class="<?=$p===$page?'is-current':''?>" href="<?=discover_h(discover_query_url(['page'=>$p]))?>"><?=$p?></a><?php endfor; ?>
   <?php if ($page < $pages): ?><a href="<?=discover_h(discover_query_url(['page'=>$page+1]))?>">Sau →</a><?php endif; ?>
  </nav><?php endif; ?>
 <?php endif; ?>
 <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body></html>
