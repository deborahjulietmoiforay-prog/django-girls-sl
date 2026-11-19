<?php
require_once __DIR__ . '/../config.php';
$admin = require_admin();
$pdo = db();
$one = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();

// ---- CSV exports (sent before any HTML) ----
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    if ($type === 'members') {
        $head = ['Name', 'Email', 'Role', 'Level', 'Interests', 'Mentor', 'Active', 'Joined', 'Last login'];
        $rows = $pdo->query('SELECT full_name,email,role,experience_level,interests,is_mentor,active,created_at,last_login FROM users ORDER BY id')->fetchAll(PDO::FETCH_NUM);
    } elseif ($type === 'events') {
        $head = ['Event', 'Date', 'Member', 'Email', 'Status'];
        $rows = $pdo->query('SELECT e.title,e.event_date,u.full_name,u.email,r.status FROM event_registrations r JOIN events e ON e.id=r.event_id JOIN users u ON u.id=r.user_id ORDER BY e.event_date, u.full_name')->fetchAll(PDO::FETCH_NUM);
    } else {
        go('index.php');
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $head);
    foreach ($rows as $r) {
        $r = array_map(fn($c) => is_string($c) && preg_match('/^[=+\-@]/', $c) ? "'" . $c : $c, $r);
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

$members     = $one("SELECT COUNT(*) FROM users WHERE role = 'member'");
$active      = $one("SELECT COUNT(*) FROM users WHERE active = 1");
$past30      = date('Y-m-d H:i:s', strtotime('-30 days'));
$s30 = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= ?");
$s30->execute([$past30]);
$new30       = (int)$s30->fetchColumn();
$mentorsN    = $one('SELECT COUNT(*) FROM users WHERE is_mentor = 1');
$eventsN     = $one('SELECT COUNT(*) FROM events');
$regs        = $one("SELECT COUNT(*) FROM event_registrations WHERE status != 'cancelled'");
$attended    = $one("SELECT COUNT(*) FROM event_registrations WHERE status = 'attended'");
$pastRegs    = $one("SELECT COUNT(*) FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.status != 'cancelled' AND e.event_date < '" . now() . "'");
$attendRate  = $pastRegs ? round($attended / $pastRegs * 100) : 0;
$topicsN     = $one('SELECT COUNT(*) FROM topics');
$repliesN    = $one('SELECT COUNT(*) FROM replies');
$accepted    = $one("SELECT COUNT(*) FROM mentorships WHERE status = 'accepted'");
$pendingM    = $one("SELECT COUNT(*) FROM mentorships WHERE status = 'pending'");
$completions = $one('SELECT COUNT(*) FROM resource_progress');

$signups = $pdo->query("SELECT SUBSTR(created_at, 1, 7) AS m, COUNT(*) AS n FROM users GROUP BY m ORDER BY m DESC LIMIT 6")->fetchAll();
$engagement = $pdo->query("SELECT e.id, e.title, e.event_date, e.capacity,
    SUM(CASE WHEN r.status != 'cancelled' THEN 1 ELSE 0 END) AS regs,
    SUM(CASE WHEN r.status = 'attended' THEN 1 ELSE 0 END) AS att
    FROM events e LEFT JOIN event_registrations r ON r.event_id = e.id GROUP BY e.id ORDER BY e.event_date DESC")->fetchAll();
$active_members = $pdo->query("SELECT u.id, u.full_name, u.avatar,
    (SELECT COUNT(*) FROM topics t WHERE t.user_id = u.id) + (SELECT COUNT(*) FROM replies r WHERE r.user_id = u.id) AS posts,
    (SELECT COUNT(*) FROM event_registrations er WHERE er.user_id = u.id AND er.status != 'cancelled') AS evs,
    (SELECT COUNT(*) FROM resource_progress rp WHERE rp.user_id = u.id) AS res
    FROM users u WHERE u.active = 1 ORDER BY (posts + evs + res) DESC, u.full_name LIMIT 6")->fetchAll();
$resourceStats = $pdo->query('SELECT r.title, r.category, COUNT(p.user_id) AS n FROM resources r LEFT JOIN resource_progress p ON p.resource_id = r.id GROUP BY r.id ORDER BY n DESC')->fetchAll();

$interestCount = array_fill_keys(INTERESTS, 0);
foreach ($pdo->query("SELECT interests FROM users WHERE interests != ''")->fetchAll(PDO::FETCH_COLUMN) as $csv) {
    foreach (explode(',', $csv) as $i) {
        if (isset($interestCount[$i])) {
            $interestCount[$i]++;
        }
    }
}
arsort($interestCount);
$maxInterest = max(1, max($interestCount));

$base = '../';
$pageTitle = 'Admin: Reports & Analytics';
require __DIR__ . '/../includes/header.php';
?>

<div class="section-head-row">
  <div>
    <span class="eyebrow">Platform Administration</span>
    <h1 style="margin:4px 0">Engagement Reports &amp; Analytics</h1>
  </div>
  <div class="actions" style="margin:0">
    <a class="btn btn-small" href="index.php?export=members"><?= icon('download', 'icon-sm') ?> Export Members (CSV)</a>
    <a class="btn btn-small btn-ghost" href="index.php?export=events"><?= icon('download', 'icon-sm') ?> Export Event Registrations (CSV)</a>
    <button class="btn btn-small btn-outline" type="button" onclick="window.print()"><?= icon('printer', 'icon-sm') ?> Print Report</button>
  </div>
</div>

<?php require __DIR__ . '/_nav.php'; ?>

<div class="stats">
  <div class="stat"><strong><?= $active ?></strong><span>Active Accounts (<?= $members ?> Members)</span></div>
  <div class="stat"><strong><?= $new30 ?></strong><span>Joined in Last 30 Days</span></div>
  <div class="stat"><strong><?= $mentorsN ?></strong><span>Community Mentors</span></div>
  <div class="stat"><strong><?= $regs ?></strong><span>Event Registrations</span></div>
  <div class="stat"><strong><?= $attendRate ?>%</strong><span>Attendance Rate (Past Events)</span></div>
  <div class="stat"><strong><?= $topicsN + $repliesN ?></strong><span>Forum Posts (<?= $topicsN ?> Topics)</span></div>
  <div class="stat"><strong><?= $accepted ?></strong><span>Active Mentorships (<?= $pendingM ?> Pending)</span></div>
  <div class="stat"><strong><?= $completions ?></strong><span>Tutorials Completed</span></div>
</div>

<div class="grid">
  <section class="card">
    <h2><?= icon('trending-up', 'icon-sm') ?> Member Signups by Month</h2>
    <?php if (!$signups): ?><p>No data yet.</p><?php endif; ?>
    <?php $maxS = $signups ? max(array_column($signups, 'n')) : 1; foreach ($signups as $s): ?>
      <div class="hbar">
        <span class="lbl"><?= e($s['m']) ?></span>
        <div class="hbar-track"><span class="fill" style="width:<?= (int)round($s['n'] / $maxS * 100) ?>%"></span></div>
        <span class="val"><?= (int)$s['n'] ?></span>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="card">
    <h2><?= icon('target', 'icon-sm') ?> Member Interests Distribution</h2>
    <?php foreach ($interestCount as $name => $n): ?>
      <div class="hbar">
        <span class="lbl"><?= e($name) ?></span>
        <div class="hbar-track"><span class="fill fill-pink" style="width:<?= (int)round($n / $maxInterest * 100) ?>%"></span></div>
        <span class="val"><?= $n ?></span>
      </div>
    <?php endforeach; ?>
  </section>
</div>

<h2 class="section-title"><?= icon('calendar', 'icon-sm') ?> Event Participation &amp; Attendance Breakdown</h2>
<div class="scroll">
  <table class="table">
    <thead>
      <tr>
        <th>Event / Workshop</th>
        <th>Date</th>
        <th>Registered</th>
        <th>Capacity Fill</th>
        <th>Marked Attended</th>
        <th>Manage</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($engagement as $r):
        $fill = $r['capacity'] ? min(100, (int)round($r['regs'] / $r['capacity'] * 100)) : 0;
      ?>
        <tr>
          <td><strong><?= e($r['title']) ?></strong></td>
          <td><?= e(fmt($r['event_date'], 'j M Y')) ?></td>
          <td><?= (int)$r['regs'] ?> / <?= (int)$r['capacity'] ?></td>
          <td style="min-width:130px">
            <div class="bar" style="height:8px;margin:4px 0"><span style="width:<?= $fill ?>%"></span></div>
            <span class="small"><?= $fill ?>% full</span>
          </td>
          <td><span class="tag tag-ok"><?= (int)$r['att'] ?> attended</span></td>
          <td><a class="btn btn-ghost btn-small" href="events.php?id=<?= (int)$r['id'] ?>">Attendance</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="grid" style="margin-top:28px">
  <section class="card">
    <h2><?= icon('award', 'icon-sm') ?> Most Active Members</h2>
    <table class="table">
      <thead><tr><th>Member</th><th>Posts</th><th>Events</th><th>Tutorials</th></tr></thead>
      <tbody>
        <?php foreach ($active_members as $m): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <?= avatar_html($m, '../', 'avatar-sm') ?>
                <a href="../member.php?id=<?= (int)$m['id'] ?>"><strong><?= e($m['full_name']) ?></strong></a>
              </div>
            </td>
            <td><?= (int)$m['posts'] ?></td>
            <td><?= (int)$m['evs'] ?></td>
            <td><?= (int)$m['res'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="card">
    <h2><?= icon('book', 'icon-sm') ?> Resource Completions</h2>
    <table class="table">
      <thead><tr><th>Learning Resource</th><th>Category</th><th>Completions</th></tr></thead>
      <tbody>
        <?php foreach ($resourceStats as $r): ?>
          <tr>
            <td><?= e($r['title']) ?></td>
            <td><span class="tag"><?= e($r['category']) ?></span></td>
            <td><strong><?= (int)$r['n'] ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
