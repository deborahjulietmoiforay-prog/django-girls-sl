<?php
require_once __DIR__ . '/config.php';
redirect_if_logged_in();

$errors = [];
$name = $email = $bio = '';
$picked = ['Python', 'Django'];
$level = 'beginner';
$mentor = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $name      = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm'] ?? '';
    $bio       = trim($_POST['bio'] ?? '');
    $interests = clean_interests($_POST['interests'] ?? []);
    $picked    = $interests !== '' ? explode(',', $interests) : [];
    $level     = in_array($_POST['level'] ?? '', LEVELS, true) ? $_POST['level'] : 'beginner';
    $mentor    = isset($_POST['is_mentor']) ? 1 : 0;

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Enter your full name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must be at least 8 characters and include at least one letter and one number.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (mb_strlen($bio) > 500) {
        $errors[] = 'Short bio must be 500 characters or fewer.';
    }

    if (!$errors) {
        $check = db()->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists. Try logging in.';
        } else {
            $defaultAvatar = $mentor ? 'mentor-fatmata.svg' : 'member-deborah.svg';
            db()->prepare('INSERT INTO users (full_name,email,password_hash,interests,experience_level,bio,avatar,is_mentor) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $interests, $level, $bio, $defaultAvatar, $mentor]);
            flash('Account created successfully! Please log in to open your personalized dashboard.');
            go('login.php');
        }
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout">
  <section class="card auth-card auth-card-wide">
    <span class="eyebrow">Member Registration</span>
    <h1>Join Django Girls Sierra Leone</h1>
    <p class="small" style="margin-bottom:16px">Create your free member account to customize your learning path, register for workshops, and join our community forum.</p>

    <?php foreach ($errors as $err): ?>
      <p class="error" role="alert"><?= e($err) ?></p>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-row-2">
        <div>
          <label for="full_name">Full name</label>
          <input id="full_name" name="full_name" type="text" value="<?= e($name) ?>" placeholder="e.g. Deborah Juliet Moiforay" required autocomplete="name">
        </div>
        <div>
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" value="<?= e($email) ?>" placeholder="you@example.com" required autocomplete="email">
        </div>
      </div>

      <div class="form-row-2">
        <div>
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required minlength="8" placeholder="Min. 8 chars (letter + number)" autocomplete="new-password">
        </div>
        <div>
          <label for="confirm">Confirm password</label>
          <input id="confirm" name="confirm" type="password" required placeholder="Repeat your password" autocomplete="new-password">
        </div>
      </div>
      <small>Security requirement: At least 8 characters including a letter and a number.</small>

      <fieldset>
        <legend>What topics do you want to learn? (Personalizes your dashboard)</legend>
        <div class="interest-grid">
          <?php foreach (INTERESTS as $i): ?>
            <label class="check-pill">
              <input type="checkbox" name="interests[]" value="<?= e($i) ?>" <?= in_array($i, $picked, true) ? 'checked' : '' ?>>
              <span><?= e($i) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <div class="form-row-2" style="margin-top:12px">
        <div>
          <label for="level">Your experience level</label>
          <select id="level" name="level">
            <?php foreach (LEVELS as $l): ?>
              <option value="<?= e($l) ?>" <?= $l === $level ? 'selected' : '' ?>><?= e(ucfirst($l)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="bio">Short bio / goal (optional)</label>
          <input id="bio" name="bio" type="text" maxlength="500" value="<?= e($bio) ?>" placeholder="e.g. Semester 5 ICT student learning web dev">
        </div>
      </div>

      <label class="check mentor-optin">
        <input type="checkbox" name="is_mentor" value="1" <?= $mentor ? 'checked' : '' ?>>
        <span><strong>Volunteer as a Mentor:</strong> I am an experienced developer and want to guide new members</span>
      </label>

      <button class="btn" type="submit">Create Member Account &rarr;</button>
    </form>
    <p class="alt">Already registered? <a href="login.php">Log in here</a></p>
  </section>

  <aside class="auth-Promo">
    <img src="assets/workshop-banner.jpg" alt="Django Girls Mentorship and Coding Workshop">
    <div class="auth-promo-body">
      <h2>Why join our community?</h2>
      <p>Whether you are writing your very first line of HTML or building full-stack PHP and Django applications, our community supports every step of your journey.</p>
      <ul class="check-list">
        <li><strong>Personalized Dashboard:</strong> Get matched with tutorials &amp; workshops for your chosen topics.</li>
        <li><strong>Hands-on Workshops:</strong> Reserve your seat at Freetown lab sessions and online bootcamps.</li>
        <li><strong>Mentorship &amp; Forum:</strong> Ask questions and learn alongside peers and mentors.</li>
      </ul>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
