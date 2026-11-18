<?php
// Header dùng chung cho toàn bộ giao diện Truyện Đêm.
$tdMemberLoggedIn = !empty($_SESSION['member_id']);
$tdHeaderCsrf = htmlspecialchars((string)($_SESSION['member_csrf'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<link rel="stylesheet" href="/css/site-header.css?v=20261118-global1">
<header class="site-header td-global-header">
  <div class="header-inner td-header-inner">
    <a class="brand" href="/index.php" aria-label="Truyện Đêm - Trang chủ">
      <span class="brand-mark">☾</span>
      <span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span>
    </a>
    <button class="mobile-menu-toggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="tdGlobalMenu">☰</button>
    <nav class="header-links td-global-menu" id="tdGlobalMenu" aria-label="Điều hướng chính">
      <a href="/index.php">Trang chủ</a>
      <a href="/index.php#library">Thư viện</a>
      <?php if ($tdMemberLoggedIn): ?>
        <a href="/library.php">Tủ sách</a>
        <a href="/submit_story.php">Đăng truyện</a>
        <a href="/notifications.php">Thông báo</a>
      <?php endif; ?>
      <a href="/community.php">Cộng đồng</a>
      <a href="/friends.php">Bạn bè</a>
      <a href="/messages.php">Tin nhắn</a>
      <a href="/shop.php">Cửa hàng</a>
      <?php if ($tdMemberLoggedIn): ?>
        <a href="/account.php">Tài khoản</a>
        <form class="member-nav-logout" method="post" action="/logout.php">
          <input type="hidden" name="csrf" value="<?= $tdHeaderCsrf ?>">
          <button type="submit">Đăng xuất</button>
        </form>
      <?php else: ?>
        <a href="/login.php">Đăng nhập</a>
        <a href="/register.php">Đăng ký</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<script>
(function () {
  var header = document.querySelector('.td-global-header');
  if (!header || header.dataset.menuReady === '1') return;
  header.dataset.menuReady = '1';
  var toggle = header.querySelector('.mobile-menu-toggle');
  var menu = header.querySelector('.td-global-menu');
  if (!toggle || !menu) return;
  toggle.addEventListener('click', function () {
    var opened = menu.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
    toggle.setAttribute('aria-label', opened ? 'Đóng menu' : 'Mở menu');
    toggle.textContent = opened ? '×' : '☰';
  });
  document.addEventListener('click', function (event) {
    if (!header.contains(event.target)) {
      menu.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.textContent = '☰';
    }
  });
})();
</script>
