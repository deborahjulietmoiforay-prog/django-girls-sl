<?php
require_once __DIR__ . '/config.php';
$me = require_login();
$tid = (int)($_GET['id'] ?? 0);
$back = 'topic.php?id=' . $tid;

$s = db()->prepare('SELECT t.*, u.full_name, u.avatar FROM topics t JOIN users u ON u.id = t.user_id WHERE t.id = ?');
$s->execute([$tid]);
$topic = $s->fetch();
if (!$topic) { flash('That topic does not exist.'); go('community.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard($back);
    $action = $_POST['action'] ?? 'reply';
    if ($action === 'reply') {
        $body = trim($_POST['body'] ?? '');
        if ($body === '' || mb_strlen($body) > 3000) { $error = 'Write a reply of up to 3000 characters.'; }
        else {
            db()->prepare('INSERT INTO replies (topic_id, user_id, body) VALUES (?, ?, ?)')->execute([$tid, $me['id'], $body]);
            go($back);
        }
    } elseif ($me['role'] === 'admin' && $action === 'delete_topic') {
        db()->prepare('DELETE FROM topics WHERE id = ?')->execute([$tid]);
        flash('Topic deleted.'); go('community.php');
    } elseif ($me['role'] === 'admin' && $action === 'delete_reply') {
        db()->prepare('DELETE FROM replies WHERE id = ? AND topic_id = ?')->execute([(int)($_POST['reply_id'] ?? 0), $tid]);
        flash('Reply deleted.'); go($back);
    }
}

$s = db()->prepare('SELECT r.*, u.full_name, u.avatar FROM replies r JOIN users u ON u.id = r.user_id WHERE r.topic_id = ? ORDER BY r.id');
$s->execute([$tid]);
$replies = $s->fetchAll();

$pageTitle = $topic['title'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="community.php">Back to the forum</a></p>
<h1><?= e($topic['title']) ?></h1>

<article class="card post">
  <div class="post-meta"><?= avatar_html($topic, '', 'avatar-sm') ?> <a href="member.php?id=<?= (int)$topic['user_id'] ?>"><?= e($topic['full_name']) ?></a>
    <span class="small"><?= e(fmt($topic['created_at'])) ?></span></div>
  <p><?= nl2br(e($topic['body'])) ?></p>
  <?php if ($me['role'] === 'admin'): ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete_topic">
      <button class="btn btn-ghost btn-small" type="submit" onclick="return confirm('Delete this topic and all its replies?')">Delete topic</button></form>
  <?php endif; ?>
</article>

<h2 class="section-title"><?= count($replies) ?> repl<?= count($replies) === 1 ? 'y' : 'ies' ?></h2>
<?php foreach ($replies as $r): ?>
  <article class="card post">
    <div class="post-meta"><?= avatar_html($r, '', 'avatar-sm') ?> <a href="member.php?id=<?= (int)$r['user_id'] ?>"><?= e($r['full_name']) ?></a>
      <span class="small"><?= e(fmt($r['created_at'])) ?></span></div>
    <p><?= nl2br(e($r['body'])) ?></p>
    <?php if ($me['role'] === 'admin'): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete_reply"><input type="hidden" name="reply_id" value="<?= (int)$r['id'] ?>">
        <button class="btn btn-ghost btn-small" type="submit" onclick="return confirm('Delete this reply?')">Delete reply</button></form>
    <?php endif; ?>
  </article>
<?php endforeach; ?>

<section class="card" style="margin-top:20px">
  <h2>Add a reply</h2>
  <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="reply">
    <label for="body">Your reply</label><textarea id="body" name="body" rows="4" maxlength="3000" required></textarea>
    <button class="btn" type="submit">Post reply</button></form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
