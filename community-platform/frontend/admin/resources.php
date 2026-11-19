<?php
require_once __DIR__ . '/../config.php';
$admin = require_admin();
$errors = [];
$f = ['title' => '', 'description' => '', 'category' => INTERESTS[0], 'level' => 'beginner', 'url' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('resources.php');
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM resources WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('Resource deleted.'); go('resources.php');
    }
    foreach (['title', 'description', 'url'] as $k) { $f[$k] = trim($_POST[$k] ?? ''); }
    $f['category'] = in_array($_POST['category'] ?? '', INTERESTS, true) ? $_POST['category'] : INTERESTS[0];
    $f['level']    = in_array($_POST['level'] ?? '', LEVELS, true) ? $_POST['level'] : 'beginner';
    if ($f['title'] === '' || mb_strlen($f['title']) > 150) { $errors[] = 'Enter a title (up to 150 characters).'; }
    if ($f['description'] === '') { $errors[] = 'Enter a short description.'; }
    if (!preg_match('#^https?://#i', $f['url']) || !filter_var($f['url'], FILTER_VALIDATE_URL)) { $errors[] = 'Enter a full link starting with http:// or https://.'; }
    if (!$errors) {
        db()->prepare('INSERT INTO resources (title,description,category,level,url) VALUES (?,?,?,?,?)')
            ->execute([$f['title'], $f['description'], $f['category'], $f['level'], $f['url']]);
        flash('Resource added.'); go('resources.php');
    }
}
$list = db()->query('SELECT * FROM resources ORDER BY category, id')->fetchAll();

$base = '../';
$pageTitle = 'Admin: resources';
require __DIR__ . '/../includes/header.php';
?>
<h1>Administration</h1>
<?php require __DIR__ . '/_nav.php'; ?>
<h2>Manage learning resources</h2>
<div class="scroll"><table class="table"><thead><tr><th>Title</th><th>Category</th><th>Level</th><th></th></tr></thead><tbody>
<?php foreach ($list as $r): ?>
  <tr><td><a href="<?= e($r['url']) ?>" target="_blank" rel="noopener"><?= e($r['title']) ?></a></td><td><?= e($r['category']) ?></td><td><?= e(ucfirst($r['level'])) ?></td>
  <td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <button class="btn btn-ghost btn-small" onclick="return confirm('Delete this resource?')">Delete</button></form></td></tr>
<?php endforeach; ?></tbody></table></div>

<section class="card" style="margin-top:28px;max-width:640px">
  <h2>Add a resource</h2>
  <?php foreach ($errors as $err): ?><p class="error"><?= e($err) ?></p><?php endforeach; ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <label for="title">Title</label><input id="title" name="title" value="<?= e($f['title']) ?>" required>
    <label for="description">Description</label><textarea id="description" name="description" rows="2" required><?= e($f['description']) ?></textarea>
    <label for="category">Category</label><select id="category" name="category"><?php foreach (INTERESTS as $c): ?><option <?= $c === $f['category'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
    <label for="level">Level</label><select id="level" name="level"><?php foreach (LEVELS as $l): ?><option value="<?= e($l) ?>" <?= $l === $f['level'] ? 'selected' : '' ?>><?= e(ucfirst($l)) ?></option><?php endforeach; ?></select>
    <label for="url">Link</label><input id="url" name="url" type="url" value="<?= e($f['url']) ?>" placeholder="https://" required>
    <button class="btn" type="submit">Add resource</button>
  </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
