<?php
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
$posts = $pdo->query('SELECT id,title,slug,status,created_at,story_type,story_kind FROM stories ORDER BY id DESC')->fetchAll();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$totalPosts = count($posts);
$publishedPosts = count(array_filter($posts, static fn($p) => $p['status'] === 'published'));
$draftPosts = $totalPosts - $publishedPosts;

$readStats = ['today'=>0,'week'=>0,'month'=>0];
if (($_SESSION['blog_admin_role'] ?? 'admin') === 'admin') {
    $readQuery = $pdo->query("SELECT
        COALESCE(SUM(read_date = CURDATE()),0) AS today_reads,
        COALESCE(SUM(read_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
            AND read_date <= CURDATE()),0) AS week_reads,
        COALESCE(SUM(read_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            AND read_date <= CURDATE()),0) AS month_reads
        FROM story_read_events
        WHERE qualified_at IS NOT NULL");
    $readRow = $readQuery->fetch(PDO::FETCH_ASSOC) ?: [];
    $readStats = [
        'today' => (int)($readRow['today_reads'] ?? 0),
        'week' => (int)($readRow['week_reads'] ?? 0),
        'month' => (int)($readRow['month_reads'] ?? 0),
    ];

    $topReadStories = [];
    $periodConditions = [
        'today' => 're.read_date = CURDATE()',
        'week' => 're.read_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND re.read_date <= CURDATE()',
        'month' => "re.read_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND re.read_date <= CURDATE()",
    ];
    foreach ($periodConditions as $period => $condition) {
        $sql = "SELECT s.id, s.title, s.slug, s.story_type, s.story_kind, COUNT(*) AS read_count
            FROM story_read_events re
            INNER JOIN stories s ON s.id = re.story_id
            WHERE re.qualified_at IS NOT NULL
              AND s.status = 'published'
              AND {$condition}
            GROUP BY s.id, s.title, s.slug, s.story_type, s.story_kind
            ORDER BY read_count DESC, s.id DESC
            LIMIT 5";
        $topReadStories[$period] = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tổng quan quản trị · Truyện Đêm</title>
<link rel="stylesheet" href="../css/style.css?v=20261110">
<link rel="stylesheet" href="../css/admin-white.css?v=20261121-layout2">
<style>
.admin-top-read-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:22px 0 28px}
.admin-top-read-card{background:#fff;border:1px solid #e7eaf0;border-radius:14px;padding:18px;min-width:0;box-shadow:0 3px 12px rgba(15,23,42,.035)}
.admin-top-read-card h3{font-size:16px;margin:0 0 5px;color:#172033}
.admin-top-read-card>p{font-size:12px;color:#7b8495;margin:0 0 15px}
.admin-top-read-list{list-style:none;margin:0;padding:0}
.admin-top-read-list li{display:flex;align-items:flex-start;gap:10px;padding:11px 0;border-top:1px solid #eef0f4;min-width:0}
.admin-top-read-rank{width:25px;height:25px;flex:0 0 25px;border-radius:8px;background:#f0f3f9;color:#46536b;display:grid;place-items:center;font-size:12px;font-weight:800}
.admin-top-read-info{min-width:0;flex:1}
.admin-top-read-info a{display:block;color:#202b3e;font-size:13px;font-weight:700;text-decoration:none;line-height:1.45;overflow-wrap:anywhere}
.admin-top-read-info small{display:block;color:#8791a2;font-size:11px;margin-top:3px}
.admin-top-read-count{white-space:nowrap;color:#3449a5;font-size:12px;font-weight:800}
.admin-top-read-empty{padding:18px 0;color:#929aaa;font-size:12px;border-top:1px solid #eef0f4}
@media(max-width:900px){.admin-top-read-grid{grid-template-columns:1fr}}
</style>
</head>
<body class="admin-theme admin-dashboard">
<div class="admin-shell">
  <div class="admin-backdrop" id="adminBackdrop"></div>
  <aside class="admin-sidebar" id="adminSidebar">
    <a class="admin-brand" href="index.php"><span class="admin-brand-mark">Đ</span><span><strong>TRUYỆN ĐÊM</strong><small>TRUNG TÂM QUẢN TRỊ</small></span></a>
    <div class="admin-nav-caption">KHÔNG GIAN LÀM VIỆC</div>
    <nav class="admin-nav">
      <a class="active" href="index.php"><span class="nav-icon">⌂</span><span>Tổng quan</span></a>
      <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?>
      <div class="admin-nav-caption">QUẢN LÝ NỘI DUNG</div>
      <a href="stories.php?list=short"><span class="nav-icon">▤</span><span>Truyện ngắn</span></a>
      <a href="stories.php?list=serial"><span class="nav-icon">▥</span><span>Truyện nhiều chương</span></a>
      <a href="stories.php?list=all"><span class="nav-icon">▦</span><span>Tất cả truyện</span></a>
      <a href="audio.php"><span class="nav-icon">♫</span><span>Truyện audio</span><span class="nav-tag">MP3</span></a>
      <?php endif; ?>
      <div class="admin-nav-caption">CỘNG ĐỒNG</div>
      <a href="review_stories.php"><span class="nav-icon">✓</span><span>Duyệt truyện thành viên</span></a>
      <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="members.php"><span class="nav-icon">♙</span><span>Thành viên</span></a><?php endif; ?>
      <a href="reports.php"><span class="nav-icon">⚑</span><span>Báo cáo vi phạm</span></a>
      <a href="notifications.php"><span class="nav-icon">♧</span><span>Thông báo quản trị</span></a>
      <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="contributors.php"><span class="nav-icon">✦</span><span>Cộng tác viên</span></a><?php endif; ?>
      <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="moderators.php"><span class="nav-icon">✓</span><span>Kiểm duyệt viên</span></a><?php endif; ?>
    </nav>
    <div class="admin-sidebar-bottom">
      <a href="../blog.php" target="_blank" rel="noopener"><span class="nav-icon">↗</span><span>Xem website</span></a>
      <form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button type="submit"><span class="nav-icon">⇥</span><span>Đăng xuất</span></button></form>
    </div>
  </aside>
  <main class="admin-main">
    <header class="admin-topbar">
      <button class="admin-menu-toggle" id="adminMenuToggle" type="button" aria-label="Mở menu" aria-expanded="false">☰</button>
      <div class="admin-breadcrumb">Truyện Đêm <span>/</span> <strong>Tổng quan</strong></div>
      <div class="admin-account"><span class="admin-avatar"><?= e(mb_strtoupper(mb_substr($_SESSION['blog_admin_name'] ?? 'A',0,1,'UTF-8'),'UTF-8')) ?></span><span><strong><?= e($_SESSION['blog_admin_name'] ?? 'Quản trị viên') ?></strong><small>Quản trị viên</small></span></div>
    </header>
    <div class="admin-content">
      <div class="admin-page-heading"><div><div class="admin-eyebrow">DASHBOARD</div><h1>Tổng quan</h1><p>Chào mừng trở lại! Đây là tình hình nội dung trên Truyện Đêm.</p></div><?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a class="admin-primary-action" href="stories.php">＋ <span>Viết truyện mới</span></a><?php endif; ?></div>
      <?php if(isset($_GET['saved'])): ?><div class="admin-alert-success">✓ Bài viết đã được lưu thành công.</div><?php endif; ?>
      <section class="admin-stat-grid">
        <article class="admin-stat-card"><div class="admin-stat-top"><span>Tổng bài viết</span><span class="admin-stat-icon purple">▤</span></div><strong><?= $totalPosts ?></strong><small>Tất cả truyện trong thư viện</small></article>
        <article class="admin-stat-card"><div class="admin-stat-top"><span>Đã xuất bản</span><span class="admin-stat-icon green">✓</span></div><strong><?= $publishedPosts ?></strong><small>Đang hiển thị công khai</small></article>
        <article class="admin-stat-card"><div class="admin-stat-top"><span>Bản nháp</span><span class="admin-stat-icon amber">◷</span></div><strong><?= $draftPosts ?></strong><small>Chưa xuất bản</small></article>
      </section>
      <?php if(($_SESSION['blog_admin_role'] ?? 'admin') === 'admin'): ?>
      <section class="admin-stat-grid admin-read-stat-grid">
        <article class="admin-stat-card"><div class="admin-stat-top"><span>Lượt đọc hôm nay</span><span class="admin-stat-icon blue">◷</span></div><strong><?= number_format($readStats['today']) ?></strong><small>Lượt đọc hợp lệ trong ngày</small></article>
        <article class="admin-stat-card"><div class="admin-stat-top"><span>Lượt đọc tuần này</span><span class="admin-stat-icon violet">▦</span></div><strong><?= number_format($readStats['week']) ?></strong><small>Từ thứ Hai đến hôm nay</small></article>
        <article class="admin-stat-card"><div class="admin-stat-top"><span>Lượt đọc tháng này</span><span class="admin-stat-icon green">▤</span></div><strong><?= number_format($readStats['month']) ?></strong><small>Từ đầu tháng đến hôm nay</small></article>
      </section>
      <?php endif; ?>
      <?php if(($_SESSION['blog_admin_role'] ?? 'admin') === 'admin'): ?>
      <section class="admin-quick-section">
        <div class="admin-section-heading"><div><h2>Top truyện theo lượt đọc</h2><p>Xếp hạng 5 truyện có lượt đọc hợp lệ cao nhất trong từng khoảng thời gian</p></div></div>
        <div class="admin-top-read-grid">
          <?php
          $topReadLabels = ['today'=>['Top truyện hôm nay','Trong ngày hiện tại'],'week'=>['Top truyện tuần này','Từ thứ Hai đến hôm nay'],'month'=>['Top truyện tháng này','Từ đầu tháng đến hôm nay']];
          foreach ($topReadLabels as $period => [$heading,$description]):
          ?>
          <article class="admin-top-read-card">
            <h3><?= e($heading) ?></h3><p><?= e($description) ?></p>
            <?php if (!empty($topReadStories[$period])): ?>
            <ol class="admin-top-read-list">
              <?php foreach ($topReadStories[$period] as $rank => $topStory):
                $topPath = ($topStory['story_type'] ?? 'text') === 'audio' ? 'audio/' : (($topStory['story_kind'] ?? 'serial') === 'short' ? 'truyen-ngan/' : 'truyen/');
              ?>
              <li>
                <span class="admin-top-read-rank"><?= $rank + 1 ?></span>
                <span class="admin-top-read-info"><a href="../<?= $topPath . rawurlencode($topStory['slug']) ?>.html" target="_blank" rel="noopener"><?= e($topStory['title']) ?></a><small><?= $topPath === 'audio/' ? 'Truyện audio' : ($topPath === 'truyen-ngan/' ? 'Truyện ngắn' : 'Truyện nhiều chương') ?></small></span>
                <span class="admin-top-read-count"><?= number_format((int)$topStory['read_count']) ?> lượt</span>
              </li>
              <?php endforeach; ?>
            </ol>
            <?php else: ?><div class="admin-top-read-empty">Chưa có lượt đọc hợp lệ trong khoảng thời gian này.</div><?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
      <section class="admin-quick-section">
        <div class="admin-section-heading"><div><h2>Truy cập nhanh</h2><p>Các khu vực quản lý thường dùng</p></div></div>
        <div class="admin-quick-grid">
          <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="stories.php?list=short" class="admin-quick-card"><span class="quick-icon violet">▤</span><span><strong>Truyện ngắn</strong><small>Quản lý truyện ngắn</small></span><b>→</b></a>
          <a href="stories.php?list=serial" class="admin-quick-card"><span class="quick-icon blue">▥</span><span><strong>Truyện nhiều chương</strong><small>Quản lý tập và chương</small></span><b>→</b></a>
          <a href="audio.php" class="admin-quick-card"><span class="quick-icon rose">♫</span><span><strong>Truyện audio</strong><small>Quản lý MP3 và danh sách nghe</small></span><b>→</b></a>
          <?php endif; ?><a href="reports.php" class="admin-quick-card"><span class="quick-icon rose">⚑</span><span><strong>Báo cáo vi phạm</strong><small>Tiếp nhận và xử lý báo cáo</small></span><b>→</b></a>
          <a href="notifications.php" class="admin-quick-card"><span class="quick-icon violet">♧</span><span><strong>Thông báo quản trị</strong><small>Báo cáo mới và bài gửi chờ duyệt</small></span><b>→</b></a>
          <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="contributors.php" class="admin-quick-card"><span class="quick-icon violet">✦</span><span><strong>Cộng tác viên</strong><small>Quản lý quyền CTV</small></span><b>→</b></a><a href="contributor-rewards.php" class="admin-quick-card"><span class="quick-icon green">₫</span><span><strong>Nhuận bút CTV</strong><small>Ghi nhận và thanh toán</small></span><b>→</b></a><a href="contributor-reward-settings.php" class="admin-quick-card"><span class="quick-icon blue">⚙</span><span><strong>Cấu hình nhuận bút</strong><small>Thiết lập quỹ và đơn giá</small></span><b>→</b></a><?php endif; ?>
          <a href="review_stories.php" class="admin-quick-card"><span class="quick-icon green">✓</span><span><strong>Duyệt truyện</strong><small>Kiểm tra bài thành viên gửi</small></span><b>→</b></a>
        </div>
      </section>
      <section class="admin-recent-section">
        <div class="admin-section-heading"><div><h2>Truyện gần đây</h2><p>Theo dõi nội dung mới nhất trên website</p></div><?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="stories.php?list=all">Quản lý tất cả truyện <span>→</span></a><?php endif; ?></div>
        <div class="admin-table-wrap">
        <?php if(!$posts): ?><div class="admin-empty-state"><span>▤</span><strong>Chưa có truyện nào</strong><p>Bắt đầu xây dựng thư viện bằng cách đăng bài viết đầu tiên.</p><?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="stories.php">＋ Thêm truyện mới</a><?php endif; ?></div>
        <?php else: ?>
          <table class="admin-dashboard-table"><thead><tr><th>TÊN TRUYỆN</th><th>TRẠNG THÁI</th><th>NGÀY TẠO</th><th>THAO TÁC</th></tr></thead><tbody>
          <?php foreach(array_slice($posts,0,8) as $p): ?><tr><td><strong><?= e($p['title']) ?></strong><small class="admin-row-slug"><?= e($p['slug']) ?></small></td><td><span class="admin-status <?= $p['status']==='published'?'is-published':'is-draft' ?>"><?= $p['status']==='published'?'Đã xuất bản':'Bản nháp' ?></span></td><td><?= e(date('d/m/Y',strtotime($p['created_at']))) ?></td><td class="admin-row-actions"><?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="stories.php?edit=<?= (int)$p['id'] ?>">Sửa</a><?php endif; ?><?php if($p['status']==='published'): ?><?php $publicPath = ($p['story_type'] ?? 'text') === 'audio' ? 'audio/' : (($p['story_kind'] ?? 'serial') === 'short' ? 'truyen-ngan/' : 'truyen/'); ?><a href="../<?= $publicPath . rawurlencode($p['slug']) ?>.html" target="_blank" rel="noopener">Xem</a><?php endif; ?><?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><form method="post" action="stories.php" onsubmit="return confirm('Xóa truyện này?')"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete_story"><input type="hidden" name="story_id" value="<?= (int)$p['id'] ?>"><button type="submit">Xóa</button></form><?php endif; ?></td></tr><?php endforeach; ?>
          </tbody></table>
        <?php endif; ?>
        </div>
      </section>
      <footer class="admin-footer">© <?= date('Y') ?> Truyện Đêm · Khu vực quản trị</footer>
    </div>
  </main>
</div>
<script>
(() => {
 const sidebar=document.getElementById('adminSidebar');
 const toggle=document.getElementById('adminMenuToggle');
 const backdrop=document.getElementById('adminBackdrop');
 const close=()=>{sidebar.classList.remove('is-open');backdrop.classList.remove('is-visible');toggle.setAttribute('aria-expanded','false');};
 toggle.addEventListener('click',()=>{const open=!sidebar.classList.contains('is-open');sidebar.classList.toggle('is-open',open);backdrop.classList.toggle('is-visible',open);toggle.setAttribute('aria-expanded',String(open));});
 backdrop.addEventListener('click',close);
 window.addEventListener('resize',()=>{if(window.innerWidth>820)close();});
})();
</script>
</body></html>