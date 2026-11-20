<?php
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$adminCurrent = basename($_SERVER['PHP_SELF'] ?? '');
$adminLinks = [
 ['index.php','Tổng quan','⌂'],
 ['review_stories.php','Duyệt bài','✓'],
 ['reports.php','Báo cáo vi phạm','⚑']
];
if (($_SESSION['blog_admin_role'] ?? 'admin') === 'admin') {
 $adminLinks[] = ['stories.php?list=all','Kho truyện','▤'];
 $adminLinks[] = ['audio.php','Studio audio','♫'];
 $adminLinks[] = ['members.php','Thành viên','♙'];
 $adminLinks[] = ['contributors.php','Cộng tác viên','✦'];
 $adminLinks[] = ['moderators.php','Kiểm duyệt viên','✓'];
}
?>
<header class="cms-topbar">
 <a class="cms-brand" href="index.php"><span class="cms-brand-mark">Đ</span><span><b>TRUYỆN ĐÊM</b><small>NEWSROOM CMS</small></span></a>
 <nav class="cms-nav" aria-label="Điều hướng quản trị">
 <?php foreach ($adminLinks as $item): $active = ($adminCurrent === 'index.php' && $item[0] === 'index.php') || ($adminCurrent === 'stories.php' && str_starts_with($item[0],'stories.php')) || ($adminCurrent === $item[0]); ?>
 <a class="<?= $active ? 'is-active' : '' ?>" href="<?= htmlspecialchars($item[0],ENT_QUOTES,'UTF-8') ?>"><span><?= $item[2] ?></span><?= htmlspecialchars($item[1],ENT_QUOTES,'UTF-8') ?></a>
 <?php endforeach; ?>
 </nav>
 <div class="cms-user"><span class="cms-user-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['blog_admin_name'] ?? 'A',0,1,'UTF-8'),'UTF-8'),ENT_QUOTES,'UTF-8') ?></span><span class="cms-user-name"><?= htmlspecialchars($_SESSION['blog_admin_name'] ?? 'Quản trị viên',ENT_QUOTES,'UTF-8') ?></span><a href="../index.php" target="_blank" rel="noopener">Xem site ↗</a><form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'],ENT_QUOTES,'UTF-8') ?>"><button type="submit">Thoát</button></form></div>
</header>
<div class="cms-mobile-links"><?php foreach ($adminLinks as $item): ?><a href="<?= htmlspecialchars($item[0],ENT_QUOTES,'UTF-8') ?>"><?= $item[2] ?> <?= htmlspecialchars($item[1],ENT_QUOTES,'UTF-8') ?></a><?php endforeach; ?></div>