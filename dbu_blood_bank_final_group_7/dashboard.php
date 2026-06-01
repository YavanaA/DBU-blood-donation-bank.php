<?php
$page_title = 'My Dashboard';
require_once 'config.php';
require_login();

$donor_id = $_SESSION['donor_id'];
$success  = '';
$error    = '';

$stmt_d = $conn->prepare("SELECT * FROM donors WHERE id = ? LIMIT 1");
$stmt_d->bind_param('i', $donor_id);
$stmt_d->execute();
$donor = $stmt_d->get_result()->fetch_assoc();
$stmt_d->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update') {
        $phone    = trim($_POST['phone']              ?? '');
        $password = $_POST['new_password']            ?? '';
        $last_don = trim($_POST['last_donation_date'] ?? '');

        $sets   = [];
        $types  = '';
        $values = [];

        if ($phone)    { $sets[] = "phone = ?";              $types .= 's'; $values[] = $phone; }
        if ($password) { $sets[] = "password = ?";           $types .= 's'; $values[] = password_hash($password, PASSWORD_DEFAULT); }
        if ($last_don) { $sets[] = "last_donation_date = ?"; $types .= 's'; $values[] = $last_don; }

        if ($sets) {
            $types   .= 'i';
            $values[] = $donor_id;
            $stmt_u   = $conn->prepare("UPDATE donors SET " . implode(', ', $sets) . " WHERE id = ?");
            $stmt_u->bind_param($types, ...$values);
            $stmt_u->execute();
            $stmt_u->close();
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Nothing to update.';
        }
    }

    if ($_POST['action'] === 'deactivate') {
        $stmt_da = $conn->prepare("UPDATE donors SET is_active = 0 WHERE id = ?");
        $stmt_da->bind_param('i', $donor_id);
        $stmt_da->execute();
        $stmt_da->close();
        session_destroy();
        redirect('index.php');
    }

    if ($_POST['action'] === 'upload_photo') {
        if (!empty($_FILES['photo']['name'])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Only image files (jpg, png, gif, webp) are allowed.';
            } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
                $error = 'File size must be under 5 MB.';
            } else {
                $filename = 'profile_' . $donor_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                    $photo_path = $upload_dir . $filename;
                    $stmt_ph = $conn->prepare("UPDATE donors SET profile_photo = ? WHERE id = ?");
                    $stmt_ph->bind_param('si', $photo_path, $donor_id);
                    $stmt_ph->execute();
                    $stmt_ph->close();
                    $success = 'Profile photo updated!';
                } else {
                    $error = 'Failed to upload photo. Check folder permissions.';
                }
            }
        } else {
            $error = 'Please select a photo to upload.';
        }
    }

    $stmt_d2 = $conn->prepare("SELECT * FROM donors WHERE id = ? LIMIT 1");
    $stmt_d2->bind_param('i', $donor_id);
    $stmt_d2->execute();
    $donor = $stmt_d2->get_result()->fetch_assoc();
    $stmt_d2->close();
}

// Eligibility Tracker: next donation date
$next_donation_info = '';
$can_donate_now     = true;
if (!empty($donor['last_donation_date']) && $donor['last_donation_date'] !== '0000-00-00') {
    $last_ts   = strtotime($donor['last_donation_date']);
    $next_ts   = strtotime('+3 months', $last_ts);
    $days_left = ceil(($next_ts - time()) / 86400);
    if ($days_left > 0) {
        $can_donate_now     = false;
        $next_donation_info = 'Next eligible donation: <strong>' . date('d F Y', $next_ts) . '</strong> (' . $days_left . ' day(s) remaining)';
    } else {
        $next_donation_info = 'You are <strong>eligible to donate now!</strong> Last donated: ' . date('d F Y', $last_ts);
    }
}

