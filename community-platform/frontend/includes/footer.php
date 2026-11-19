</main>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <h3>{&#9829;} <?= e(ORG_NAME) ?></h3>
        <p>Empowering women across Sierra Leone to learn Python, Django, and modern web programming through free hands-on workshops, curated learning resources, and community mentorship.</p>
      </div>
      <div class="footer-links">
        <h4>Platform</h4>
        <ul>
          <li><a href="<?= e($base ?? '') ?>index.php">Home</a></li>
          <?php if (is_logged_in()): ?>
            <li><a href="<?= e($base ?? '') ?>dashboard.php">Member Dashboard</a></li>
            <li><a href="<?= e($base ?? '') ?>events.php">Events &amp; Workshops</a></li>
            <li><a href="<?= e($base ?? '') ?>resources.php">Learning Library</a></li>
          <?php else: ?>
            <li><a href="<?= e($base ?? '') ?>signup.php">Member Registration</a></li>
            <li><a href="<?= e($base ?? '') ?>login.php">Member Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="footer-links">
        <h4>Community</h4>
        <ul>
          <?php if (is_logged_in()): ?>
            <li><a href="<?= e($base ?? '') ?>community.php">Discussion Forum</a></li>
            <li><a href="<?= e($base ?? '') ?>mentors.php">Mentorship Hub</a></li>
            <li><a href="<?= e($base ?? '') ?>members.php">Member Directory</a></li>
            <li><a href="<?= e($base ?? '') ?>profile.php">My Profile</a></li>
          <?php else: ?>
            <li><span>Freetown Innovation Hub</span></li>
            <li><span>Limkokwing University SL</span></li>
            <li><span>DCOMP207 Web Programming</span></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= e(STUDENT_FIRST) ?> <?= e(STUDENT_LAST) ?>, <?= e(PROGRAM) ?>, <?= e(SEMESTER) ?>, <?= e(STUDENT_ID) ?>, <?= date('Y') ?> &mdash; <?= e(ORG_NAME) ?></p>
    </div>
  </div>
</footer>
</body>
</html>
