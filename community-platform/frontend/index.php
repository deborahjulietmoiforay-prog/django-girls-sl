<?php
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';

$pdo = db();
$totalMembers   = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE active = 1')->fetchColumn();
$totalEvents    = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalResources = (int)$pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn();
$totalMentors   = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_mentor = 1 AND active = 1')->fetchColumn();

$upcomingPreview = $pdo->prepare('SELECT * FROM events WHERE event_date >= ? ORDER BY event_date LIMIT 3');
$upcomingPreview->execute([now()]);
$previewEvents = $upcomingPreview->fetchAll();
?>

<section class="hero-split">
  <div class="hero-content">
    <span class="eyebrow"><?= icon('code', 'icon-sm') ?> Empowering Women in Tech &bull; Sierra Leone</span>
    <h1>Learn to code.<br><span>Build your future.</span></h1>
    <p class="hero-lead">Django Girls Sierra Leone is a community-driven organisation helping women in Freetown and across Sierra Leone take their first steps into Python, Django, and modern web programming. Join free hands-on workshops, track your learning progress, and connect with experienced mentors.</p>
    <div class="actions">
      <?php if ($me): ?>
        <a class="btn btn-lg" href="dashboard.php">Open My Dashboard <?= icon('arrow-right', 'icon-sm') ?></a>
        <a class="btn btn-ghost btn-lg" href="events.php">Browse Workshops</a>
      <?php else: ?>
        <a class="btn btn-lg" href="signup.php">Create Free Account <?= icon('arrow-right', 'icon-sm') ?></a>
        <a class="btn btn-ghost btn-lg" href="login.php">Member Log in</a>
      <?php endif; ?>
    </div>
    <div class="hero-trust">
      <span><?= icon('check', 'icon-sm') ?> 100% Free Workshops</span>
      <span><?= icon('check', 'icon-sm') ?> Beginner Friendly</span>
      <span><?= icon('check', 'icon-sm') ?> Local Freetown Mentors</span>
    </div>
  </div>
  <div class="hero-visual">
    <div class="hero-image-card">
      <div class="hero-corner-badge">
        <span class="dot"></span>
        <span>Active Workshop Cohort</span>
      </div>
      <img class="hero-main-img" src="assets/img/training-session.jpg" alt="Instructor leading live hands-on Python and web development training in Freetown">
      <div class="hero-floating-badge">
        <strong>Freetown Tech Innovation Hub</strong>
        <span>Live coding sessions &bull; Limkokwing Lab 2</span>
      </div>
    </div>
    <div class="hero-floating-card">
      <img src="assets/img/mentorship.jpg" alt="Mentor helping student debug code on workstation screen">
      <div class="hero-floating-card-body">
        <strong><?= icon('users', 'icon-sm') ?> 1-on-1 Mentorship</strong>
        <span>Dedicated code reviews</span>
      </div>
    </div>
  </div>
</section>

<section class="impact-strip" aria-label="Community statistics">
  <div class="impact-item">
    <strong><?= $totalMembers ?>+</strong>
    <span>Community Members</span>
  </div>
  <div class="impact-item">
    <strong><?= $totalEvents ?></strong>
    <span>Workshops &amp; Events</span>
  </div>
  <div class="impact-item">
    <strong><?= $totalResources ?></strong>
    <span>Curated Tutorials</span>
  </div>
  <div class="impact-item">
    <strong><?= $totalMentors ?></strong>
    <span>Active Mentors</span>
  </div>
</section>

<section class="section-block">
  <div class="section-header-center">
    <span class="eyebrow">Platform Features</span>
    <h2>Everything you need to grow as a developer</h2>
    <p>Built upon our secure Assignment 1 Authentication System, transformed into a complete community &amp; organisation management platform.</p>
  </div>

  <div class="features">
    <div class="feature-card">
      <div class="feature-icon"><?= icon('calendar', 'icon-lg') ?></div>
      <h3>Events &amp; Workshops</h3>
      <p>Discover upcoming Freetown coding bootcamps, online training sessions, and career talks. Register in one click and track your participation history.</p>
      <a href="<?= $me ? 'events.php' : 'signup.php' ?>" class="feature-link">Explore events &rarr;</a>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><?= icon('book', 'icon-lg') ?></div>
      <h3>Learning Library</h3>
      <p>Follow structured tutorials in Python, Django, HTML &amp; CSS, JavaScript, Data Science, and UI/UX Design with interactive progress tracking.</p>
      <a href="<?= $me ? 'resources.php' : 'signup.php' ?>" class="feature-link">Start learning &rarr;</a>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><?= icon('message-square', 'icon-lg') ?></div>
      <h3>Community Forum</h3>
      <p>Ask programming questions, share your semester projects, collaborate with peers, and get feedback in a welcoming discussion board.</p>
      <a href="<?= $me ? 'community.php' : 'signup.php' ?>" class="feature-link">Join discussions &rarr;</a>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><?= icon('users', 'icon-lg') ?></div>
      <h3>1-on-1 Mentorship</h3>
      <p>Connect directly with experienced Sierra Leonean developers and organisers who volunteer to guide you through code reviews and career steps.</p>
      <a href="<?= $me ? 'mentors.php' : 'signup.php' ?>" class="feature-link">Meet mentors &rarr;</a>
    </div>
  </div>
