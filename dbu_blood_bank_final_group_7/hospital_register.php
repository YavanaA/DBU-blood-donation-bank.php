<?php
/**
 * Hospital Registration — DBU Blood Bank Management System
 * University : Debre Berhan University
 */
$page_title = 'Hospital Registration';
require_once 'config.php';

if (isset($_SESSION['hospital_id'])) redirect('hospital_dashboard.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hospital_name = trim($_POST['hospital_name']     ?? '');
    $email         = trim($_POST['email']             ?? '');
    $phone         = trim($_POST['primary_contact']   ?? '');
    $address       = trim($_POST['address']           ?? '');
    $password      = $_POST['password']               ?? '';
    $confirm       = $_POST['confirm_password']       ?? '';

    if (!$hospital_name || !$email || !$phone || !$password) {
        $error = 'Hospital name, email, phone, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt_chk = $conn->prepare("SELECT id FROM hospitals WHERE email = ? LIMIT 1");
        $stmt_chk->bind_param('s', $email);
        $stmt_chk->execute();
        $stmt_chk->store_result();
        if ($stmt_chk->num_rows > 0) {
            $stmt_chk->close();
            $error = 'This email is already registered. Please login.';
        } else {
            $stmt_chk->close();
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO hospitals (hospital_name, email, password, primary_contact, address, is_active) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param('sssss', $hospital_name, $email, $hashed, $phone, $address);
            $stmt->execute();
            $stmt->close();
            $success = 'Registration submitted! Your account is pending admin approval. You will be notified once activated.';
        }
    }
}

require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="text-white py-5 text-center"
     style="background:linear-gradient(135deg,#0891b2 0%,#003087 50%,#4c1d95 100%);
            position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 50% 50%,rgba(245,158,11,.1),transparent 70%);
       pointer-events:none;"></div>
  <div style="position:relative;">
    <div style="font-size:52px;margin-bottom:8px;">🏥</div>
    <h1 class="fw-black fs-2">Hospital Registration</h1>
    <p style="color:rgba(255,255,255,.8);">Register your hospital to request blood from DBU Blood Bank</p>
  </div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

      <?php if ($success): ?>
        <div class="alert alert-success rounded-3 text-center fw-semibold" style="font-size:.95rem;">
          ✅ <?= htmlspecialchars($success) ?>
          <div class="mt-3">
            <a href="hospital_login.php" class="btn btn-sm btn-outline-success">← Back to Hospital Login</a>
          </div>
        </div>
      <?php else: ?>

      <div class="card border-0 overflow-hidden"
           style="border-radius:22px;box-shadow:0 8px 40px rgba(0,0,0,.12);">
        <div class="p-4 text-center text-white"
             style="background:linear-gradient(135deg,#0891b2,#003087,#4c1d95);">
          <div style="font-size:32px;margin-bottom:6px;">📋</div>
          <div class="fw-bold fs-5">Hospital Sign Up</div>
          <div style="color:rgba(255,255,255,.7);font-size:.85rem;">DBU Blood Bank — Hospital Access Request</div>
        </div>

        <div class="p-4">
          <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 py-2" style="font-size:.9rem;">⚠️ <?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" autocomplete="off">

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.9rem;">Hospital / Clinic Name <span class="text-danger">*</span></label>
              <input type="text" name="hospital_name" class="form-control" required
                     placeholder="e.g. Debre Berhan Referral Hospital"
                     value="<?= htmlspecialchars($_POST['hospital_name'] ?? '') ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.9rem;">Official Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required
                     placeholder="hospital@example.et"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.9rem;">Primary Contact Number <span class="text-danger">*</span></label>
              <input type="tel" name="primary_contact" class="form-control" required
                     placeholder="+251 9XX XXX XXX"
                     value="<?= htmlspecialchars($_POST['primary_contact'] ?? '') ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.9rem;">Address / Location</label>
              <input type="text" name="address" class="form-control"
                     placeholder="Town, Region (optional)"
                     value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:.9rem;">Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" required
                     placeholder="Min. 6 characters">
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold" style="font-size:.9rem;">Confirm Password <span class="text-danger">*</span></label>
              <input type="password" name="confirm_password" class="form-control" required
                     placeholder="Re-enter password">
            </div>

            <button type="submit" class="btn w-100 fw-bold text-white py-2"
                    style="background:linear-gradient(135deg,#0891b2,#003087);
                           border-radius:12px;letter-spacing:.5px;">
              🏥 Submit Registration
            </button>

            <div class="text-center mt-4 text-muted small">
              Already registered?
              <a href="hospital_login.php" class="fw-bold text-decoration-none" style="color:#0891b2;">Login here</a>
            </div>
          </form>
        </div>
      </div>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
