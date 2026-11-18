<?php
// Header dùng chung cho toàn bộ giao diện Truyện Đêm.
$tdMemberLoggedIn = !empty($_SESSION['member_id']);
$tdHeaderCsrf = htmlspecialchars((string)($_SESSION['member_csrf'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$tdGenres = [];
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $tdGenreStmt = $pdo->query("SELECT DISTINCT category FROM stories WHERE category IS NOT NULL AND TRIM(category) <> '' ORDER BY category ASC LIMIT 12");
        $tdGenres = $tdGenreStmt ? $tdGenreStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
} catch (Throwable $e) { $tdGenres = []; }
?>
<link rel="stylesheet" href="/css/site-header.css?v=20261118-drawer2">
<header class="site-header td-global-header">
  <div class="header-inner td-header-inner">
    <a class="brand" href="/index.php" aria-label="Truyện Đêm - Trang chủ">
      <span class="brand-mark">☾</span>
      <span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span>
    </a>
    <button class="mobile-menu-toggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="tdGlobalMenu"><span></span><span></span><span></span></button>
  </div>
</header>
<div class="td-drawer-backdrop" id="tdDrawerBackdrop" hidden></div>
<aside class="td-drawer" id="tdGlobalMenu" aria-hidden="true" aria-label="Menu Truyện Đêm">
  <div class="td-drawer-head">
    <a class="td-drawer-brand" href="/index.php"><span class="td-drawer-logo">☾</span><strong>Truyện Đêm</strong></a>
    <button class="td-drawer-close" type="button" aria-label="Đóng menu">×</button>
  </div>
  <?php if ($tdMemberLoggedIn): ?>
    <a class="td-drawer-auth" href="/account.php">♙ &nbsp; Tài khoản thành viên</a>
  <?php else: ?>
    <a class="td-drawer-auth" href="/login.php">⇥ &nbsp; Đăng nhập / Đăng ký</a>
  <?php endif; ?>
  <div class="td-drawer-scroll">
    <section class="td-drawer-section">
      <h3>KHÁM PHÁ</h3>
      <a class="td-drawer-link" href="/index.php"><span>⌂</span> Trang chủ</a>
      <a class="td-drawer-link" href="/index.php#library"><span>✧</span> Mới cập nhật</a>
      <a class="td-drawer-link" href="/index.php#hot"><span>♨</span> Truyện HOT</a>
      <a class="td-drawer-link" href="/index.php#ranking"><span>♜</span> Bảng xếp hạng</a>
      <a class="td-drawer-link" href="/index.php#completed"><span>✓</span> Truyện hoàn thành</a>
      <a class="td-drawer-link" href="/community.php"><span>♧</span> Cộng đồng</a>
    </section>
    <section class="td-drawer-section td-drawer-genres">
      <div class="td-drawer-section-title"><h3>THỂ LOẠI PHỔ BIẾN</h3><a href="/index.php#library">Tất cả ›</a></div>
      <?php if ($tdGenres): ?>
        <div class="td-genre-grid">
          <?php foreach ($tdGenres as $tdGenre): ?><a href="/index.php?category=<?= rawurlencode((string)$tdGenre) ?>#library"><?= htmlspecialchars((string)$tdGenre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a><?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="td-genre-grid"><a href="/index.php#library">Truyện ngắn</a><a href="/index.php#library">Truyện dài</a><a href="/index.php#library">Truyện mới</a><a href="/index.php#library">Tất cả truyện</a></div>
      <?php endif; ?>
    </section>
    <section class="td-drawer-section">
      <h3>GÓC THÀNH VIÊN</h3>
      <?php if ($tdMemberLoggedIn): ?>
        <a class="td-drawer-link" href="/submit_story.php"><span>✎</span> Đăng truyện</a>
        <a class="td-drawer-link" href="/library.php"><span>♡</span> Tủ sách</a>
        <a class="td-drawer-link" href="/notifications.php"><span>♧</span> Thông báo</a>
        <a class="td-drawer-link" href="/messages.php"><span>✉</span> Tin nhắn</a>
        <a class="td-drawer-link" href="/friends.php"><span>♙</span> Bạn bè</a>
        <form class="td-drawer-logout" method="post" action="/logout.php"><input type="hidden" name="csrf" value="<?= $tdHeaderCsrf ?>"><button type="submit">↪ &nbsp; Đăng xuất</button></form>
      <?php else: ?>
        <a class="td-drawer-link" href="/register.php"><span>♙</span> Tạo tài khoản</a>
        <a class="td-drawer-link" href="/shop.php"><span>✧</span> Cửa hàng</a>
      <?php endif; ?>
    </section>
  </div>
</aside>
<script>
(function () {
  var header = document.querySelector('.td-global-header');
  var drawer = document.getElementById('tdGlobalMenu');
  var backdrop = document.getElementById('tdDrawerBackdrop');
  if (!header || !drawer || !backdrop || header.dataset.drawerReady === '1') return;
  header.dataset.drawerReady = '1';
  var toggle = header.querySelector('.mobile-menu-toggle');
  var close = drawer.querySelector('.td-drawer-close');
  function openDrawer() {
    backdrop.hidden = false;
    requestAnimationFrame(function () { drawer.classList.add('is-open'); backdrop.classList.add('is-visible'); });
    drawer.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('td-drawer-open');
  }
  function closeDrawer() {
    drawer.classList.remove('is-open');
    backdrop.classList.remove('is-visible');
    drawer.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('td-drawer-open');
    window.setTimeout(function () { if (!drawer.classList.contains('is-open')) backdrop.hidden = true; }, 260);
  }
  toggle.addEventListener('click', openDrawer);
  close.addEventListener('click', closeDrawer);
  backdrop.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeDrawer(); });
  drawer.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', closeDrawer); });
})();
</script>
