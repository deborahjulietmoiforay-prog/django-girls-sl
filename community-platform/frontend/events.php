<?php
require_once __DIR__ . '/config.php';
$me   = require_login();
$uid  = (int)$me['id'];
$mine = user_interests($me);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('events.php');
    $eid = (int)($_POST['event_id'] ?? 0);
    $s = db()->prepare('SELECT * FROM events WHERE id = ?');
    $s->execute([$eid]);
    $ev = $s->fetch();

    if (!$ev) {
        flash('That event no longer exists.');
    } elseif (($_POST['action'] ?? '') === 'register') {
        $s = db()->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status != 'cancelled'");
        $s->execute([$eid]);
        if ($ev['event_date'] < now()) {
            flash('This event has already taken place.');
        } elseif ((int)$s->fetchColumn() >= (int)$ev['capacity']) {
            flash('Sorry, this event is currently full.');
        } else {
            $regCheck = db()->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
            $regCheck->execute([$eid, $uid]);
            if ($regCheck->fetch()) {
                db()->prepare("UPDATE event_registrations SET status = 'registered' WHERE event_id = ? AND user_id = ?")->execute([$eid, $uid]);
            } else {
                db()->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'registered')")->execute([$eid, $uid]);
            }
            flash('You are registered for "' . $ev['title'] . '"!');
        }
    } elseif (($_POST['action'] ?? '') === 'cancel') {
        db()->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE event_id = ? AND user_id = ? AND status = 'registered'")->execute([$eid, $uid]);
        flash('Your registration for "' . $ev['title'] . '" was cancelled.');
    }
    go('events.php');
}

$typeFilter = in_array($_GET['type'] ?? '', EVENT_TYPES, true) ? $_GET['type'] : '';

$s = db()->prepare("SELECT e.*,
        (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id AND r.status != 'cancelled') AS taken,
        (SELECT status FROM event_registrations r WHERE r.event_id = e.id AND r.user_id = ?) AS my_status
    FROM events e ORDER BY e.event_date");
$s->execute([$uid]);
$all = $s->fetchAll();

$upcoming = array_filter($all, fn($e) => $e['event_date'] >= now() && ($typeFilter === '' || $e['type'] === $typeFilter));
$myParticipations = array_filter($all, fn($e) => in_array($e['my_status'], ['registered', 'attended', 'cancelled'], true));

$pageTitle = 'Events and workshops';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-card">
  <div>
    <span class="eyebrow">Workshops &amp; Training Sessions</span>
    <h1>Events &amp; Coding Workshops</h1>
    <p class="lead" style="margin-bottom:0">Join hands-on coding sessions at Limkokwing Lab in Freetown or online. Register in one click and track your participation history below.</p>
  </div>
  <img src="assets/img/classroom.jpg" alt="Students learning with laptops and tablets in a technology class" class="page-hero-img">
</section>

<div class="filters">
  <a class="chip <?= $typeFilter === '' ? 'on' : '' ?>" href="events.php">All Sessions</a>
  <?php foreach (EVENT_TYPES as $t): ?>
    <a class="chip <?= $typeFilter === $t ? 'on' : '' ?>" href="events.php?type=<?= urlencode($t) ?>"><?= e($t) ?>s</a>
  <?php endforeach; ?>
</div>

<?php if (!$upcoming): ?>
  <section class="card"><p>No upcoming events match this filter. <a href="events.php">View all upcoming sessions</a></p></section>
<?php else: ?>
  <div class="grid">
    <?php foreach ($upcoming as $ev):
        $taken = (int)$ev['taken'];
        $cap   = max(1, (int)$ev['capacity']);
        $left  = max(0, $cap - $taken);
        $fillPct = min(100, (int)round($taken / $cap * 100));
        $in    = $ev['my_status'] === 'registered';
    ?>
      <article class="card event-card">
        <div class="event-card-top">
          <span class="tag tag-pink"><?= e($ev['type']) ?></span>
          <span class="tag"><?= e($ev['topic']) ?></span>
          <?php if (in_array($ev['topic'], $mine, true)): ?>
            <span class="tag tag-match">Matches Interest</span>
          <?php endif; ?>
        </div>

        <h2><?= e($ev['title']) ?></h2>
        <p><?= e($ev['description']) ?></p>

        <div class="event-meta">
          <div class="meta-item"><?= icon('calendar', 'icon-sm') ?> <strong>Date &amp; Time:</strong> <?= e(fmt($ev['event_date'])) ?></div>
          <div class="meta-item" style="margin-top:4px"><?= icon('map-pin', 'icon-sm') ?> <strong>Location:</strong> <?= e($ev['location']) ?></div>
        </div>

        <div style="margin:10px 0">
          <div style="display:flex;justify-content:space-between;font-size:.82rem">
            <span><?= $taken ?> registered</span>
            <strong><?= $left > 0 ? $left . ' of ' . $cap . ' seats left' : 'Full' ?></strong>
          </div>
          <div class="bar" style="height:8px;margin:4px 0 0"><span style="width:<?= $fillPct ?>%"></span></div>
        </div>

        <form method="post" style="margin-top:auto">
          <?= csrf_field() ?>
          <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
          <?php if ($in): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px">
              <span class="tag tag-ok"><?= icon('check', 'icon-sm') ?> Registered</span>
              <button class="btn btn-ghost btn-small" name="action" value="cancel" type="submit" style="margin:0;width:auto">Cancel</button>
            </div>
          <?php elseif ($left > 0): ?>
            <button class="btn" name="action" value="register" type="submit">Register for Session</button>
          <?php else: ?>
            <button class="btn" type="button" disabled>Session Full</button>
          <?php endif; ?>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2 class="section-title">My Event Registrations &amp; Participation History</h2>
<?php if (!$myParticipations): ?>
  <section class="card"><p>You have not registered for any events yet. Click <strong>Register for Session</strong> on any workshop above to track your participation here.</p></section>
<?php else: ?>
  <div class="scroll">
    <table class="table">
      <thead>
        <tr>
          <th>Event / Workshop</th>
          <th>Type &amp; Topic</th>
          <th>Date &amp; Time</th>
          <th>Location</th>
          <th>Participation Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($myParticipations as $ev): ?>
          <tr>
            <td><strong><?= e($ev['title']) ?></strong></td>
            <td><span class="tag"><?= e($ev['type']) ?></span> <span class="tag"><?= e($ev['topic']) ?></span></td>
            <td><?= e(fmt($ev['event_date'])) ?></td>
            <td><?= e($ev['location']) ?></td>
            <td>
              <?php if ($ev['my_status'] === 'attended'): ?>
                <span class="tag tag-ok"><?= icon('check', 'icon-sm') ?> Attended</span>
              <?php elseif ($ev['my_status'] === 'registered'): ?>
                <span class="tag tag-pink">Registered (Upcoming)</span>
              <?php else: ?>
                <span class="tag">Cancelled</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