require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<!-- PROFILE HEADER -->
<div class="text-white py-4 px-3" style="background:linear-gradient(135deg,#003087,#001d6e);">
  <div class="container">
    <div class="d-flex align-items-center gap-4 flex-wrap">
      <div class="position-relative">
        <div class="rounded-circle overflow-hidden border border-4 d-flex align-items-center justify-content-center"
             style="width:100px;height:100px;border-color:#FFD700!important;background:#1e3a6e;font-size:36px;color:#fff;flex-shrink:0;">
          <?php if (!empty($donor['profile_photo']) && file_exists($donor['profile_photo'])): ?>
            <img src="<?= htmlspecialchars($donor['profile_photo']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            👤
          <?php endif; ?>
        </div>
      </div>
      <div>
        <h2 class="fw-black mb-2"><?= htmlspecialchars($donor['name']) ?></h2>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge px-3 py-2" style="background:rgba(204,0,0,.3);color:#ff8080;border:1px solid rgba(204,0,0,.4);">🩸 <?= htmlspecialchars($donor['blood_type']) ?></span>
          <span class="badge px-3 py-2" style="background:rgba(255,215,0,.15);color:#FFD700;border:1px solid rgba(255,215,0,.4);">🎓 <?= htmlspecialchars($donor['dbu_id']) ?></span>
          <span class="badge px-3 py-2" style="background:rgba(255,255,255,.1);color:#cdd5e0;border:1px solid rgba(255,255,255,.2);">📞 <?= htmlspecialchars($donor['phone']) ?></span>
          <span class="badge px-3 py-2" style="background:rgba(255,255,255,.1);color:#cdd5e0;border:1px solid rgba(255,255,255,.2);">Member since <?= date('M Y', strtotime($donor['created_at'])) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container py-5">

  <?php if ($success): ?>
  <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Eligibility Tracker Banner -->
  <?php if ($next_donation_info): ?>
  <div class="mb-4 p-4 rounded-3 d-flex align-items-center gap-3 flex-wrap"
       style="background:<?= $can_donate_now ? 'linear-gradient(135deg,#ecfdf5,#d1fae5)' : 'linear-gradient(135deg,#fffbeb,#fef3c7)' ?>;
              border:2px solid <?= $can_donate_now ? '#059669' : '#d97706' ?>;">
    <div style="font-size:2rem;"><?= $can_donate_now ? '✅' : '⏳' ?></div>
    <div>
      <div class="fw-bold" style="color:<?= $can_donate_now ? '#059669' : '#d97706' ?>;">
        <?= $can_donate_now ? 'Eligible to Donate!' : 'Donation Interval in Progress' ?>
      </div>
      <div class="text-muted small"><?= $next_donation_info ?></div>
    </div>
    <?php if ($can_donate_now): ?>
    <a href="register.php" class="btn btn-sm rounded-pill ms-auto fw-bold"
       style="background:linear-gradient(135deg,#059669,#0891b2);color:#fff;border:none;">
      💉 Donate Now
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Quick Actions -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <a href="certificate.php" class="text-decoration-none">
        <div class="card card-dbu p-3 text-center h-100 hover-lift">
          <div style="font-size:2rem;margin-bottom:8px;">🏅</div>
          <div class="fw-bold small" style="color:#003087;">My Certificate</div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="eligibility.php" class="text-decoration-none">
        <div class="card card-dbu p-3 text-center h-100">
          <div style="font-size:2rem;margin-bottom:8px;">✅</div>
          <div class="fw-bold small" style="color:#059669;">Check Eligibility</div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="search.php" class="text-decoration-none">
        <div class="card card-dbu p-3 text-center h-100">
          <div style="font-size:2rem;margin-bottom:8px;">🔍</div>
          <div class="fw-bold small" style="color:#7c3aed;">Find Donors</div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="request.php" class="text-decoration-none">
        <div class="card card-dbu p-3 text-center h-100">
          <div style="font-size:2rem;margin-bottom:8px;">🩸</div>
          <div class="fw-bold small" style="color:#CC0000;">Request Blood</div>
        </div>
      </a>
    </div>
  </div>

  <div class="row g-4">

    <!-- Profile Photo Upload -->
    <div class="col-md-6">
      <div class="card card-dbu p-4 h-100">
        <h5 class="fw-bold mb-3" style="color:#003087;">📷 Profile Photo</h5>
        <?php if (!empty($donor['profile_photo']) && file_exists($donor['profile_photo'])): ?>
          <img src="<?= htmlspecialchars($donor['profile_photo']) ?>" alt="Current photo"
               class="rounded mb-3" style="width:90px;height:90px;object-fit:cover;border:2px solid #e5e7eb;">
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_photo">
          <div class="mb-3">
            <label class="form-label fw-semibold">Upload New Photo</label>
            <input type="file" name="photo" accept="image/*" class="form-control">
          </div>
          <button type="submit" class="btn btn-dbu-blue btn-sm rounded-pill px-4">↑ Upload Photo</button>
        </form>
      </div>
    </div>

    <!-- Update Profile -->
    <div class="col-md-6">
      <div class="card card-dbu p-4 h-100">
        <h5 class="fw-bold mb-3" style="color:#003087;">✏ Update Profile</h5>
        <form method="POST">
          <input type="hidden" name="action" value="update">
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone Number</label>
            <input type="tel" name="phone" class="form-control" placeholder="<?= htmlspecialchars($donor['phone']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">New Password</label>
            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">📅 Last Donation Date</label>
            <input type="date" name="last_donation_date" class="form-control"
                   max="<?= date('Y-m-d') ?>"
                   value="<?= htmlspecialchars($donor['last_donation_date'] ?? '') ?>">
            <div class="text-muted small mt-1">Used to track your next eligible donation date.</div>
          </div>
          <button type="submit" class="btn btn-dbu-blue rounded-pill px-4">Save Changes</button>
        </form>
      </div>
    </div>

    <!-- Account Deactivation -->
    <div class="col-12">
      <div class="card card-dbu p-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">⚠ Account Management</h5>
        <div class="rounded-3 p-4" style="background:#fff5f5;border:1px solid #fecaca;">
          <h6 class="text-danger fw-bold">Deactivate Account</h6>
          <p class="text-muted small mb-3">Deactivating your account will remove you from the donor search results. You will be logged out and need to contact admin to reactivate. This action cannot be undone easily.</p>
          <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate your account?');">
            <input type="hidden" name="action" value="deactivate">
            <button type="submit" class="btn btn-outline-danger rounded-pill px-4">Deactivate My Account</button>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
