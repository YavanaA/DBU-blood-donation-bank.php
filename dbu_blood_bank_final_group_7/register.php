<?php
/**
 * Donor Registration — DBU Blood Bank
 * Task 3: Supports 'Unknown' blood type registration
 */
$page_title = 'Donor Registration';
require_once 'config.php';

if (is_logged_in()) redirect('dashboard.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = sanitize($conn, $_POST['name']     ?? '');
    $email        = sanitize($conn, $_POST['email']    ?? '');
    $blood        = sanitize($conn, $_POST['blood_type']?? '');
    $phone        = sanitize($conn, $_POST['phone']    ?? '');
    $dbu_id       = sanitize($conn, $_POST['dbu_id']   ?? '');
    $planned_date = sanitize($conn, $_POST['planned_donation_date'] ?? '');
    $password     = $_POST['password']         ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    // Task 3: Allow 'Unknown' as a valid blood type
    $valid_blood_types = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];

    if (!$name || !$email || !$blood || !$phone || !$dbu_id || !$planned_date || !$password) {
        $error = 'All fields are required.';
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = 'Name must contain only letters and spaces.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    } elseif (!in_array($blood, $valid_blood_types)) {
        $error = 'Please select a valid blood type.';
    } elseif (strtotime($planned_date) < strtotime(date('Y-m-d'))) {
        $error = 'Planned donation date cannot be in the past.';
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{6,}$/", $password)) {
        $error = 'Password must be at least 6 characters and include uppercase, lowercase, number, and special character.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt_chk = $conn->prepare("SELECT id FROM donors WHERE email = ? LIMIT 1");
        $stmt_chk->bind_param('s', $email);
        $stmt_chk->execute();
        $stmt_chk->store_result();

        if ($stmt_chk->num_rows > 0) {
            $error = 'This email is already registered. Please login.';
            $stmt_chk->close();
        } else {
            $stmt_chk->close();
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Task 3: If blood_type is 'Unknown', eligibility_status starts as 'pending_verification'
            $eligibility = $blood === 'Unknown' ? 'pending_verification' : 'eligible';

            $stmt = $conn->prepare("INSERT INTO donors (name, email, blood_type, phone, dbu_id, password, eligibility_status, planned_donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssss', $name, $email, $blood, $phone, $dbu_id, $hashed, $eligibility, $planned_date);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                $_SESSION['donor_id']    = $new_id;
                $_SESSION['donor_name']  = $name;
                $_SESSION['donor_blood'] = $blood;
                redirect('dashboard.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}

$blood_types = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];
require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="text-white py-5 text-center"
     style="background:linear-gradient(135deg,#064e3b 0%,#059669 50%,#0891b2 100%);
            position:relative;overflow:hidden;">
  <div style="position:relative; z-index: 2;">
    <div style="font-size:48px;margin-bottom:8px;">🩸</div>
    <h1 class="fw-bold fs-2">Join as a Donor</h1>
    <p style="color:rgba(255,255,255,.85);">Create your account and start saving lives at DBU</p>
  </div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card border-0 shadow-lg" style="border-radius:20px;">
        <div class="p-4 p-md-5">

          <?php if ($error): ?>
          <div class="alert alert-danger border-0 rounded-3 mb-4">
              ⚠ <?= htmlspecialchars($error) ?>
          </div>
          <?php endif; ?>

          <!-- Task 3 notice: Unknown blood type allowed -->
          <div class="alert alert-info border-0 rounded-3 mb-4 d-flex align-items-start gap-2" style="background:#eff6ff;border-left:4px solid #3b82f6!important;">
            <span style="font-size:1.2rem;">ℹ</span>
            <div class="small">
              <strong>Don't know your blood type?</strong> Select <strong>"Unknown"</strong> — our lab team will verify and update it after your first visit. You will receive an email notification once confirmed.
            </div>
          </div>

          <form method="POST">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">FULL NAME</label>
                <input type="text" name="name" class="form-control py-2" placeholder="Enter your full name"
                       pattern="[a-zA-Z\s]+" title="Letters and spaces only"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">EMAIL ADDRESS</label>
                <input type="email" name="email" class="form-control py-2" placeholder="name@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">BLOOD TYPE</label>
                <select name="blood_type" class="form-select py-2" required>
                  <option value="">Choose...</option>
                  <?php foreach ($blood_types as $bt): ?>
                    <option value="<?= $bt ?>" <?= (($_POST['blood_type'] ?? '') === $bt) ? 'selected' : '' ?>>
                      <?= $bt ?><?= $bt === 'Unknown' ? ' — (Lab will verify)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">PHONE NUMBER</label>
                <input type="tel" name="phone" class="form-control py-2" placeholder="09xxxxxxxx"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold small text-secondary">DBU ID NUMBER</label>
                <input type="text" name="dbu_id" class="form-control py-2" placeholder="DBU/XXXX/XX"
                       value="<?= htmlspecialchars($_POST['dbu_id'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-bold small text-secondary">PLANNED DONATION DATE</label>
                <input type="date" name="planned_donation_date" class="form-control py-2"
                       min="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($_POST['planned_donation_date'] ?? '') ?>" required>
                <div class="text-muted small mt-1">Select the date you plan to visit the campus clinic to donate blood.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">PASSWORD</label>
                <input type="password" name="password" class="form-control py-2" placeholder="Min 6 chars, upper, lower, number, symbol" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">CONFIRM PASSWORD</label>
                <input type="password" name="confirm_password" class="form-control py-2" placeholder="Repeat password" required>
              </div>
              <div class="col-12 mt-4">
                <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow-sm"
                        style="background:linear-gradient(135deg,#059669,#0891b2);border:none;">
                  ✓ Register as Donor
                </button>
              </div>
            </div>
          </form>

          <div class="text-center mt-4">
            <span class="text-muted">Already have an account?</span>
            <a href="login.php" class="text-success fw-bold text-decoration-none"> Login here</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