</section>

<section class="section-block">
  <div class="section-header-center">
    <span class="eyebrow">Real Impact &bull; Real Experience</span>
    <h2>Inside Django Girls Sierra Leone</h2>
    <p>See our students and mentors in action across interactive bootcamps, campus labs, and peer study groups in Freetown.</p>
  </div>

  <div class="workshop-gallery">
    <article class="gallery-card">
      <div class="gallery-card-thumb">
        <img src="assets/img/training-session.jpg" alt="Instructor demonstrating web development on a large display screen">
        <span class="thumb-tag"><?= icon('code', 'icon-sm') ?> Live Instruction</span>
      </div>
      <div class="gallery-card-body">
        <h3>Instructor-Led Demonstrations</h3>
        <p>Experienced Sierra Leonean developers break down Python, HTML, CSS, and database queries step-by-step on live screens.</p>
      </div>
    </article>

    <article class="gallery-card">
      <div class="gallery-card-thumb">
        <img src="assets/img/computer-lab.jpg" alt="Students writing code at desktop computer workstations">
        <span class="thumb-tag"><?= icon('map-pin', 'icon-sm') ?> Limkokwing Lab</span>
      </div>
      <div class="gallery-card-body">
        <h3>Hands-on Workstation Practice</h3>
        <p>Every attendee codes along in real-time, building functional projects from scratch with provided lab computers.</p>
      </div>
    </article>

    <article class="gallery-card">
      <div class="gallery-card-thumb">
        <img src="assets/img/classroom.jpg" alt="Female students seated around a conference table discussing projects with laptops">
        <span class="thumb-tag"><?= icon('users', 'icon-sm') ?> Collaborative Study</span>
      </div>
      <div class="gallery-card-body">
        <h3>Peer Collaboration &amp; Seminars</h3>
        <p>Women learn together in small groups, sharing ideas, tackling semester assignments, and preparing for tech careers.</p>
      </div>
    </article>

    <article class="gallery-card">
      <div class="gallery-card-thumb">
        <img src="assets/img/mentorship.jpg" alt="A mentor guiding a student on her workstation monitor">
        <span class="thumb-tag"><?= icon('award', 'icon-sm') ?> 1-on-1 Mentorship</span>
      </div>
      <div class="gallery-card-body">
        <h3>Direct Developer Guidance</h3>
        <p>No student gets stuck alone: volunteer mentors provide friendly code reviews, debugging assistance, and career advice.</p>
      </div>
    </article>
  </div>
</section>

<section class="spotlight-banner">
  <div class="spotlight-image">
    <img src="assets/img/computer-lab.jpg" alt="Students working on laptops in a computer lab">
    <div class="spotlight-caption">
      <?= icon('map-pin', 'icon-sm') ?> <span>Limkokwing University Computer Lab &bull; Freetown, Sierra Leone</span>
    </div>
  </div>
  <div class="spotlight-text">
    <span class="eyebrow">Our Mission &amp; Vision</span>
    <h2>Bridging the gender gap in technology across Sierra Leone</h2>
    <p>At Django Girls Sierra Leone, we believe that technology thrives when everyone has a seat at the table. Starting from a member's very first login, our platform personalizes learning materials and workshop recommendations based on their unique interests and experience level.</p>
    <ul class="check-list">
      <li>Personalized member dashboard tailored to your coding interests</li>
      <li>Hands-on training from beginner HTML/CSS to full-stack Python &amp; Django</li>
      <li>Supportive community network of students, mentors, and tech leaders</li>
    </ul>
    <div class="actions">
      <a class="btn" href="<?= $me ? 'dashboard.php' : 'signup.php' ?>"><?= $me ? 'Go to Dashboard' : 'Become a Member Today' ?></a>
    </div>
  </div>
</section>

<?php if ($previewEvents): ?>
<section class="section-block">
  <div class="section-head-row">
    <div>
      <span class="eyebrow">Mark Your Calendar</span>
      <h2 style="margin:4px 0 0">Upcoming Workshops &amp; Sessions</h2>
    </div>
    <a class="btn btn-ghost btn-small" href="<?= $me ? 'events.php' : 'login.php' ?>">View All Events &rarr;</a>
  </div>
  <div class="grid">
    <?php foreach ($previewEvents as $ev): ?>
      <article class="card event-card">
        <div class="event-card-top">
          <span class="tag tag-pink"><?= e($ev['type']) ?></span>
          <span class="tag"><?= e($ev['topic']) ?></span>
        </div>
        <h3><?= e($ev['title']) ?></h3>
        <p><?= e($ev['description']) ?></p>
        <div class="event-meta">
          <div class="meta-item"><?= icon('calendar', 'icon-sm') ?> <?= e(fmt($ev['event_date'])) ?></div>
          <div class="meta-item" style="margin-top:4px"><?= icon('map-pin', 'icon-sm') ?> <?= e($ev['location']) ?></div>
        </div>
        <a class="btn btn-small" href="<?= $me ? 'events.php' : 'login.php' ?>"><?= $me ? 'Register on Events Page' : 'Log in to Register' ?></a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
