<?php
/**
 * DBU Blood Bank — Emergency Password Reset Helper
 * Run this ONCE in your browser if admin/hospital login is not working.
 */
require_once 'config.php';

$messages = [];
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset'])) {

    $admin_pass    = 'admin123';
    $hospital_pass = 'hospital123';

    // Reset admin password
    $hash_admin = password_hash($admin_pass, PASSWORD_BCRYPT);
    $r1 = $conn->query("UPDATE admins SET password='$hash_admin' WHERE email='admin@dbu.edu.et'");
    if ($r1 && $conn->affected_rows >= 0) {
        $messages[] = '✅ Admin password reset to: <strong>admin123</strong>';
    } else {
        $errors[] = '❌ Admin update failed: ' . $conn->error;
    }

    // Reset all hospital passwords
    $hash_hosp = password_hash($hospital_pass, PASSWORD_BCRYPT);
    $r2 = $conn->query("UPDATE hospitals SET password='$hash_hosp'");
    if ($r2) {
        $messages[] = '✅ All hospital passwords reset to: <strong>hospital123</strong>';
        $messages[] = '✅ Affected hospitals: ' . $conn->affected_rows;
    } else {
        $errors[] = '❌ Hospital update failed: ' . $conn->error;
    }

    // Reset sample donor passwords
    $hash_donor = password_hash('password', PASSWORD_BCRYPT);
    $r3 = $conn->query("UPDATE donors SET password='$hash_donor'");
    if ($r3) {
        $messages[] = '✅ All donor passwords reset to: <strong>password</strong>';
    } else {
        $errors[] = '❌ Donor update failed: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Reset — DBU Blood Bank</title>
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family:'Segoe UI',Arial,sans-serif; min-height:100vh;
         background:linear-gradient(135deg,#0d1b2a,#001040);
         display:flex; align-items:center; justify-content:center; padding:20px; }
  .box { background:#fff; border-radius:20px; padding:44px 40px; max-width:540px; width:100%; }
</style>
</head>
<body>
<div class="box">
  <div class="text-center mb-4">
    <div style="font-size:52px;">🔑</div>
    <h3 class="fw-bold mt-2" style="color:#003087;">DBU Blood Bank</h3>
    <p class="text-muted">Emergency Password Reset Tool</p>
  </div>

  <?php if ($messages): ?>
    <?php foreach ($messages as $m): ?>
      <div class="alert alert-success border-0"><?= $m ?></div>
    <?php endforeach; ?>
    <div class="alert alert-warning border-0 mt-3">
      <strong>⚠ Security Notice:</strong> Delete <code>reset_password.php</code>
      from your server now that passwords have been reset.
    </div>
    <hr>
    <h6 class="fw-bold mt-3">Use these credentials to log in:</h6>
    <table class="table table-bordered mt-2" style="font-size:.9rem;">
      <tr><th>Role</th><th>Email</th><th>Password</th></tr>
      <tr><td>Admin</td><td>admin@dbu.edu.et</td><td><code>admin123</code></td></tr>
      <tr><td>Hospital</td><td>dberanhosp@health.gov.et</td><td><code>hospital123</code></td></tr>
      <tr><td>Hospital</td><td>healthcenter@dbu.edu.et</td><td><code>hospital123</code></td></tr>
      <tr><td>Hospital</td><td>stgmk@health.gov.et</td><td><code>hospital123</code></td></tr>
      <tr><td>Donors</td><td>any donor email</td><td><code>password</code></td></tr>
    </table>
    <a href="admin_login.php" class="btn w-100 mt-2 fw-bold rounded-pill"
       style="background:linear-gradient(135deg,#003087,#0050cc);color:#fff;">
      → Go to Admin Login
    </a>
  <?php endif; ?>

  <?php if ($errors): ?>
    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger border-0"><?= $e ?></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if (!$messages && !$errors): ?>
  <div class="alert alert-info border-0">
    This tool will reset <strong>all</strong> passwords to the defaults below:
    <ul class="mt-2 mb-0">
      <li>Admin → <code>admin123</code></li>
      <li>Hospitals → <code>hospital123</code></li>
      <li>Donors → <code>password</code></li>
    </ul>
  </div>
  <form method="POST">
    <input type="hidden" name="do_reset" value="1">
    <button type="submit" class="btn w-100 py-3 fw-bold rounded-pill border-0"
            style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;"
            onclick="return confirm('Reset ALL passwords to defaults?')">
      🔄 Reset All Passwords Now
    </button>
  </form>
  <?php endif; ?>

  <div class="text-center mt-4">
    <a href="index.php" class="text-muted small">← Back to main site</a>
  </div>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
