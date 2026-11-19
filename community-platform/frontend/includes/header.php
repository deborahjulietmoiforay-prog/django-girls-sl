<?php
require_once __DIR__ . '/../config.php';
$base = $base ?? '';
$me = current_user();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$inAdmin = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false;

// Returns 'active' when the current page matches (ignores admin pages unless asked)
$isActive = function (array $pages, bool $admin = false) use ($currentPage, $inAdmin): string {
    if ($admin) { return $inAdmin ? 'active' : ''; }
    return (!$inAdmin && in_array($currentPage, $pages, true)) ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#2c4f4a">
<title><?= e($pageTitle ?? ORG_NAME) ?> | <?= e(ORG_NAME) ?></title>
<link rel="stylesheet" href="<?= e($base) ?>assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a href="<?= e($base) ?>index.php" class="brand" aria-label="<?= e(ORG_NAME) ?> home">
      <img src="<?= e($base) ?>assets/logo.svg" alt="Django Girls Sierra Leone logo" class="logo"
           onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'logo-fallback',textContent:'{♥} Django Girls SL'}))">
    </a>

    <!-- Hamburger button (visible on mobile & tablet only) -->
    <button class="nav-toggle" type="button" id="navToggle"
            aria-controls="mainNav" aria-expanded="false" aria-label="Open menu">
      <span class="nav-toggle-box" aria-hidden="true">
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
      </span>
      <span class="nav-toggle-label">Menu</span>
    </button>

    <nav class="main-nav" id="mainNav" aria-label="Main navigation">
      <!-- Drawer header (mobile only) -->
      <div class="drawer-head">
        <?php if ($me): ?>
          <div class="drawer-user">
            <?= avatar_html($me, $base, 'avatar-lg') ?>
            <div>
              <strong><?= e($me['full_name']) ?></strong>
              <span><?= e(ucfirst($me['role'])) ?><?= $me['is_mentor'] ? ' &bull; Mentor' : '' ?></span>
            </div>
          </div>
        <?php else: ?>
          <div class="drawer-user">
            <span class="drawer-logo-mark">{&#9829;}</span>
            <div>
              <strong>Django Girls SL</strong>
              <span>Learn to code. Build your future.</span>
            </div>
          </div>
        <?php endif; ?>
        <button class="drawer-close" type="button" id="navClose" aria-label="Close menu">&times;</button>
      </div>

      <div class="drawer-links">
        <span class="drawer-section">Navigate</span>
        <a href="<?= e($base) ?>index.php" class="<?= $isActive(['index.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('home', 'icon-sm') ?></span>Home</a>
        <?php if ($me): ?>
          <a href="<?= e($base) ?>dashboard.php" class="<?= $isActive(['dashboard.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('dashboard', 'icon-sm') ?></span>Dashboard</a>
          <a href="<?= e($base) ?>events.php" class="<?= $isActive(['events.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('calendar', 'icon-sm') ?></span>Events</a>
          <a href="<?= e($base) ?>resources.php" class="<?= $isActive(['resources.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('book', 'icon-sm') ?></span>Resources</a>
          <span class="drawer-section">Community</span>
          <a href="<?= e($base) ?>community.php" class="<?= $isActive(['community.php', 'topic.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('message-square', 'icon-sm') ?></span>Forum</a>
          <a href="<?= e($base) ?>mentors.php" class="<?= $isActive(['mentors.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('users', 'icon-sm') ?></span>Mentors</a>
          <a href="<?= e($base) ?>members.php" class="<?= $isActive(['members.php', 'member.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('user', 'icon-sm') ?></span>Members</a>
          <?php if ($me['role'] === 'admin'): ?>
            <span class="drawer-section">Administration</span>
            <a href="<?= e($base) ?>admin/index.php" class="nav-admin <?= $isActive([], true) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('shield', 'icon-sm') ?></span>Admin Panel</a>
          <?php endif; ?>
          <span class="drawer-section">Account</span>
          <a href="<?= e($base) ?>profile.php" class="nav-profile <?= $isActive(['profile.php']) ?>">
            <?= avatar_html($me, $base, 'avatar-sm') ?>
            <span><?= e(explode(' ', $me['full_name'])[0]) ?></span>
          </a>
        <?php else: ?>
          <a href="<?= e($base) ?>login.php" class="<?= $isActive(['login.php']) ?>"><span class="nav-ico" aria-hidden="true"><?= icon('log-in', 'icon-sm') ?></span>Log in</a>
        <?php endif; ?>
      </div>

      <div class="drawer-foot">
        <?php if ($me): ?>
          <a href="<?= e($base) ?>logout.php" class="btn btn-small btn-outline">Log out</a>
        <?php else: ?>
          <a href="<?= e($base) ?>signup.php" class="btn btn-small">Join Community</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
  <div class="nav-overlay" id="navOverlay" hidden></div>
</header>

<script>
(function () {
  var toggle  = document.getElementById('navToggle');
  var nav     = document.getElementById('mainNav');
  var overlay = document.getElementById('navOverlay');
  var closeBt = document.getElementById('navClose');
  var mq      = window.matchMedia('(max-width: 1024px)');
  if (!toggle || !nav) return;

  function focusables() {
    return nav.querySelectorAll('a[href], button:not([disabled])');
  }
  function open() {
    document.body.classList.add('nav-open');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
    overlay.hidden = false;
    requestAnimationFrame(function () { overlay.classList.add('show'); });
    var f = focusables(); if (f.length) f[0].focus();
  }
  function close(returnFocus) {
    document.body.classList.remove('nav-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
    overlay.classList.remove('show');
    setTimeout(function () { if (!document.body.classList.contains('nav-open')) overlay.hidden = true; }, 300);
    if (returnFocus) toggle.focus();
  }
  function isOpen() { return document.body.classList.contains('nav-open'); }

  toggle.addEventListener('click', function () { isOpen() ? close(true) : open(); });
  overlay.addEventListener('click', function () { close(true); });
  closeBt.addEventListener('click', function () { close(true); });
  nav.addEventListener('click', function (e) { if (e.target.closest('a') && mq.matches) close(false); });

  document.addEventListener('keydown', function (e) {
    if (!isOpen()) return;
    if (e.key === 'Escape') { close(true); return; }
    if (e.key === 'Tab') { // keep keyboard focus inside the drawer
      var f = focusables(), first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  // Swipe right to close on touch devices
  var startX = null;
  nav.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
  nav.addEventListener('touchend', function (e) {
    if (startX !== null && e.changedTouches[0].clientX - startX > 70) close(false);
    startX = null;
  });

  // Reset when resizing back to desktop
  (mq.addEventListener ? mq.addEventListener.bind(mq, 'change') : mq.addListener.bind(mq))(function (ev) {
    if (!ev.matches && isOpen()) close(false);
  });
})();
</script>

<main class="container">
<?php if ($m = flash()): ?>
  <div class="notice" role="status">
    <span class="notice-icon"><?= icon('check', 'icon-sm') ?></span>
    <span><?= e($m) ?></span>
  </div>
<?php endif; ?>
