<?php
require __DIR__ . '/../includes/db.php';
if (empty($_SESSION['blog_admin_id'])) { header('Location: login.php'); exit; }
$posts = $pdo->query('SELECT id,title,slug,status,created_at,story_type,story_kind FROM stories ORDER BY id DESC')->fetchAll();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$totalPosts = count($posts);
$publishedPosts = count(array_filter($posts, static fn($p) => $p['status'] === 'published'));
$draftPosts = $totalPosts - $publishedPosts;
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tổng quan quản trị · Truyện Đêm</title>
<link rel="stylesheet" href="../css/style.css?v=20261110">
<link rel="stylesheet" href="../css/admin-white.css?v=20261121-layout2">
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
      <section class="admin-quick-section">
        <div class="admin-section-heading"><div><h2>Truy cập nhanh</h2><p>Các khu vực quản lý thường dùng</p></div></div>
        <div class="admin-quick-grid">
          <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="stories.php?list=short" class="admin-quick-card"><span class="quick-icon violet">▤</span><span><strong>Truyện ngắn</strong><small>Quản lý truyện ngắn</small></span><b>→</b></a>
          <a href="stories.php?list=serial" class="admin-quick-card"><span class="quick-icon blue">▥</span><span><strong>Truyện nhiều chương</strong><small>Quản lý tập và chương</small></span><b>→</b></a>
          <a href="audio.php" class="admin-quick-card"><span class="quick-icon rose">♫</span><span><strong>Truyện audio</strong><small>Quản lý MP3 và danh sách nghe</small></span><b>→</b></a>
          <?php endif; ?><a href="reports.php" class="admin-quick-card"><span class="quick-icon rose">⚑</span><span><strong>Báo cáo vi phạm</strong><small>Tiếp nhận và xử lý báo cáo</small></span><b>→</b></a>
          <a href="notifications.php" class="admin-quick-card"><span class="quick-icon violet">♧</span><span><strong>Thông báo quản trị</strong><small>Báo cáo mới và bài gửi chờ duyệt</small></span><b>→</b></a>
          <?php if(($_SESSION["blog_admin_role"] ?? "admin") === "admin"): ?><a href="contributors.php" class="admin-quick-card"><span class="quick-icon violet">✦</span><span><strong>Cộng tác viên</strong><small>Quản lý quyền CTV</small></span><b>→</b></a><?php endif; ?>
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