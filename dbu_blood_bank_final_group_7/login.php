<?php
/**
 * Donor Login — DBU Blood Bank
 */
$page_title = 'Donor Login';
require_once 'config.php';

if (is_logged_in()) redirect('dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, blood_type, password, is_active FROM donors WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $donor = $res->fetch_assoc();
            $stmt->close();

            if (!$donor['is_active']) {
                $error = 'Your account has been deactivated. Please contact admin.';
            } elseif (password_verify($password, $donor['password'])) {
                // Set Session Variables
                $_SESSION['donor_id']    = $donor['id'];
                $_SESSION['donor_name']  = $donor['name'];
                $_SESSION['donor_blood'] = $donor['blood_type'];
                
                redirect('dashboard.php');
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            if(isset($stmt)) $stmt->close();
            $error = 'Invalid email or password.';
        }
    }
}
require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="min-vh-100 d-flex flex-column">
  <div class="text-white py-5 text-center"
       style="background:linear-gradient(135deg,#003087 0%,#4c1d95 60%,#7c3aed 100%);
              position:relative;overflow:hidden;">
    <div style="position:relative; z-index: 2;">
      <div style="font-size:48px;margin-bottom:8px;">🩸</div>
      <h1 class="fw-bold fs-2">Donor Login</h1>
      <p style="color:rgba(255,255,255,.85);font-size:.95rem;">Welcome back! Sign in to save lives.</p>
    </div>
  </div>

  <div class="container py-5 flex-grow-1">
    <div class="row justify-content-center">
      <div class="col-md-5 col-sm-8">
        <div class="card border-0 shadow-lg" style="border-radius:22px;">
          <div class="p-4 text-center text-white"
               style="background:linear-gradient(135deg,#4c1d95,#7c3aed,#db2777); border-radius: 22px 22px 0 0;">
            <div style="font-size:36px;margin-bottom:6px;">🔑</div>
            <div class="fw-bold fs-5">Sign In to Your Account</div>
            <div style="color:rgba(255,255,255,.7);font-size:.85rem;">DBU Blood Bank Portal</div>
          </div>

          <div class="p-4 p-md-5 bg-white" style="border-radius: 0 0 22px 22px;">

            <?php if ($error): ?>
            <div class="alert border-0 rounded-3 mb-4"
                 style="background:rgba(204,0,0,.08); color:#CC0000; border-left:4px solid #CC0000 !important;">
              ⚠ <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST">
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">EMAIL ADDRESS</label>
                <input type="email" name="email" class="form-control form-control-lg"
                       style="border-radius:12px; border:2px solid #e5e7eb;"
                       placeholder="your@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
              </div>
              <div class="mb-4">
                <label class="form-label fw-semibold text-secondary small">PASSWORD</label>
                <input type="password" name="password" class="form-control form-control-lg"
                       style="border-radius:12px; border:2px solid #e5e7eb;"
                       placeholder="Your password" required>
              </div>
              <button type="submit" class="btn w-100 py-3 fw-bold fs-5 rounded-pill border-0 text-white"
                      style="background:linear-gradient(135deg,#4c1d95,#7c3aed,#db2777);
                             box-shadow:0 4px 20px rgba(124,58,237,.35);">
                → Login to Dashboard
              </button>
            </form>

            <hr class="my-4 opacity-50">

            <div class="text-center text-muted small">
              Don't have an account?
              <a href="register.php" class="fw-bold text-decoration-none" style="color:#7c3aed;">Register here</a>
            </div>
            
            <div class="text-center mt-3">
              <a href="admin_login.php" class="text-muted text-decoration-none" style="font-size:.85rem;">Admin Access Only →</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>