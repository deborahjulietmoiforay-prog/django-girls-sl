<?php
require_once __DIR__ . '/config.php';
$me  = require_login();
$uid = (int)$me['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    post_guard('profile.php');
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'password') {
        $cur = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        if (!password_verify($cur, $me['password_hash'])) {
            $errors[] = 'Your current password is incorrect.';
        } elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors[] = 'New password must be at least 8 characters with a letter and a number.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('Your password has been updated.');
            go('profile.php');
        }
    } else {
        $name   = trim($_POST['full_name'] ?? '');
        $bio    = trim($_POST['bio'] ?? '');
        $level  = in_array($_POST['level'] ?? '', LEVELS, true) ? $_POST['level'] : 'beginner';
        $mentor = isset($_POST['is_mentor']) ? 1 : 0;
        $avatar = $me['avatar'];

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'Enter your full name (up to 100 characters).';
        }
        if (mb_strlen($bio) > 500) {
            $errors[] = 'Bio must be 500 characters or fewer.';
        }

        // Check if user selected a preset avatar
        $preset = $_POST['preset_avatar'] ?? '';
        if ($preset !== '' && defined('PRESET_AVATARS') && is_array(PRESET_AVATARS) && array_key_exists($preset, PRESET_AVATARS)) {
            $avatar = $preset;
        }

        // Profile picture upload: size, MIME type & image check, random file name
        if (!empty($_FILES['avatar']['name'])) {
            $f = $_FILES['avatar'];
            $mime = ($f['error'] === UPLOAD_ERR_OK && is_file($f['tmp_name'])) ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
            $ext = [
                'image/jpeg'  => 'jpg',
                'image/pjpeg' => 'jpg',
                'image/jpg'   => 'jpg',
                'image/png'   => 'png',
                'image/webp'  => 'webp',
            ][$mime] ?? null;
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'The picture could not be uploaded. Try a smaller file.';
            } elseif ($f['size'] > 8 * 1024 * 1024) {
                $errors[] = 'Picture must be 8 MB or smaller.';
            } elseif (!$ext || !@getimagesize($f['tmp_name'])) {
                $errors[] = 'Upload a valid JPG, PNG, or WebP image.';
            } elseif (!$errors) {
                $dir = __DIR__ . '/uploads/avatars';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $newName = bin2hex(random_bytes(12)) . '.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $newName)) {
                    if ($avatar && (!defined('PRESET_AVATARS') || !is_array(PRESET_AVATARS) || !array_key_exists($avatar, PRESET_AVATARS)) && is_file($dir . '/' . $avatar)) {
                        @unlink($dir . '/' . $avatar);
                    }
                    $avatar = $newName;
                } else {
                    $errors[] = 'Could not save the picture. Check that uploads/avatars is writable.';
                }
            }
        }

        if (!$errors) {
            db()->prepare('UPDATE users SET full_name=?, bio=?, interests=?, experience_level=?, is_mentor=?, avatar=? WHERE id=?')
                ->execute([$name, $bio, clean_interests($_POST['interests'] ?? []), $level, $mentor, $avatar, $uid]);
            flash('Your profile and picture have been saved.');
            go('profile.php');
        }
        $me = array_merge($me, [
            'full_name'        => $name,
            'bio'              => $bio,
            'experience_level' => $level,
            'is_mentor'        => $mentor,
            'avatar'           => $avatar,
            'interests'        => clean_interests($_POST['interests'] ?? []),
        ]);
    }
}
$mine = user_interests($me);

$pageTitle = 'My profile';
require __DIR__ . '/includes/header.php';
?>
<div class="section-head-row">
  <div>
    <span class="eyebrow">Member Account Settings</span>
    <h1 style="margin:4px 0">My Profile &amp; Picture</h1>
    <p class="lead" style="margin-bottom:0">Customize how you appear in the community directory and forum.</p>
  </div>
  <a class="btn btn-ghost btn-small" href="member.php?id=<?= $uid ?>">View My Public Profile &rarr;</a>
