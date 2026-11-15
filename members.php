<?php
require_once __DIR__ . '/config.php';
$me = require_login();

$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT id, full_name, role, bio, avatar, interests, experience_level, is_mentor, created_at,
        (SELECT COUNT(*) FROM resource_progress rp WHERE rp.user_id = users.id) AS completed_res
        FROM users WHERE active = 1";
$params = [];

if ($q !== '') {
    $sql .= " AND (full_name LIKE ? OR interests LIKE ? OR bio LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($filter === 'mentors') {
    $sql .= " AND is_mentor = 1";
} elseif ($filter === 'admins') {
    $sql .= " AND role = 'admin'";
}

$sql .= " ORDER BY (role = 'admin') DESC, is_mentor DESC, full_name ASC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

$pageTitle = 'Member & Team Directory';
require __DIR__ . '/includes/header.php';
?>
<div class="section-head-row">
  <div>
    <span class="eyebrow">Team &amp; Community Profiles</span>
    <h1 style="margin:4px 0">Member Directory</h1>
    <p class="lead" style="margin-bottom:0">Meet the organisers, mentors, and fellow developers of Django Girls Sierra Leone.</p>
  </div>
  <a class="btn btn-small" href="profile.php">&#9998; Edit My Profile</a>
</div>

<div class="directory-toolbar">
  <div class="filters" style="margin:0">
    <a class="chip <?= $filter === 'all' ? 'on' : '' ?>" href="members.php?q=<?= urlencode($q) ?>">All Members (<?= count($members) ?>)</a>
    <a class="chip <?= $filter === 'mentors' ? 'on' : '' ?>" href="members.php?filter=mentors&amp;q=<?= urlencode($q) ?>">Mentors</a>
    <a class="chip <?= $filter === 'admins' ? 'on' : '' ?>" href="members.php?filter=admins&amp;q=<?= urlencode($q) ?>">Organisers</a>
  </div>
  <form method="get" class="inline search" style="margin:0">
    <input type="hidden" name="filter" value="<?= e($filter) ?>">
    <input name="q" value="<?= e($q) ?>" placeholder="Search by name, skill or bio..." aria-label="Search members">
    <button class="btn btn-small" type="submit">Search</button>
  </form>
</div>

<?php if (!$members): ?>
  <section class="card"><p>No members matched your search. <a href="members.php">Show all members</a></p></section>
<?php else: ?>
  <div class="grid">
    <?php foreach ($members as $m):
      $mInterests = user_interests($m);
    ?>
      <article class="card member-card">
        <div class="post-meta">
          <?= avatar_html($m, '', 'avatar-lg') ?>
          <div>
            <h2 style="margin:0 0 4px"><a href="member.php?id=<?= (int)$m['id'] ?>"><?= e($m['full_name']) ?></a></h2>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <?php if ($m['role'] === 'admin'): ?><span class="tag tag-pink">Organiser</span><?php endif; ?>
              <?php if ($m['is_mentor']): ?><span class="tag tag-ok">Mentor</span><?php endif; ?>
              <span class="tag"><?= e(ucfirst($m['experience_level'])) ?></span>
            </div>
          </div>
        </div>
        <p style="margin:12px 0;font-size:.95rem"><?= e($m['bio'] !== '' ? $m['bio'] : 'Member of Django Girls Sierra Leone.') ?></p>
        <?php if ($mInterests): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
            <?php foreach ($mInterests as $int): ?>
              <span class="tag" style="font-size:.75rem"><?= e($int) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="member-card-foot">
          <span class="small">&#127891; <?= (int)$m['completed_res'] ?> tutorials done</span>
          <a class="btn btn-ghost btn-small" href="member.php?id=<?= (int)$m['id'] ?>">View Profile &rarr;</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
