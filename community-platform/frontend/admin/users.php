<?php
require_once __DIR__ . '/../config.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('users.php');
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$admin['id']) {
        flash('You cannot change your own role or status.');
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'toggle') {
            db()->prepare('UPDATE users SET active = 1 - active WHERE id = ?')->execute([$id]);
            flash('Account status updated.');
        } elseif ($action === 'role') {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([($_POST['role'] ?? '') === 'admin' ? 'admin' : 'member', $id]);
            flash('Role updated.');
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('Account deleted along with its posts and registrations.');
        }
    }
    go('users.php');
}

$q = trim($_GET['q'] ?? '');
$s = db()->prepare("SELECT * FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
$s->execute(['%' . $q . '%', '%' . $q . '%']);
$users = $s->fetchAll();

$base = '../';
$pageTitle = 'Admin: users';
require __DIR__ . '/../includes/header.php';
?>
<h1>Administration</h1>
<?php require __DIR__ . '/_nav.php'; ?>
<h2>Manage users (<?= count($users) ?>)</h2>
<form method="get" class="inline search"><input name="q" value="<?= e($q) ?>" placeholder="Search by name or email" aria-label="Search users"> <button class="btn btn-small" type="submit">Search</button></form>
<div class="scroll"><table class="table"><thead><tr><th>Member</th><th>Role</th><th>Joined</th><th>Last login</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($users as $u): $self = (int)$u['id'] === (int)$admin['id']; ?>
  <tr>
    <td><a href="../member.php?id=<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?></a><div class="small"><?= e($u['email']) ?></div></td>
    <td><?= e(ucfirst($u['role'])) ?><?= $u['is_mentor'] ? ' (mentor)' : '' ?></td>
    <td><?= e(fmt($u['created_at'], 'j M Y')) ?></td>
    <td><?= $u['last_login'] ? e(fmt($u['last_login'], 'j M Y')) : 'Never' ?></td>
    <td><?= $u['active'] ? 'Active' : 'Deactivated' ?></td>
    <td><?php if ($self): ?><span class="small">This is you</span><?php else: ?>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
        <button class="btn btn-ghost btn-small" name="action" value="toggle"><?= $u['active'] ? 'Deactivate' : 'Reactivate' ?></button>
        <button class="btn btn-ghost btn-small" name="action" value="role" onclick="this.form.role.value='<?= $u['role'] === 'admin' ? 'member' : 'admin' ?>'"><?= $u['role'] === 'admin' ? 'Make member' : 'Make admin' ?></button>
        <input type="hidden" name="role" value="">
        <button class="btn btn-ghost btn-small" name="action" value="delete" onclick="return confirm('Permanently delete this account and everything it posted?')">Delete</button>
      </form><?php endif; ?></td>
  </tr>
<?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
