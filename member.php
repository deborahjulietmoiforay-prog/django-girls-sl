<?php
require_once __DIR__ . '/config.php';
$me = require_login();
$s = db()->prepare('SELECT id, full_name, role, bio, avatar, interests, experience_level, is_mentor, created_at FROM users WHERE id = ? AND active = 1');
$s->execute([(int)($_GET['id'] ?? 0)]);
$u = $s->fetch();
if (!$u) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="card"><h1>Member not found</h1><p><a href="members.php">Back to Member Directory</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$s = db()->prepare('SELECT (SELECT COUNT(*) FROM topics WHERE user_id = ?) + (SELECT COUNT(*) FROM replies WHERE user_id = ?)');
$s->execute([$u['id'], $u['id']]);
$posts = (int)$s->fetchColumn();

$s = db()->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ? AND status != 'cancelled'");
$s->execute([$u['id']]);
$events = (int)$s->fetchColumn();

$s = db()->prepare('SELECT COUNT(*) FROM resource_progress WHERE user_id = ?');
$s->execute([$u['id']]);
$completedRes = (int)$s->fetchColumn();

$s = db()->prepare('SELECT id, title, created_at FROM topics WHERE user_id = ? ORDER BY id DESC LIMIT 5');
$s->execute([$u['id']]);
$memberTopics = $s->fetchAll();

$uInterests = user_interests($u);

$pageTitle = $u['full_name'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="members.php">&larr; Back to Member Directory</a></p>

<section class="card profile-showcase">
  <div class="profile-banner-bg"></div>
  <div class="profile-showcase-body">
    <div class="dash-head">
      <?= avatar_html($u, '', 'avatar-xl') ?>
      <div>
        <h1 style="margin-bottom:6px"><?= e($u['full_name']) ?></h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <span class="tag"><?= e(ucfirst($u['role'])) ?></span>
          <span class="tag"><?= e(ucfirst($u['experience_level'])) ?> Developer</span>
          <?php if ($u['is_mentor']): ?><span class="tag tag-ok">&#9733; Community Mentor</span><?php endif; ?>
        </div>
      </div>
    </div>

    <p class="profile-bio"><?= $u['bio'] !== '' ? nl2br(e($u['bio'])) : 'This member has not written a bio yet.' ?></p>

    <?php if ($uInterests): ?>
      <div style="margin:16px 0">
        <strong class="small" style="display:block;margin-bottom:6px">Skills &amp; Interests:</strong>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <?php foreach ($uInterests as $int): ?>
            <span class="tag tag-pink"><?= e($int) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="stats" style="margin-top:20px">
      <div class="stat">
        <strong><?= $completedRes ?></strong>
        <span>Tutorials Completed</span>
      </div>
      <div class="stat">
        <strong><?= $events ?></strong>
        <span>Workshops Joined</span>
      </div>
      <div class="stat">
        <strong><?= $posts ?></strong>
        <span>Forum Contributions</span>
      </div>
      <div class="stat">
        <strong style="font-size:1.2rem"><?= e(fmt($u['created_at'], 'M Y')) ?></strong>
        <span>Member Since</span>
      </div>
    </div>

    <div class="actions">
      <?php if ((int)$u['id'] === (int)$me['id']): ?>
        <a class="btn" href="profile.php">&#9998; Edit My Profile</a>
      <?php elseif ($u['is_mentor']): ?>
        <a class="btn" href="mentors.php">Request Mentorship from <?= e(explode(' ', $u['full_name'])[0]) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($memberTopics): ?>
<section class="card" style="margin-top:24px">
  <h2>Recent Forum Discussions by <?= e(explode(' ', $u['full_name'])[0]) ?></h2>
  <ul class="list">
    <?php foreach ($memberTopics as $t): ?>
      <li>
        <a href="topic.php?id=<?= (int)$t['id'] ?>"><strong><?= e($t['title']) ?></strong></a>
        <span class="small"><?= e(fmt($t['created_at'], 'j M Y')) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
