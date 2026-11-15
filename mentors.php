<?php
require_once __DIR__ . '/config.php';
$me  = require_login();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('mentors.php');
    $action = $_POST['action'] ?? '';
    if ($action === 'request') {
        $mid = (int)($_POST['mentor_id'] ?? 0);
        $s = db()->prepare('SELECT id, full_name FROM users WHERE id = ? AND is_mentor = 1 AND active = 1 AND id != ?');
        $s->execute([$mid, $uid]);
        if ($mentorRow = $s->fetch()) {
            db()->prepare('INSERT OR IGNORE INTO mentorships (mentee_id, mentor_id, message) VALUES (?, ?, ?)')
                ->execute([$uid, $mid, mb_substr(trim($_POST['message'] ?? ''), 0, 300)]);
            flash('Mentorship request sent to ' . $mentorRow['full_name'] . '!');
        }
    } elseif ($action === 'respond') {
        $decision = ($_POST['decision'] ?? '') === 'accept' ? 'accepted' : 'declined';
        db()->prepare("UPDATE mentorships SET status = ? WHERE id = ? AND mentor_id = ? AND status = 'pending'")
            ->execute([$decision, (int)($_POST['id'] ?? 0), $uid]);
        flash('Mentorship request ' . $decision . '.');
    }
    go('mentors.php');
}

// Mentors, with this member's request status for each
$s = db()->prepare("SELECT u.id, u.full_name, u.bio, u.avatar, u.interests, u.experience_level,
        (SELECT status FROM mentorships m WHERE m.mentee_id = ? AND m.mentor_id = u.id) AS my_status,
        (SELECT message FROM mentorships m WHERE m.mentee_id = ? AND m.mentor_id = u.id) AS my_message
    FROM users u WHERE u.is_mentor = 1 AND u.active = 1 AND u.id != ? ORDER BY u.full_name");
$s->execute([$uid, $uid, $uid]);
$mentors = $s->fetchAll();

$incoming = [];
if ($me['is_mentor']) {
    $s = db()->prepare("SELECT m.id, m.message, m.status, m.created_at, u.id AS uid, u.full_name, u.avatar FROM mentorships m
                        JOIN users u ON u.id = m.mentee_id WHERE m.mentor_id = ? ORDER BY (m.status = 'pending') DESC, m.id DESC");
    $s->execute([$uid]);
    $incoming = $s->fetchAll();
}

$pageTitle = 'Mentors';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-card">
  <div>
    <span class="eyebrow">1-on-1 Developer Guidance</span>
    <h1>Mentorship Connections</h1>
    <p class="lead" style="margin-bottom:0">Connect with experienced Sierra Leonean developers and organisers who volunteer to review code, answer questions, and guide your learning journey.</p>
  </div>
  <img src="assets/hero-banner.jpg" alt="Mentorship session in Freetown" class="page-hero-img">
</section>

<?php if ($incoming): ?>
<h2 class="section-title">&#128229; Mentorship Requests Sent to You</h2>
<div class="scroll">
  <table class="table">
    <thead>
      <tr>
        <th>Mentee</th>
        <th>Request Message</th>
        <th>Requested On</th>
        <th>Status / Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($incoming as $r): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <?= avatar_html($r, '', 'avatar-sm') ?>
              <a href="member.php?id=<?= (int)$r['uid'] ?>"><strong><?= e($r['full_name']) ?></strong></a>
            </div>
          </td>
          <td><?= e($r['message'] ?: 'Would love your mentorship guidance!') ?></td>
          <td><?= e(fmt($r['created_at'], 'j M Y')) ?></td>
          <td>
            <?php if ($r['status'] === 'pending'): ?>
              <form method="post" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="respond">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-small" name="decision" value="accept">Accept</button>
                <button class="btn btn-ghost btn-small" name="decision" value="decline">Decline</button>
              </form>
            <?php else: ?>
              <span class="tag <?= $r['status'] === 'accepted' ? 'tag-ok' : '' ?>"><?= e(ucfirst($r['status'])) ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<h2 class="section-title">&#128105;&#8205;&#128187; Available Community Mentors</h2>
<?php if (!$mentors): ?>
  <section class="card"><p>No other mentors are listed right now. Experienced members can opt in from their <a href="profile.php">Profile Settings</a>.</p></section>
<?php else: ?>
  <div class="grid">
    <?php foreach ($mentors as $m):
      $mSkills = user_interests($m);
    ?>
      <article class="card mentor-card">
        <div class="post-meta">
          <?= avatar_html($m, '', 'avatar-lg') ?>
          <div>
            <h2 style="margin:0 0 4px"><a href="member.php?id=<?= (int)$m['id'] ?>"><?= e($m['full_name']) ?></a></h2>
            <span class="tag tag-ok">&#9733; Mentor</span>
            <span class="tag"><?= e(ucfirst($m['experience_level'])) ?></span>
          </div>
        </div>

        <p><?= e($m['bio'] ?: 'Experienced developer volunteering as a mentor at Django Girls Sierra Leone.') ?></p>

        <?php if ($mSkills): ?>
          <div style="margin-bottom:14px">
            <span class="small" style="display:block;margin-bottom:4px"><strong>Mentors in:</strong></span>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <?php foreach ($mSkills as $sk): ?>
                <span class="tag tag-pink"><?= e($sk) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div style="margin-top:auto">
          <?php if ($m['my_status']): ?>
            <div class="mentor-status-box">
              <span class="tag <?= $m['my_status'] === 'accepted' ? 'tag-ok' : 'tag-pink' ?>">
                <?= $m['my_status'] === 'accepted' ? '&#10003; Connected Mentor' : 'Request ' . e(ucfirst($m['my_status'])) ?>
              </span>
              <?php if ($m['my_message']): ?>
                <div class="small" style="margin-top:6px">Your note: "<?= e($m['my_message']) ?>"</div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <form method="post" class="mentor-req-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="request">
              <input type="hidden" name="mentor_id" value="<?= (int)$m['id'] ?>">
              <label for="msg<?= (int)$m['id'] ?>" class="small">Short note to <?= e(explode(' ', $m['full_name'])[0]) ?>:</label>
              <input id="msg<?= (int)$m['id'] ?>" name="message" maxlength="300" placeholder="What topic or project would you like guidance on?">
              <button class="btn btn-small" type="submit">Request Mentorship</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
