<?php
$page_title = 'Admin Login';
require_once 'config.php';
if (is_admin()) redirect('admin_panel.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $admin = $res->fetch_assoc();
            $stmt->close();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id']    = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                redirect('admin_panel.php');
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $stmt->close();
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — DBU Blood Bank</title>
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family:'Segoe UI',Arial,sans-serif; min-height:100vh; background:linear-gradient(135deg,#0d1b2a 0%,#0a1628 50%,#001040 100%); display:flex; flex-direction:column; align-items:center; justify-content:center; padding:20px; }
  .form-card { background:rgba(255,255,255,.04); backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,.1); border-radius:22px; padding:52px 48px; width:100%; max-width:420px; }
  .admin-badge { display:inline-block; background:rgba(255,215,0,.12); border:1px solid rgba(255,215,0,.3); color:#FFD700; padding:6px 20px; border-radius:50px; font-size:12px; text-transform:uppercase; letter-spacing:2px; margin-bottom:20px; }
  label { color:#9ab; font-size:.825rem; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
  input { background:rgba(255,255,255,.06)!important; border:1px solid rgba(255,255,255,.12)!important; color:#fff!important; }
  input::placeholder { color:#556!important; }
  input:focus { border-color:#FFD700!important; background:rgba(255,255,255,.09)!important; box-shadow:none!important; }
  .security-note { color:#445; font-size:.78rem; border-top:1px solid rgba(255,255,255,.06); padding-top:18px; margin-top:24px; }
</style>
</head>
<body>
<div class="form-card text-center">
  <div style="font-size:60px;margin-bottom:16px;">🛡</div>
  <span class="admin-badge">Admin Portal</span>
  <h2 class="text-white fw-bold mb-1">Secure Admin Access</h2>
  <p class="mb-4" style="color:#8899aa;font-size:.9rem;">Debre Berhan University Blood Bank Administration</p>

  <?php if ($error): ?>
    <div class="alert" style="background:rgba(204,0,0,.15);color:#ff8080;border:1px solid rgba(204,0,0,.3);">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="text-start">
    <div class="mb-3">
      <label class="form-label">Admin Email</label>
      <input type="email" name="email" class="form-control form-control-lg" placeholder="admin@dbu.edu.et"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
    </div>
    <div class="mb-4">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn w-100 py-3 fw-bold rounded-pill" style="background:linear-gradient(135deg,#003087,#0050cc);color:#fff;">🛡 Access Admin Panel</button>
  </form>

  </a>

  <a href="index.php" class="d-block mt-4" style="color:#667;font-size:.85rem;text-decoration:none;">← Back to main site</a>
  <div class="security-note text-center">This is a restricted area. Unauthorized access is prohibited.</div>
</div>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
