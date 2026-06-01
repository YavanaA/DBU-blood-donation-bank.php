<?php
$page_title = 'Hospital Portal Login';
require_once 'config.php';

if (isset($_SESSION['hospital_id'])) redirect('hospital_dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, hospital_name, email, password FROM hospitals WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $hospital = $res->fetch_assoc();
            $stmt->close();
            if (password_verify($password, $hospital['password'])) {
                $_SESSION['hospital_id']    = $hospital['id'];
                $_SESSION['hospital_name']  = $hospital['hospital_name'];
                $_SESSION['hospital_email'] = $hospital['email'];
                redirect('hospital_dashboard.php');
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $stmt->close();
            $error = 'Hospital account not found or deactivated. Contact admin.';
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
    <h1 class="fw-black fs-2">Hospital Portal</h1>
    <p style="color:rgba(255,255,255,.8);">Authorized hospitals can request blood and manage requests</p>
  </div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="row g-4 align-items-start">

        <!-- Login Form -->
        <div class="col-md-6">
          <div class="card border-0 overflow-hidden"
               style="border-radius:22px;box-shadow:0 8px 40px rgba(0,0,0,.12);">
            <div class="p-4 text-center text-white"
                 style="background:linear-gradient(135deg,#0891b2,#003087,#4c1d95);">
              <div style="font-size:32px;margin-bottom:6px;">🔑</div>
              <div class="fw-bold fs-5">Hospital Sign In</div>
              <div style="color:rgba(255,255,255,.7);font-size:.85rem;">DBU Blood Bank — Hospital Access</div>
            </div>
            <div class="p-4 p-md-5" style="background:#fff;">

              <?php if ($error): ?>
              <div class="alert border-0 rounded-3 mb-4"
                   style="background:rgba(204,0,0,.07);color:#CC0000;border-left:4px solid #CC0000;">
                ⚠ <?= htmlspecialchars($error) ?>
              </div>
              <?php endif; ?>

              <form method="POST">
                <div class="mb-3">
                  <label class="form-label fw-semibold" style="color:#374151;">📧 Hospital Email</label>
                  <input type="email" name="email" class="form-control form-control-lg"
                         style="border-radius:12px;border:2px solid #e5e7eb;"
                         placeholder="hospital@example.com"
                         value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-4">
                  <label class="form-label fw-semibold" style="color:#374151;">🔒 Password</label>
                  <input type="password" name="password" class="form-control form-control-lg"
                         style="border-radius:12px;border:2px solid #e5e7eb;"
                         placeholder="Your password" required>
                </div>
                <button type="submit" class="btn w-100 py-3 fw-bold fs-5 rounded-pill border-0"
                        style="background:linear-gradient(135deg,#0891b2,#003087,#4c1d95);color:#fff;
                               box-shadow:0 4px 20px rgba(8,145,178,.35);">
                  🏥 Login to Hospital Portal
                </button>
              </form>

             
              <div class="text-center mt-4 text-muted small">
                New hospital?
                <a href="hospital_register.php" class="fw-bold text-decoration-none" style="color:#0891b2;">Register here</a>
                &nbsp;·&nbsp; Need help? Contact
                <a href="contact.php" class="fw-bold text-decoration-none" style="color:#0891b2;">DBU Blood Bank Admin</a>
              </div>
              <div class="text-center mt-2">
                <a href="login.php" class="text-muted" style="font-size:.85rem;">← Donor Login</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Info Panel -->
        <div class="col-md-6">
          <div class="card border-0 p-4 mb-4"
               style="border-radius:18px;background:linear-gradient(160deg,#eff6ff,#ecfeff);
                      border-left:5px solid #0891b2;">
            <h5 class="fw-bold mb-3" style="color:#0891b2;">🏥 Hospital Portal Features</h5>
            <ul class="list-unstyled mb-0" style="font-size:.9rem;">
              <li class="mb-2 d-flex gap-2"><span style="color:#059669;">✓</span> Submit blood requests for patients</li>
              <li class="mb-2 d-flex gap-2"><span style="color:#059669;">✓</span> View current blood stock availability</li>
              <li class="mb-2 d-flex gap-2"><span style="color:#059669;">✓</span> Track request status in real time</li>
              <li class="mb-2 d-flex gap-2"><span style="color:#059669;">✓</span> Emergency urgent request submission</li>
              <li class="mb-2 d-flex gap-2"><span style="color:#059669;">✓</span> Contact DBU Blood Bank team directly</li>
            </ul>
          </div>
          <div class="card border-0 p-4"
               style="border-radius:18px;background:linear-gradient(160deg,#fff1f2,#fce7f3);
                      border-left:5px solid #e11d48;">
            <h6 class="fw-bold mb-2" style="color:#e11d48;">🆘 Emergency Blood Request?</h6>
            <p class="text-muted small mb-2">
              For urgent blood needs outside portal hours, call directly:
            </p>
            <div class="fw-bold" style="color:#CC0000;font-size:1.1rem;">📞 +251 11 681 2345</div>
            <div class="text-muted small mt-1">Available Mon–Fri, 8AM–5PM</div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
