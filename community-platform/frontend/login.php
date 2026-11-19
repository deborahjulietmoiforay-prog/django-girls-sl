<?php
require_once __DIR__ . '/config.php';
redirect_if_logged_in();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['active']) {
                $error = 'This account has been deactivated. Please contact an administrator.';
            } else {
                session_regenerate_id(true); // Prevents session fixation (Assignment 1 security standard)
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                db()->prepare("UPDATE users SET last_login = ? WHERE id = ?")->execute([date('Y-m-d H:i:s'), $user['id']]);
                flash('Welcome back, ' . explode(' ', $user['full_name'])[0] . '!');
                go('dashboard.php');
            }
        } else {
            // Identical message for unknown email or wrong password to prevent user enumeration
            $error = 'Email or password is incorrect.';
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout">
  <section class="card auth-card">
    <span class="eyebrow">Member Authentication</span>
    <h1>Welcome back</h1>
    <p class="small" style="margin-bottom:16px">Sign in to access your personalized Django Girls Sierra Leone dashboard, workshops, and learning tracker.</p>

    <?php if ($error): ?>
      <p class="error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" novalidate id="loginForm">
      <?= csrf_field() ?>
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" value="<?= e($email) ?>" placeholder="you@example.com" required autocomplete="email">

      <label for="password">Password</label>
      <div class="pw-wrap">
        <input id="password" name="password" type="password" placeholder="Enter your password" required autocomplete="current-password">
        <button type="button" class="pw-toggle" onclick="const p=document.getElementById('password');p.type=p.type==='password'?'text':'password';this.textContent=p.type==='password'?'Show':'Hide'">Show</button>
      </div>

      <button class="btn" type="submit">Log in to Dashboard &rarr;</button>
    </form>

    <div class="demo-box">
      <strong>Quick Demo Accounts (Click to auto-fill):</strong>
      <div class="demo-btns">
        <button type="button" class="chip" onclick="document.getElementById('email').value='admin@djangogirls.sl';document.getElementById('password').value='Admin@12345';">
          <?= icon('shield', 'icon-sm') ?> Admin Account
        </button>
        <button type="button" class="chip" onclick="document.getElementById('email').value='aminata@djangogirls.sl';document.getElementById('password').value='Mentor@12345';">
          <?= icon('user-check', 'icon-sm') ?> Mentor Account
        </button>
      </div>
    </div>

    <p class="alt">New to Django Girls Sierra Leone? <a href="signup.php">Create an account</a></p>
  </section>

  <aside class="auth-Promo">
    <img src="assets/img/computer-lab.jpg" alt="Members working together in a computer lab">
    <div class="auth-promo-body">
      <h2>Connected with Assignment 1 (AUTH)</h2>
      <p>Our authentication system uses industry-standard security practices—including Bcrypt/Argon2 password hashing, CSRF tokens, prepared PDO statements, and session fixation protection—and connects directly into our community platform.</p>
      <ul class="check-list">
        <li>Track your progress across 8+ curated coding tutorials</li>
        <li>Register for upcoming Freetown &amp; online workshops</li>
        <li>Connect with experienced mentors and peers</li>
      </ul>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
