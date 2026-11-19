<?php
require_once __DIR__ . '/config.php';
$me  = require_login();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('resources.php');
    $rid = (int)($_POST['resource_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'done') {
        $progCheck = db()->prepare('SELECT 1 FROM resource_progress WHERE user_id = ? AND resource_id = ?');
        $progCheck->execute([$uid, $rid]);
        if (!$progCheck->fetchColumn()) {
            db()->prepare('INSERT INTO resource_progress (user_id, resource_id) VALUES (?, ?)')->execute([$uid, $rid]);
        }
        flash('Tutorial marked as completed!');
    } elseif (($_POST['action'] ?? '') === 'undo') {
        db()->prepare('DELETE FROM resource_progress WHERE user_id = ? AND resource_id = ?')->execute([$uid, $rid]);
        flash('Resource completion status updated.');
    }
    $redirectCat = isset($_POST['cat']) && in_array($_POST['cat'], INTERESTS, true) ? '?cat=' . urlencode($_POST['cat']) : '';
    go('resources.php' . $redirectCat);
}

$cat = in_array($_GET['cat'] ?? '', INTERESTS, true) ? $_GET['cat'] : '';
$q   = trim($_GET['q'] ?? '');

$sql = 'SELECT r.*, (SELECT 1 FROM resource_progress p WHERE p.resource_id = r.id AND p.user_id = ?) AS done FROM resources r WHERE 1=1';
$args = [$uid];
if ($cat !== '') {
    $sql .= ' AND r.category = ?';
    $args[] = $cat;
}
if ($q !== '') {
    $sql .= ' AND (r.title LIKE ? OR r.description LIKE ?)';
    $args[] = '%' . $q . '%';
    $args[] = '%' . $q . '%';
}
$s = db()->prepare($sql . ' ORDER BY r.category, r.id');
$s->execute($args);
$list = $s->fetchAll();

$total = (int)db()->query('SELECT COUNT(*) FROM resources')->fetchColumn();
$s = db()->prepare('SELECT COUNT(*) FROM resource_progress WHERE user_id = ?');
$s->execute([$uid]);
$done = (int)$s->fetchColumn();
$pct  = $total ? (int)round($done / $total * 100) : 0;

$pageTitle = 'Learning resources';
require __DIR__ . '/includes/header.php';
?>

<section class="card progress-hero-card">
  <div class="section-head-row" style="margin-bottom:8px">
    <div>
      <span class="eyebrow">Tutorials &amp; Learning Materials</span>
      <h1 style="margin:4px 0">Learning Resource Library</h1>
      <p class="small" style="margin:0">Explore structured web programming guides and mark tutorials complete to track your training progress.</p>
    </div>
    <div class="progress-badge-big">
      <strong><?= $pct ?>%</strong>
      <span><?= $done ?> of <?= $total ?> Completed</span>
    </div>
  </div>
  <div class="bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
    <span style="width:<?= $pct ?>%"></span>
  </div>
</section>

<div class="directory-toolbar">
  <div class="filters" style="margin:0">
    <a class="chip <?= $cat === '' ? 'on' : '' ?>" href="resources.php">All Topics</a>
    <?php foreach (INTERESTS as $i): ?>
      <a class="chip <?= $cat === $i ? 'on' : '' ?>" href="resources.php?cat=<?= urlencode($i) ?>"><?= e($i) ?></a>
    <?php endforeach; ?>
  </div>
  <form method="get" class="inline search" style="margin:0">
    <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
    <input name="q" value="<?= e($q) ?>" placeholder="Search tutorials..." aria-label="Search tutorials">
    <button class="btn btn-small" type="submit"><?= icon('search', 'icon-sm') ?> Search</button>
  </form>
</div>

<?php if (!$list): ?>
  <section class="card"><p>No learning resources matched your filter. <a href="resources.php">View all resources</a></p></section>
<?php else: ?>
  <div class="grid">
    <?php foreach ($list as $r): ?>
      <article class="card resource-card <?= $r['done'] ? 'resource-done' : '' ?>">
        <div class="event-card-top">
          <span class="tag tag-pink"><?= e($r['category']) ?></span>
          <span class="tag"><?= e(ucfirst($r['level'])) ?></span>
          <?php if ($r['done']): ?><span class="tag tag-ok"><?= icon('check', 'icon-sm') ?> Completed</span><?php endif; ?>
        </div>
        <h2><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['title']) ?> <?= icon('external-link', 'icon-sm') ?></a></h2>
        <p><?= e($r['description']) ?></p>

        <div class="resource-actions">
          <a class="btn btn-small btn-ghost" href="<?= e($r['url']) ?>" target="_blank" rel="noopener">Open Tutorial <?= icon('external-link', 'icon-sm') ?></a>
          <form method="post" class="inline">
            <?= csrf_field() ?>
            <input type="hidden" name="resource_id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="cat" value="<?= e($cat) ?>">
            <?php if ($r['done']): ?>
              <button class="btn btn-small btn-outline" name="action" value="undo" type="submit">Undo</button>
            <?php else: ?>
              <button class="btn btn-small" name="action" value="done" type="submit"><?= icon('check', 'icon-sm') ?> Mark Complete</button>
            <?php endif; ?>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
