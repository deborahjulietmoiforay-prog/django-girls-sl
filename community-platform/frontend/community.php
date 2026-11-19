<?php
require_once __DIR__ . '/config.php';
$me = require_login();
$errors = [];
$title = $body = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('community.php');
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');
    if ($title === '' || mb_strlen($title) > 150) {
        $errors[] = 'Give your topic a title of up to 150 characters.';
    }
    if ($body === '' || mb_strlen($body) > 5000) {
        $errors[] = 'Write a message of up to 5000 characters.';
    }
    if (!$errors) {
        db()->prepare('INSERT INTO topics (user_id, title, body) VALUES (?, ?, ?)')->execute([$me['id'], $title, $body]);
        flash('Your discussion topic has been posted!');
        go('topic.php?id=' . db()->lastInsertId());
    }
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT t.id, t.user_id, t.title, t.body, t.created_at, u.full_name, u.avatar, u.role, u.is_mentor,
        (SELECT COUNT(*) FROM replies r WHERE r.topic_id = t.id) AS n,
        COALESCE((SELECT MAX(r.created_at) FROM replies r WHERE r.topic_id = t.id), t.created_at) AS last_activity
        FROM topics t JOIN users u ON u.id = t.user_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE t.title LIKE ? OR t.body LIKE ?';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= ' ORDER BY last_activity DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$topics = $stmt->fetchAll();

$pageTitle = 'Community Forum';
require __DIR__ . '/includes/header.php';
?>

<div class="section-head-row">
  <div>
    <span class="eyebrow">Peer Collaboration &amp; Discussion Board</span>
    <h1 style="margin:4px 0">Community Forum</h1>
    <p class="lead" style="margin-bottom:0">Ask coding questions, share your web projects, and collaborate with fellow Django Girls Sierra Leone members.</p>
  </div>
  <a class="btn btn-small" href="#new-topic">+ Start New Topic</a>
</div>

<div class="directory-toolbar">
  <span class="small"><strong><?= count($topics) ?></strong> active discussion topic<?= count($topics) === 1 ? '' : 's' ?></span>
  <form method="get" class="inline search" style="margin:0">
    <input name="q" value="<?= e($q) ?>" placeholder="Search forum discussions..." aria-label="Search forum">
    <button class="btn btn-small" type="submit">Search</button>
  </form>
</div>

<?php if ($topics): ?>
  <div class="forum-list">
    <?php foreach ($topics as $t): ?>
      <article class="card forum-row">
        <div class="forum-row-main">
          <?= avatar_html($t, '', 'avatar-lg') ?>
          <div>
            <h2 style="margin:0 0 4px;font-size:1.15rem">
              <a href="topic.php?id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></a>
            </h2>
            <p class="small" style="margin:0 0 6px;color:var(--muted)">
              <?= e(mb_strimwidth($t['body'], 0, 140, '...')) ?>
            </p>
            <div class="small">
              Started by <a href="member.php?id=<?= (int)$t['user_id'] ?>"><strong><?= e($t['full_name']) ?></strong></a>
              <?php if ($t['is_mentor']): ?><span class="tag tag-ok" style="font-size:.7rem">Mentor</span><?php endif; ?>
              &bull; Last activity <?= e(fmt($t['last_activity'], 'j M Y, H:i')) ?>
            </div>
          </div>
        </div>
        <div class="forum-row-stats">
          <a href="topic.php?id=<?= (int)$t['id'] ?>" class="reply-pill">
            <strong><?= (int)$t['n'] ?></strong>
            <span><?= (int)$t['n'] === 1 ? 'Reply' : 'Replies' ?></span>
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <section class="card"><p>No topics matched your search. Start a new conversation below!</p></section>
<?php endif; ?>

<section class="card" id="new-topic" style="margin-top:28px">
  <h2><?= icon('message-square', 'icon-sm') ?> Start a New Discussion Topic</h2>
  <p class="small">Have a question about PHP, Python, Django, HTML/CSS, or an upcoming workshop? Post it below.</p>
  <?php foreach ($errors as $err): ?>
    <p class="error" role="alert"><?= e($err) ?></p>
  <?php endforeach; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label for="title">Discussion Title</label>
    <input id="title" name="title" maxlength="150" value="<?= e($title) ?>" placeholder="e.g. How do I connect PDO to SQLite in my PHP project?" required>
    <label for="body">Message</label>
    <textarea id="body" name="body" rows="5" maxlength="5000" placeholder="Share details, code snippets, or questions..." required><?= e($body) ?></textarea>
    <button class="btn" type="submit">Post Topic to Forum</button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
