<?php
$page_title = 'Tips & Gallery';
require_once 'config.php';

$success = '';
$error   = '';

// ── Upload donation photos (2 at once) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $donor_id   = $_SESSION['donor_id'];
    $donor_name = $_SESSION['donor_name'];
    $caption    = sanitize($conn, $_POST['caption'] ?? '');
    $upload_dir = 'uploads/gallery/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $allowed  = ['jpg','jpeg','png','gif','webp'];
    $uploaded = 0;

    foreach (['photo1','photo2'] as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Only image files (jpg, png, gif, webp) are allowed.';
                break;
            }
            if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
                $error = 'Each photo must be under 5 MB.';
                break;
            }
            $filename  = 'donation_' . $donor_id . '_' . time() . '_' . $field . '.' . $ext;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $filename)) {
                $photo_path = $upload_dir . $filename;
                $esc_path   = $conn->real_escape_string($photo_path);
                $esc_name   = $conn->real_escape_string($donor_name);
                $esc_cap    = $conn->real_escape_string($caption);
                $conn->query("INSERT INTO donation_photos (donor_id, donor_name, photo_path, caption)
                              VALUES ($donor_id, '$esc_name', '$esc_path', '$esc_cap')");
                $uploaded++;
            }
        }
    }
    if (!$error) {
        if ($uploaded > 0) {
            $success = "$uploaded photo(s) uploaded to the gallery successfully!";
        } else {
            $error = 'Please select at least one photo to upload.';
        }
    }
}

// ── Gallery photos ─────────────────────────────────────────
$gallery = $conn->query("SELECT * FROM donation_photos ORDER BY created_at DESC LIMIT 30");
require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="dbu-hero text-white py-5 text-center">
  <h1 class="fw-black display-5">📖 Education & Gallery</h1>
  <p style="color:rgba(255,255,255,.8);">Learn who can donate, who should wait, how to prepare — plus share your donation moments!</p>
</div>

<div class="container py-5">

  <!-- ── TIP CARDS ──────────────────────────────────────── -->
  <div class="text-center mb-4">
    <h2 class="section-title">💡 Donation Tips</h2>
  </div>
  <div class="row g-4 mb-5">
    <!-- Who Can Donate -->
    <div class="col-md-4">
      <div class="card card-dbu p-4 h-100">
        <div class="fs-2 mb-2">❤️</div>
        <h5 class="fw-bold mb-3 pb-2" style="color:#003087;border-bottom:2px solid #f0f4fa;">Who Can Donate?</h5>
        <ul class="list-unstyled mb-0">
          <?php $can = ['Age 18–65 years old','Weight at least 50 kg','Good general health condition','Hemoglobin ≥ 12.5 g/dL','No recent illness or fever','Normal blood pressure','DBU student, staff, or community'];
          foreach ($can as $c): ?>
          <li class="py-2 border-bottom small text-muted d-flex gap-2"><span class="text-success fw-bold">✓</span> <?= $c ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <!-- Who Cannot -->
    <div class="col-md-4">
      <div class="card card-dbu p-4 h-100">
        <div class="fs-2 mb-2">🚫</div>
        <h5 class="fw-bold mb-3 pb-2" style="color:#CC0000;border-bottom:2px solid #f0f4fa;">Who Cannot Donate?</h5>
        <ul class="list-unstyled mb-0">
          <?php $cannot = ['Pregnant or breastfeeding women','Recent surgery (within 6 months)','HIV, Hepatitis B or C positive','Donated blood within 3 months','Active infection or fever','Taking blood thinners','History of heart disease'];
          foreach ($cannot as $c): ?>
          <li class="py-2 border-bottom small text-muted d-flex gap-2"><span class="text-danger fw-bold">✗</span> <?= $c ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <!-- Pre-Donation Tips -->
    <div class="col-md-4">
      <div class="card card-dbu p-4 h-100">
        <div class="fs-2 mb-2">📋</div>
        <h5 class="fw-bold mb-3 pb-2" style="color:#003087;border-bottom:2px solid #f0f4fa;">Pre-Donation Tips</h5>
        <ul class="list-unstyled mb-0">
          <?php $tips = ['Drink 500ml of water before donating','Eat a light, iron-rich meal','Avoid fatty foods 4 hours before','Get a good night\'s sleep','Avoid alcohol for 24 hours before','Wear comfortable, loose clothing','Bring your DBU ID card'];
          foreach ($tips as $t): ?>
          <li class="py-2 border-bottom small text-muted d-flex gap-2"><span style="color:#003087;">●</span> <?= $t ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- DIVIDER -->
  <div style="height:2px;background:linear-gradient(90deg,transparent,#FFD700,transparent);" class="my-5"></div>

  <!-- ── UPLOAD SECTION ─────────────────────────────────── -->
  <div class="text-center mb-4">
    <h2 class="section-title">📸 Donation Gallery</h2>
    <p class="text-muted">Share your donation moments and inspire others in the DBU community!</p>
  </div>

  <div class="card card-dbu p-4 mx-auto mb-5" style="max-width:680px;">
    <h5 class="fw-bold mb-3" style="color:#003087;">↑ Upload Your Donation Photos</h5>
    <?php if ($success): ?>
      <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (is_logged_in()): ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Photo 1 <span class="text-danger">*</span></label>
          <input type="file" name="photo1" accept="image/*" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Photo 2 <span class="text-muted">(Optional)</span></label>
          <input type="file" name="photo2" accept="image/*" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Caption / Message</label>
          <textarea name="caption" class="form-control" rows="3"
                    placeholder="Share your experience or a motivational message..."></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-dbu-blue rounded-pill px-4">📷 Upload to Gallery</button>
        </div>
      </div>
    </form>
    <?php else: ?>
    <div class="alert alert-info">
      🔒 You need to be <a href="login.php" class="fw-bold">logged in as a donor</a> to upload photos.
      <a href="register.php" class="fw-bold">Register now</a> if you don't have an account.
    </div>
    <?php endif; ?>
  </div>

  <!-- ── GALLERY DISPLAY ────────────────────────────────── -->
  <?php if ($gallery->num_rows === 0): ?>
    <div class="text-center py-5 text-muted">
      <div style="font-size:60px;">📷</div>
      <p class="mt-3">No photos in the gallery yet. Be the first to share your donation moment!</p>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php while ($photo = $gallery->fetch_assoc()): ?>
      <div class="col-md-3 col-sm-4 col-6">
        <div class="card h-100" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 4px 18px rgba(0,48,135,.07);transition:.2s;"
             onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
          <img src="<?= htmlspecialchars($photo['photo_path']) ?>" alt="Donation"
               style="width:100%;height:180px;object-fit:cover;display:block;"
               onerror="this.closest('.col-md-3').style.display='none'">
          <div class="p-3">
            <div class="fw-semibold small">👤 <?= htmlspecialchars($photo['donor_name']) ?></div>
            <?php if ($photo['caption']): ?>
              <div class="text-muted mt-1" style="font-size:.8rem;">"<?= htmlspecialchars($photo['caption']) ?>"</div>
            <?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.75rem;">📅 <?= date('d M Y', strtotime($photo['created_at'])) ?></div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <div class="text-center mt-4">
      <a href="gallery.php" class="btn btn-outline-primary rounded-pill px-4">View Full Gallery →</a>
    </div>
  <?php endif; ?>

</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
