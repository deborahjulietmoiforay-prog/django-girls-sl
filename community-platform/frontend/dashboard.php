<?php
require_once __DIR__ . '/config.php';
$me   = require_login();
$uid  = (int)$me['id'];
$mine = user_interests($me);

// Training progress = completed learning resources / all resources
$total = (int)db()->query('SELECT COUNT(*) FROM resources')->fetchColumn();
$s = db()->prepare('SELECT COUNT(*) FROM resource_progress WHERE user_id = ?');
$s->execute([$uid]);
$done = (int)$s->fetchColumn();
$pct  = $total ? (int)round($done / $total * 100) : 0;

// Upcoming events, prioritizing topics that match the member's interests
$s = db()->prepare("SELECT e.*,
        (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id AND r.status != 'cancelled') AS taken,
        (SELECT status FROM event_registrations r WHERE r.event_id = e.id AND r.user_id = ?) AS my_status
    FROM events e WHERE e.event_date >= ? ORDER BY e.event_date");
$s->execute([$uid, now()]);
$events = $s->fetchAll();
usort($events, fn($a, $b) => (int)in_array($b['topic'], $mine, true) <=> (int)in_array($a['topic'], $mine, true)
                              ?: strcmp($a['event_date'], $b['event_date']));
$events = array_slice($events, 0, 4);

// Count total events joined by member
$s = db()->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ? AND status != 'cancelled'");
$s->execute([$uid]);
$eventsJoinedCount = (int)$s->fetchColumn();

// Count forum posts by member
$s = db()->prepare('SELECT (SELECT COUNT(*) FROM topics WHERE user_id = ?) + (SELECT COUNT(*) FROM replies WHERE user_id = ?)');
$s->execute([$uid, $uid]);
$myPostsCount = (int)$s->fetchColumn();

// Recommended resources: member's interests first, not completed yet
$s = db()->prepare('SELECT * FROM resources WHERE id NOT IN (SELECT resource_id FROM resource_progress WHERE user_id = ?)');
$s->execute([$uid]);
$todo = $s->fetchAll();
usort($todo, fn($a, $b) => (int)in_array($b['category'], $mine, true) <=> (int)in_array($a['category'], $mine, true));
$todo = array_slice($todo, 0, 4);

// Mentorship connections
$s = db()->prepare("SELECT u.id, u.full_name, u.avatar, u.interests FROM mentorships m JOIN users u ON u.id = m.mentor_id WHERE m.mentee_id = ? AND m.status = 'accepted'");
$s->execute([$uid]);
$mentors = $s->fetchAll();

$s = db()->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND status = 'pending'");
$s->execute([$uid]);
$pendingReq = (int)$s->fetchColumn();

// Latest community discussions
$topics = db()->query('SELECT t.id, t.title, t.created_at, u.full_name, u.avatar,
        (SELECT COUNT(*) FROM replies r WHERE r.topic_id = t.id) AS n
    FROM topics t JOIN users u ON u.id = t.user_id ORDER BY t.id DESC LIMIT 4')->fetchAll();

// Assignment 1 Next Steps Checklist (Interactive evolution!)
$stepProfile   = !empty($mine) && trim($me['bio'] ?? '') !== '';
$stepTutorial  = $done > 0;
$stepWorkshop  = $eventsJoinedCount > 0;
$stepCommunity = $myPostsCount > 0 || count($mentors) > 0;
$stepsDone     = (int)$stepProfile + (int)$stepTutorial + (int)$stepWorkshop + (int)$stepCommunity;

$hour  = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$first = explode(' ', $me['full_name'])[0];
$dbDriver = db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? 'MySQL' : 'SQLite';

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="dash-banner">
  <div class="dash-banner-main">
    <?= avatar_html($me, '', 'avatar-xl') ?>
    <div class="dash-banner-info">
      <div class="dash-badges">
        <span class="tag tag-light"><?= e(ucfirst($me['role'])) ?></span>
        <span class="tag tag-light"><?= e(ucfirst($me['experience_level'])) ?> Level</span>
        <span class="tag tag-light" style="background:rgba(255,255,255,0.22);color:#fff;border-color:rgba(255,255,255,0.4)" title="Connected to active database"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#34d399;margin-right:6px;box-shadow:0 0 6px #34d399"></span>DB: <?= $dbDriver ?> (djangogirls_sl)</span>
        <?php if ($me['is_mentor']): ?><span class="tag tag-pink">Community Mentor</span><?php endif; ?>
      </div>
      <h1><?= e($greet) ?>, <?= e($first) ?>!</h1>
      <p class="dash-sub">
        <?php if ($mine): ?>
          Personalized for your interests:
          <?php foreach ($mine as $interest): ?>
            <span class="interest-chip"><?= e($interest) ?></span>
          <?php endforeach; ?>
        <?php else: ?>
          Add your coding interests in your profile to unlock personalized workshop and tutorial recommendations.
        <?php endif; ?>
      </p>
    </div>
  </div>
  <div class="dash-banner-actions">
    <a class="btn btn-small" href="profile.php"><?= icon('edit', 'icon-sm') ?> Edit Profile</a>
    <a class="btn btn-small btn-outline-light" href="member.php?id=<?= $uid ?>">Public Profile</a>
  </div>
</section>

<!-- KPI Summary Strip -->
<div class="stats">
  <div class="stat">
    <strong><?= $pct ?>%</strong>
    <span>Training Progress (<?= $done ?>/<?= $total ?>)</span>
  </div>
  <div class="stat">
    <strong><?= $eventsJoinedCount ?></strong>
    <span>Workshops &amp; Events Joined</span>
  </div>
  <div class="stat">
    <strong><?= $myPostsCount ?></strong>
    <span>Community Forum Posts</span>
  </div>
  <div class="stat">
    <strong><?= count($mentors) ?></strong>
    <span>Active Mentor<?= count($mentors) === 1 ? '' : 's' ?></span>
  </div>
</div>

<div class="grid">
  <!-- Training Progress Card -->
  <section class="card">
    <div class="card-head">
      <h2><?= icon('award', 'icon-sm') ?> Training Progress</h2>
      <span class="tag tag-ok"><?= $pct ?>% Complete</span>
    </div>
    <p>You have completed <strong><?= $done ?> of <?= $total ?></strong> curated learning resources.</p>
    <div class="bar" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
      <span style="width:<?= $pct ?>%"></span>
    </div>
    <p class="small">Keep marking tutorials complete on the Resources page as you finish them.</p>
    <div style="margin-top:14px">
      <a class="btn btn-small" href="resources.php">Continue Learning &rarr;</a>
    </div>
  </section>

  <!-- Evolved from Assignment 1: Interactive Next Steps Checklist -->
  <section class="card">
    <div class="card-head">
      <h2><?= icon('check-circle', 'icon-sm') ?> Next Steps Journey</h2>
      <span class="tag"><?= $stepsDone ?>/4 Done</span>
    </div>
    <ul class="checklist-interactive">
      <li class="<?= $stepProfile ? 'done' : '' ?>">
        <span class="chk-box"><?= $stepProfile ? icon('check', 'icon-sm') : '1' ?></span>
        <div>
          <a href="profile.php">Complete your profile &amp; learning interests</a>
        </div>
      </li>
      <li class="<?= $stepTutorial ? 'done' : '' ?>">
        <span class="chk-box"><?= $stepTutorial ? icon('check', 'icon-sm') : '2' ?></span>
        <div>
          <a href="resources.php">Complete the Django Girls tutorial</a>
        </div>
      </li>
      <li class="<?= $stepWorkshop ? 'done' : '' ?>">
        <span class="chk-box"><?= $stepWorkshop ? icon('check', 'icon-sm') : '3' ?></span>
        <div>
          <a href="events.php">Join a local Freetown or online workshop</a>
        </div>
      </li>
      <li class="<?= $stepCommunity ? 'done' : '' ?>">
        <span class="chk-box"><?= $stepCommunity ? icon('check', 'icon-sm') : '4' ?></span>
        <div>
          <a href="community.php">Introduce yourself in the community forum</a>
        </div>
      </li>
    </ul>
  </section>

  <!-- Evolved from Assignment 1: Account Activity & Profile Summary -->
  <section class="card">
    <div class="card-head">
      <h2><?= icon('user', 'icon-sm') ?> Account Activity</h2>
      <span class="tag">Assignment 1 Auth</span>
    </div>
    <dl class="meta-dl">
      <dt>Full Name</dt>
      <dd><?= e($me['full_name']) ?></dd>
      <dt>Email Address</dt>
      <dd><?= e($me['email']) ?></dd>
      <dt>Member Since</dt>
      <dd><?= e(fmt($me['created_at'], 'j F Y')) ?></dd>
      <dt>Last Login</dt>
      <dd><?= $me['last_login'] ? e(fmt($me['last_login'], 'j M Y, H:i') . ' UTC') : 'First session active' ?></dd>
    </dl>
  </section>

  <!-- Personalized Upcoming Events & Workshops -->
  <section class="card span-2">
    <div class="card-head">
      <h2><?= icon('calendar', 'icon-sm') ?> Upcoming Events &amp; Workshops for You</h2>
      <a class="small" href="events.php">View all &rarr;</a>
    </div>
    <?php if (!$events): ?>
      <p>No upcoming events right now. Check back soon.</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($events as $ev):
          $left = max(0, (int)$ev['capacity'] - (int)$ev['taken']);
        ?>
          <li>
            <div>
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <a href="events.php" style="font-weight:bold;font-size:1.02rem"><?= e($ev['title']) ?></a>
                <span class="tag"><?= e($ev['type']) ?></span>
                <?php if (in_array($ev['topic'], $mine, true)): ?>
                  <span class="tag tag-match">Matches interest: <?= e($ev['topic']) ?></span>
                <?php endif; ?>
              </div>
              <div class="small" style="margin-top:6px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <span class="meta-item"><?= icon('calendar', 'icon-sm') ?> <?= e(fmt($ev['event_date'])) ?></span>
                <span class="meta-item"><?= icon('map-pin', 'icon-sm') ?> <?= e($ev['location']) ?></span>
                <span><?= $left ?> seats left</span>
              </div>
            </div>
            <div>
              <?php if (in_array($ev['my_status'], ['registered', 'attended'], true)): ?>
                <span class="tag tag-ok"><?= icon('check', 'icon-sm') ?> Registered</span>
              <?php else: ?>
                <form method="post" action="events.php" class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
                  <button class="btn btn-small" name="action" value="register" type="submit">Register</button>
                </form>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- Mentorship Card -->
  <section class="card">
    <div class="card-head">
      <h2><?= icon('users', 'icon-sm') ?> Mentorship</h2>
      <a class="small" href="mentors.php">Directory &rarr;</a>
    </div>
    <?php if ($mentors): ?>
      <p class="small">Your connected mentor<?= count($mentors) > 1 ? 's' : '' ?>:</p>
      <ul class="list">
        <?php foreach ($mentors as $m): ?>
          <li style="align-items:center">
            <div style="display:flex;align-items:center;gap:10px">
              <?= avatar_html($m, '', 'avatar-sm') ?>
              <div>
                <a href="member.php?id=<?= (int)$m['id'] ?>"><strong><?= e($m['full_name']) ?></strong></a>
                <div class="small"><?= e(str_replace(',', ', ', $m['interests'])) ?></div>
              </div>
            </div>
            <span class="tag tag-ok">Connected</span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Get 1-on-1 coding advice, project feedback, and career guidance from an experienced developer.</p>
      <a class="btn btn-small btn-ghost" href="mentors.php">Find a Mentor &rarr;</a>
    <?php endif; ?>

    <?php if ($me['is_mentor'] && $pendingReq): ?>
      <div class="notice" style="margin-top:12px;margin-bottom:0">
        <a href="mentors.php"><strong><?= $pendingReq ?> mentorship request<?= $pendingReq > 1 ? 's' : '' ?></strong> waiting for your response &rarr;</a>
      </div>
    <?php endif; ?>
  </section>

  <!-- Personalized Learning Recommendations -->
  <section class="card">
    <div class="card-head">
      <h2><?= icon('book', 'icon-sm') ?> Recommended Tutorials</h2>
      <a class="small" href="resources.php">Library &rarr;</a>
    </div>
    <?php if (!$todo): ?>
      <p>Congratulations! You have completed every learning resource in the library!</p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($todo as $r): ?>
          <li>
            <div>
              <a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><strong><?= e($r['title']) ?></strong> <?= icon('external-link', 'icon-sm') ?></a>
              <div class="small" style="margin-top:3px">
                <span class="tag"><?= e($r['category']) ?></span>
                <span class="tag"><?= e(ucfirst($r['level'])) ?></span>
                <?php if (in_array($r['category'], $mine, true)): ?>
                  <span class="tag tag-match">For you</span>
                <?php endif; ?>
              </div>
            </div>
            <form method="post" action="resources.php" class="inline">
              <?= csrf_field() ?>
              <input type="hidden" name="resource_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-small btn-ghost" name="action" value="done" type="submit" title="Mark as completed"><?= icon('check', 'icon-sm') ?> Done</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- Latest Discussions in the Community -->
  <section class="card span-2">
    <div class="card-head">
      <h2><?= icon('message-square', 'icon-sm') ?> Latest in the Community Forum</h2>
      <a class="btn btn-small btn-ghost" href="community.php">Open Forum &rarr;</a>
    </div>
    <?php if (!$topics): ?>
      <p>No discussions yet. <a href="community.php">Start the first conversation!</a></p>
    <?php else: ?>
      <ul class="list">
        <?php foreach ($topics as $t): ?>
          <li style="align-items:center">
            <div style="display:flex;align-items:center;gap:12px">
              <?= avatar_html($t, '', 'avatar-sm') ?>
              <div>
                <a href="topic.php?id=<?= (int)$t['id'] ?>"><strong><?= e($t['title']) ?></strong></a>
                <div class="small">Started by <?= e($t['full_name']) ?> &bull; <?= e(fmt($t['created_at'], 'j M Y')) ?></div>
              </div>
            </div>
            <span class="tag"><?= (int)$t['n'] ?> repl<?= (int)$t['n'] === 1 ? 'y' : 'ies' ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
