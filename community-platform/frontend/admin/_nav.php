<?php $cur = basename($_SERVER['SCRIPT_NAME']); ?>
<div class="admin-nav-bar">
  <div class="filters" style="margin:0">
    <a class="chip <?= $cur === 'index.php' ? 'on' : '' ?>" href="index.php"><?= icon('trending-up', 'icon-sm') ?> Analytics &amp; Reports</a>
    <a class="chip <?= $cur === 'users.php' ? 'on' : '' ?>" href="users.php"><?= icon('users', 'icon-sm') ?> Manage Users</a>
    <a class="chip <?= $cur === 'events.php' ? 'on' : '' ?>" href="events.php"><?= icon('calendar', 'icon-sm') ?> Manage Events</a>
    <a class="chip <?= $cur === 'resources.php' ? 'on' : '' ?>" href="resources.php"><?= icon('book', 'icon-sm') ?> Manage Resources</a>
  </div>
</div>
