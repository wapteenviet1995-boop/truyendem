<?php
// Header dùng chung cho toàn bộ giao diện Truyện Đêm.
$tdMemberLoggedIn = !empty($_SESSION['member_id']);
$tdIsContributor = false;
if ($tdMemberLoggedIn && isset($pdo) && $pdo instanceof PDO) {
    try {
        $tdRoleQuery = $pdo->prepare('SELECT account_type FROM members WHERE id=? LIMIT 1');
        $tdRoleQuery->execute([(int)$_SESSION['member_id']]);
        $tdIsContributor = $tdRoleQuery->fetchColumn() === 'contributor';
    } catch (Throwable $e) { $tdIsContributor = false; }
}
if ($tdMemberLoggedIn && empty($_SESSION['member_csrf'])) {
    $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
}
$tdHeaderCsrf = htmlspecialchars((string)($_SESSION['member_csrf'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$tdUnreadNotifications = 0;
if ($tdMemberLoggedIn && isset($pdo) && $pdo instanceof PDO) {
    try {
        $tdNoticeCount = $pdo->prepare('SELECT COUNT(*) FROM member_notifications WHERE recipient_id=? AND is_read=0');
        $tdNoticeCount->execute([(int)$_SESSION['member_id']]);
        $tdUnreadNotifications = (int)$tdNoticeCount->fetchColumn();
    } catch (Throwable $e) { $tdUnreadNotifications = 0; }
}
$tdGenres = [];
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $tdGenreStmt = $pdo->query("SELECT DISTINCT category FROM stories WHERE category IS NOT NULL AND TRIM(category) <> '' ORDER BY category ASC LIMIT 12");
        $tdGenres = $tdGenreStmt ? $tdGenreStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
} catch (Throwable $e) { $tdGenres = []; }
?>
<link rel="stylesheet" href="/css/site-header.css?v=20261118-compact2">
<link rel="stylesheet" href="/css/theme-mode.css?v=20261121-index-light1">
<style>.td-notification-link{display:flex;align-items:center;gap:8px}.td-notification-count{display:inline-grid;place-items:center;min-width:19px;height:19px;padding:0 5px;border-radius:20px;background:#a879d2;color:#fff;font-size:11px;line-height:1}.td-drawer-link .td-notification-count{margin-left:auto}</style>
<header class="site-header td-global-header">
  <div class="header-inner td-header-inner">
    <a class="brand" href="/index.php" aria-label="Truyện Đêm - Trang chủ">
      <span class="brand-mark">☾</span>
      <span class="brand-copy"><strong>Truyện Đêm</strong><small>Mỗi đêm, một thế giới khác</small></span>
    </a>
    <div class="td-header-actions">
    <button class="td-theme-toggle" type="button" aria-label="Chuyển sang giao diện sáng" title="Chuyển giao diện">☀</button>
    <button class="td-search-toggle" type="button" aria-label="Mở tìm kiếm" aria-expanded="false" aria-controls="tdHeaderSearch">⌕</button>
    <button class="mobile-menu-toggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="tdGlobalMenu"><span></span><span></span><span></span></button>
    </div>
  </div>
</header>
<form class="td-header-search" id="tdHeaderSearch" action="/search.php" method="get" hidden autocomplete="off">
  <div class="td-search-input-wrap">
    <input type="search" id="tdQuickSearchInput" name="q" placeholder="Tìm tên truyện, tác giả..." aria-label="Tìm truyện" autocomplete="off">
    <div class="td-quick-results" id="tdQuickResults" hidden></div>
  </div>
  <button type="submit">Tìm kiếm</button>
</form>

<nav class="td-bottom-nav" aria-label="Điều hướng nhanh">
  <a href="/index.php"><span>⌂</span><small>Trang chủ</small></a>
  <a href="/library.php"><span>▤</span><small>Thư viện</small></a>
  <a class="td-bottom-nav-primary" href="<?= $tdMemberLoggedIn ? '/submit_story.php' : '/login.php' ?>"><span>＋</span><small>Đăng truyện</small></a>
  <a href="/library.php"><span>♡</span><small>Tủ sách</small></a>
  <a href="<?= $tdMemberLoggedIn ? '/account.php' : '/login.php' ?>"><span>♙</span><small>Tài khoản</small></a>
</nav>
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
      <a class="td-drawer-link" href="/index.php#categories"><span>♨</span> Thể loại truyện</a>
      <a class="td-drawer-link" href="/index.php#leaderboard"><span>♜</span> Bảng xếp hạng</a>
      <a class="td-drawer-link" href="/index.php#library"><span>✧</span> Truyện mới cập nhật</a>
      <a class="td-drawer-link" href="/community.php"><span>♧</span> Cộng đồng</a>
      <a class="td-drawer-link" href="/contributor-leaderboard.php"><span>♜</span> Bảng xếp hạng CTV</a>
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
        <?php if($tdIsContributor): ?><a class="td-drawer-link" href="/contributor-dashboard.php"><span>▦</span> Tổng quan CTV</a><?php endif; ?>
        <a class="td-drawer-link" href="/library.php"><span>♡</span> Tủ sách</a>
        <a class="td-drawer-link" href="/following-authors.php"><span>♧</span> Tác giả đang theo dõi</a>
        <a class="td-drawer-link td-notification-link" href="/notifications.php"><span>♧</span> Thông báo <?php if($tdUnreadNotifications>0):?><b class="td-notification-count"><?=$tdUnreadNotifications>99?'99+':$tdUnreadNotifications?></b><?php endif;?></a>
        <a class="td-drawer-link" href="/moderation-history.php"><span>✓</span> Lịch sử kiểm duyệt</a>
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
  var themeToggle = header.querySelector('.td-theme-toggle');
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (themeToggle) {
      var light = theme === 'light';
      themeToggle.textContent = light ? '☾' : '☀';
      themeToggle.setAttribute('aria-label', light ? 'Chuyển sang giao diện tối' : 'Chuyển sang giao diện sáng');
      themeToggle.setAttribute('title', light ? 'Bật giao diện tối' : 'Bật giao diện sáng');
    }
  }
  var savedTheme = 'dark';
  try { savedTheme = localStorage.getItem('td-theme') || 'dark'; } catch (e) {}
  applyTheme(savedTheme === 'light' ? 'light' : 'dark');
  if (themeToggle) themeToggle.addEventListener('click', function () {
    var next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    applyTheme(next);
    try { localStorage.setItem('td-theme', next); } catch (e) {}
  });
  var searchToggle = header.querySelector('.td-search-toggle');
  var searchPanel = document.getElementById('tdHeaderSearch');
  if (searchToggle && searchPanel) {
    var searchInput = document.getElementById('tdQuickSearchInput');
    var resultsBox = document.getElementById('tdQuickResults');
    var searchTimer = null;
    var lastQuery = '';
    searchToggle.addEventListener('click', function () {
      var opening = searchPanel.hidden;
      searchPanel.hidden = !opening;
      searchToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
      if (opening && searchInput) searchInput.focus();
    });
    if (searchInput && resultsBox) {
      searchInput.addEventListener('input', function () {
        var query = searchInput.value.trim();
        window.clearTimeout(searchTimer);
        if (query.length < 2) { resultsBox.hidden = true; resultsBox.innerHTML = ''; return; }
        searchTimer = window.setTimeout(function () {
          if (query === lastQuery) return;
          lastQuery = query;
          resultsBox.hidden = false;
          resultsBox.innerHTML = '<div class="td-quick-empty">Đang tìm...</div>';
          fetch('/search_suggest.php?q=' + encodeURIComponent(query), {headers:{'Accept':'application/json'}})
            .then(function (response) { if (!response.ok) throw new Error('search'); return response.json(); })
            .then(function (items) {
              if (searchInput.value.trim() !== query) return;
              resultsBox.innerHTML = '';
              if (!Array.isArray(items) || !items.length) {
                resultsBox.innerHTML = '<div class="td-quick-empty">Không tìm thấy truyện phù hợp</div>';
              } else {
                var heading = document.createElement('div');
                heading.className = 'td-quick-heading';
                heading.textContent = 'TRUYỆN PHÙ HỢP';
                resultsBox.appendChild(heading);
                items.forEach(function (item) {
                  var link = document.createElement('a');
                  link.className = 'td-quick-result';
                  link.href = item.url;
                  var cover = document.createElement('span');
                  cover.className = 'td-quick-cover';
                  if (item.cover_url) {
                    var img = document.createElement('img');
                    img.src = item.cover_url;
                    img.alt = '';
                    img.loading = 'lazy';
                    img.onerror = function () { cover.classList.add('is-empty'); img.remove(); };
                    cover.appendChild(img);
                  } else {
                    cover.classList.add('is-empty');
                    cover.textContent = '☾';
                  }
                  var info = document.createElement('span');
                  info.className = 'td-quick-info';
                  var title = document.createElement('strong');
                  title.textContent = item.title || 'Truyện';
                  var meta = document.createElement('small');
                  meta.textContent = item.author ? 'Tác giả: ' + item.author : 'Truyện Đêm';
                  info.appendChild(title);
                  info.appendChild(meta);
                  if (item.category) {
                    var tag = document.createElement('em');
                    tag.textContent = item.category;
                    info.appendChild(tag);
                  }
                  link.appendChild(cover);
                  link.appendChild(info);
                  resultsBox.appendChild(link);
                });
                var all = document.createElement('button');
                all.type = 'submit'; all.className = 'td-quick-all';
                all.textContent = 'Xem tất cả kết quả cho “' + query + '”';
                resultsBox.appendChild(all);
              }
              resultsBox.hidden = false;
            })
            .catch(function () { resultsBox.innerHTML = '<div class="td-quick-empty">Không thể tải kết quả lúc này</div>'; resultsBox.hidden = false; });
        }, 250);
      });
      document.addEventListener('click', function (event) {
        if (!searchPanel.contains(event.target) && event.target !== searchToggle) resultsBox.hidden = true;
      });
      searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { resultsBox.hidden = true; searchInput.blur(); }
      });
    }
  }
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
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { closeDrawer(); if (searchPanel) { searchPanel.hidden = true; if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false'); } } });
  drawer.querySelectorAll('a').forEach(function (link) { link.addEventListener('click', closeDrawer); });
})();
</script>