</div>

<?php foreach ($errors as $err): ?>
  <p class="error" role="alert"><?= e($err) ?></p>
<?php endforeach; ?>

<div class="grid">
  <section class="card span-2">
    <h2>Profile Details &amp; Avatar</h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="profile">

      <div class="avatar-upload-box">
        <div id="avatarPreviewWrap">
          <?= avatar_html($me, '', 'avatar-xl') ?>
        </div>
        <div style="flex:1">
          <label for="avatar" style="margin-top:0">Upload Custom Profile Picture</label>
          <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"
                 onchange="if(this.files&&this.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('avatarPreviewWrap').innerHTML='<img class=\'avatar avatar-xl\' src=\''+e.target.result+'\' alt=\'Preview\'>';};r.readAsDataURL(this.files[0]);}">
          <small>Upload your real photo (JPG, PNG, or WebP up to 8 MB):</small>

          <?php if (defined('PRESET_AVATARS') && !empty(PRESET_AVATARS)): ?>
          <div class="preset-avatars">
            <?php foreach (PRESET_AVATARS as $file => $label): ?>
              <label class="preset-option <?= ($me['avatar'] ?? '') === $file ? 'selected' : '' ?>" title="<?= e($label) ?>">
                <input type="radio" name="preset_avatar" value="<?= e($file) ?>" <?= ($me['avatar'] ?? '') === $file ? 'checked' : '' ?>
                       onchange="document.getElementById('avatarPreviewWrap').innerHTML='<img class=\'avatar avatar-xl\' src=\'uploads/avatars/<?= e($file) ?>\' alt=\'Preview\'>'">
                <img src="uploads/avatars/<?= e($file) ?>" alt="<?= e($label) ?>" class="avatar avatar-sm">
                <span><?= e($label) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row-2">
        <div>
          <label for="full_name">Full name</label>
          <input id="full_name" name="full_name" value="<?= e($me['full_name']) ?>" required>
        </div>
        <div>
          <label for="email">Email address (verified)</label>
          <input id="email" value="<?= e($me['email']) ?>" disabled>
        </div>
      </div>

      <label for="bio">Short bio &amp; coding goals</label>
      <textarea id="bio" name="bio" rows="3" maxlength="500" placeholder="Share your background, what you are studying, or what projects you are building..."><?= e($me['bio']) ?></textarea>

      <fieldset>
        <legend>Learning &amp; Teaching Interests</legend>
        <div class="interest-grid">
          <?php foreach (INTERESTS as $i): ?>
            <label class="check-pill">
              <input type="checkbox" name="interests[]" value="<?= e($i) ?>" <?= in_array($i, $mine, true) ? 'checked' : '' ?>>
              <span><?= e($i) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <label for="level">Experience level</label>
      <select id="level" name="level">
        <?php foreach (LEVELS as $l): ?>
          <option value="<?= e($l) ?>" <?= $l === $me['experience_level'] ? 'selected' : '' ?>><?= e(ucfirst($l)) ?></option>
        <?php endforeach; ?>
      </select>

      <label class="check mentor-optin">
        <input type="checkbox" name="is_mentor" value="1" <?= $me['is_mentor'] ? 'checked' : '' ?>>
        <span><strong>Available as a Community Mentor:</strong> Allow members to send me mentorship requests</span>
      </label>

      <button class="btn" type="submit">Save Profile Changes</button>
    </form>
  </section>

  <section class="card">
    <h2>Security &amp; Password</h2>
    <p class="small">Update your account password. Use at least 8 characters with both letters and numbers.</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <label for="current">Current password</label>
      <input id="current" name="current" type="password" required autocomplete="current-password">
      <label for="new">New password</label>
      <input id="new" name="new" type="password" required minlength="8" autocomplete="new-password">
      <button class="btn btn-ghost" type="submit">Update Password</button>
    </form>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
