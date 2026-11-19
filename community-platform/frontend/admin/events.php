<?php
require_once __DIR__ . '/../config.php';
$admin = require_admin();
$errors = [];
$f = ['title' => '', 'description' => '', 'type' => 'Workshop', 'topic' => 'General', 'event_date' => '', 'location' => '', 'capacity' => 30];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('events.php');
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        foreach (['title', 'description', 'location', 'event_date'] as $k) { $f[$k] = trim($_POST[$k] ?? ''); }
        $f['type']  = in_array($_POST['type'] ?? '', EVENT_TYPES, true) ? $_POST['type'] : 'Event';
        $f['topic'] = in_array($_POST['topic'] ?? '', INTERESTS, true) ? $_POST['topic'] : 'General';
        $f['capacity'] = (int)($_POST['capacity'] ?? 0);
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $f['event_date']);
        if ($f['title'] === '' || mb_strlen($f['title']) > 150) { $errors[] = 'Enter a title (up to 150 characters).'; }
        if ($f['description'] === '') { $errors[] = 'Enter a description.'; }
        if ($f['location'] === '') { $errors[] = 'Enter a location.'; }
        if (!$dt) { $errors[] = 'Choose a valid date and time.'; }
        if ($f['capacity'] < 1 || $f['capacity'] > 1000) { $errors[] = 'Capacity must be between 1 and 1000.'; }
        if (!$errors) {
            db()->prepare('INSERT INTO events (title,description,type,topic,event_date,location,capacity) VALUES (?,?,?,?,?,?,?)')
                ->execute([$f['title'], $f['description'], $f['type'], $f['topic'], $dt->format('Y-m-d H:i'), $f['location'], $f['capacity']]);
            flash('Event created.');
            go('events.php');
        }
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM events WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('Event deleted.'); go('events.php');
    } elseif ($action === 'status') {
        $st = in_array($_POST['status'] ?? '', ['registered', 'attended'], true) ? $_POST['status'] : 'registered';
        db()->prepare('UPDATE event_registrations SET status = ? WHERE id = ? AND status != ?')->execute([$st, (int)($_POST['reg_id'] ?? 0), 'cancelled']);
        go('events.php?id=' . (int)($_POST['id'] ?? 0));
    }
}

$viewId = (int)($_GET['id'] ?? 0);
$attendees = []; $viewEvent = null;
if ($viewId) {
    $s = db()->prepare('SELECT * FROM events WHERE id = ?'); $s->execute([$viewId]); $viewEvent = $s->fetch();
    if ($viewEvent) {
        $s = db()->prepare("SELECT r.id, r.status, r.created_at, u.full_name, u.email FROM event_registrations r JOIN users u ON u.id = r.user_id WHERE r.event_id = ? AND r.status != 'cancelled' ORDER BY u.full_name");
        $s->execute([$viewId]); $attendees = $s->fetchAll();
    }
}
$events = db()->query("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id AND r.status != 'cancelled') AS regs FROM events e ORDER BY e.event_date DESC")->fetchAll();

$base = '../';
$pageTitle = 'Admin: events';
require __DIR__ . '/../includes/header.php';
?>
<h1>Administration</h1>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if ($viewEvent): ?>
  <h2>Attendance: <?= e($viewEvent['title']) ?></h2>
  <p><a href="events.php">Back to all events</a></p>
  <?php if (!$attendees): ?><p>Nobody has registered yet.</p><?php else: ?>
  <table class="table"><thead><tr><th>Member</th><th>Email</th><th>Status</th><th>Mark</th></tr></thead><tbody>
  <?php foreach ($attendees as $a): ?>
    <tr><td><?= e($a['full_name']) ?></td><td><?= e($a['email']) ?></td><td><?= e(ucfirst($a['status'])) ?></td>
    <td><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $viewId ?>"><input type="hidden" name="reg_id" value="<?= (int)$a['id'] ?>">
      <?php if ($a['status'] === 'attended'): ?><button class="btn btn-ghost btn-small" name="status" value="registered">Undo attended</button>
      <?php else: ?><button class="btn btn-small" name="status" value="attended">Mark attended</button><?php endif; ?></form></td></tr>
  <?php endforeach; ?></tbody></table><?php endif; ?>
<?php else: ?>
  <h2>Manage events</h2>
  <div class="scroll"><table class="table"><thead><tr><th>Event</th><th>Date</th><th>Registered</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($events as $ev): ?>
    <tr><td><?= e($ev['title']) ?><div class="small"><?= e($ev['type']) ?>, <?= e($ev['topic']) ?></div></td><td><?= e(fmt($ev['event_date'])) ?></td>
    <td><?= (int)$ev['regs'] ?> / <?= (int)$ev['capacity'] ?></td>
    <td><a class="btn btn-ghost btn-small" href="events.php?id=<?= (int)$ev['id'] ?>">Attendance</a>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
      <button class="btn btn-ghost btn-small" onclick="return confirm('Delete this event and its registrations?')">Delete</button></form></td></tr>
  <?php endforeach; ?></tbody></table></div>

  <section class="card" style="margin-top:28px;max-width:640px">
    <h2>Create an event</h2>
    <?php foreach ($errors as $err): ?><p class="error"><?= e($err) ?></p><?php endforeach; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
      <label for="title">Title</label><input id="title" name="title" value="<?= e($f['title']) ?>" required>
      <label for="description">Description</label><textarea id="description" name="description" rows="3" required><?= e($f['description']) ?></textarea>
      <label for="type">Type</label><select id="type" name="type"><?php foreach (EVENT_TYPES as $t): ?><option <?= $t === $f['type'] ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
      <label for="topic">Topic</label><select id="topic" name="topic"><option>General</option><?php foreach (INTERESTS as $t): ?><option <?= $t === $f['topic'] ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
      <label for="event_date">Date and time</label><input id="event_date" name="event_date" type="datetime-local" value="<?= e($f['event_date']) ?>" required>
      <label for="location">Location</label><input id="location" name="location" value="<?= e($f['location']) ?>" required>
      <label for="capacity">Capacity</label><input id="capacity" name="capacity" type="number" min="1" max="1000" value="<?= (int)$f['capacity'] ?>" required>
      <button class="btn" type="submit">Create event</button>
    </form>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
