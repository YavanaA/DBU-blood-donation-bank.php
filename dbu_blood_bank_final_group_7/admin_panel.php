<?php
/**
  *Admin Panel — DBU Blood Bank Management System
  *Instructor : Mr. Getachew
  *University : Debre Berhan University
  *Tabs: Donors | Requests | Blood Stock | Gallery | Messages | System Settings
 */
$page_title = 'Admin Panel';
require_once 'config.php';
require_admin();

$success = '';
$error   = '';

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['delete_donor'])) {
        $id = (int)$_POST['delete_donor'];
        $conn->query("DELETE FROM donors WHERE id=$id");
        $success = 'Donor deleted.';
    }
    if (isset($_POST['toggle_active'])) {
        $id  = (int)$_POST['toggle_active'];
        $val = (int)$_POST['current_active'] === 1 ? 0 : 1;
        $conn->query("UPDATE donors SET is_active=$val WHERE id=$id");
        $success = 'Donor status updated.';
    }
    // ── Task 2: Approve with Real-Time Inventory Deduction ───
    if (isset($_POST['approve_request'])) {
        $id     = (int)$_POST['approve_request'];
        $result = $db->approveRequestWithDeduction($id);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    if (isset($_POST['delete_request'])) {
        $id = (int)$_POST['delete_request'];
        $db->returnStockOnDeletedRequest($id);
        $conn->query("DELETE FROM blood_requests WHERE id=$id");
        $success = 'Blood request deleted.';
    }
    if (isset($_POST['update_stock'])) {
        $type   = trim($_POST['blood_type_stock'] ?? '');
        $units  = (int)$_POST['units'];
        $status = trim($_POST['stock_status'] ?? '');
        $allowed_types   = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
        $allowed_statuses = ['available','low','critical','unavailable'];
        if (in_array($type, $allowed_types) && in_array($status, $allowed_statuses)) {
            $stmt_sc = $conn->prepare("SELECT id FROM blood_stock WHERE blood_type = ?");
            $stmt_sc->bind_param('s', $type);
            $stmt_sc->execute();
            $stmt_sc->store_result();
            if ($stmt_sc->num_rows > 0) {
                $stmt_sc->close();
                $stmt_su = $conn->prepare("UPDATE blood_stock SET units = ?, status = ?, updated_at = NOW() WHERE blood_type = ?");
                $stmt_su->bind_param('iss', $units, $status, $type);
                $stmt_su->execute();
                $stmt_su->close();
            } else {
                $stmt_sc->close();
                $stmt_si = $conn->prepare("INSERT INTO blood_stock (blood_type, units, status) VALUES (?, ?, ?)");
                $stmt_si->bind_param('sis', $type, $units, $status);
                $stmt_si->execute();
                $stmt_si->close();
            }
        }
        $success = 'Blood stock updated.';
    }
    if (isset($_POST['delete_photo'])) {
        $id    = (int)$_POST['delete_photo'];
        $photo = $conn->query("SELECT photo_path FROM donation_photos WHERE id=$id")->fetch_assoc();
        if ($photo && file_exists($photo['photo_path'])) unlink($photo['photo_path']);
        $conn->query("DELETE FROM donation_photos WHERE id=$id");
        $success = 'Photo deleted.';
    }
    if (isset($_POST['admin_upload_photo'])) {
        if (!empty($_FILES['gallery_photo']['name'])) {
            $file      = $_FILES['gallery_photo'];
            $allowed   = ['image/jpeg','image/png','image/gif','image/webp'];
            $max_size  = 5 * 1024 * 1024;
            if (!in_array($file['type'], $allowed)) {
                $error = 'Invalid file type. Please upload a JPG, PNG, GIF or WEBP image.';
            } elseif ($file['size'] > $max_size) {
                $error = 'File too large. Maximum size is 5 MB.';
            } else {
                $upload_dir = __DIR__ . '/uploads/gallery/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename = 'admin_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                    $photo_path = 'uploads/gallery/' . $filename;
                    $donor_name = trim($_POST['gallery_donor_name'] ?? 'Admin');
                    $caption    = trim($_POST['gallery_caption']    ?? '');
                    $stmt_gp = $conn->prepare("INSERT INTO donation_photos (donor_id, donor_name, photo_path, caption) VALUES (NULL, ?, ?, ?)");
                    $stmt_gp->bind_param('sss', $donor_name, $photo_path, $caption);
                    $stmt_gp->execute();
                    $stmt_gp->close();
                    $success = 'Gallery photo uploaded successfully.';
                    $photos  = $conn->query("SELECT * FROM donation_photos ORDER BY created_at DESC LIMIT 20");
                } else {
                    $error = 'Upload failed. Check folder permissions for uploads/gallery/';
                }
            }
        } else {
            $error = 'No photo file selected.';
        }
    }
    if (isset($_POST['send_reply'])) {
        $id         = (int)$_POST['send_reply'];
        $reply_text = trim($_POST['reply_text'] ?? '');
        if ($reply_text === '') {
            $error = 'Reply message cannot be empty.';
        } else {
            $stmt_rp = $conn->prepare("UPDATE contact_messages SET status = 'replied', admin_reply = ? WHERE id = ?");
            $stmt_rp->bind_param('si', $reply_text, $id);
            $stmt_rp->execute();
            $stmt_rp->close();
            $success = 'Reply sent and message marked as replied.';
        }
    }

    // ── Task 3: Update Donor Blood Type from 'Unknown' ───────
    if (isset($_POST['update_blood_type'])) {
        $donor_id      = (int)$_POST['update_bt_donor_id'];
        $new_blood_type = sanitize($conn, $_POST['new_blood_type'] ?? '');
        $allowed_types  = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];

        if (!$donor_id || !in_array($new_blood_type, $allowed_types)) {
            $error = 'Invalid donor or blood type selection.';
        } else {
            // Fetch current donor info
            $stmt_dt = $conn->prepare("SELECT name, email, blood_type FROM donors WHERE id = ? LIMIT 1");
            $stmt_dt->bind_param('i', $donor_id);
            $stmt_dt->execute();
            $donor_bt = $stmt_dt->get_result()->fetch_assoc();
            $stmt_dt->close();

            if ($donor_bt && strtolower($donor_bt['blood_type']) === 'unknown') {
                // Update blood type and eligibility
                $stmt_upd = $conn->prepare(
                    "UPDATE donors SET blood_type = ?, eligibility_status = 'eligible', status = 'active' WHERE id = ?"
                );
                $stmt_upd->bind_param('si', $new_blood_type, $donor_id);
                $stmt_upd->execute();
                $stmt_upd->close();

                // Task 3: Dispatch real email via PHPMailer + audit log
                $subject = 'DBU Blood Bank — Your Blood Type Has Been Verified';
                $body    = "Dear {$donor_bt['name']},\n\nThank you for your recent visit to the DBU Blood Bank laboratory. Your serological blood typing analysis is now finalized.\n\nYour verified blood group is: {$new_blood_type}\n\nYou are now fully activated in our donor network and eligible to save lives. Please log in to your donor dashboard to view your complete profile.\n\nWith gratitude,\nDBU Blood Bank Medical Team\nDebre Berhan University";
                $emailSent = $db->logNotification($donor_id, $donor_bt['email'], $subject, $body, 'blood_type_verified', $donor_bt['name']);
                $emailBadge = $emailSent
                    ? '<span class="badge bg-success ms-1">📧 Email Sent to ' . htmlspecialchars($donor_bt['email']) . '</span>'
                    : '<span class="badge bg-secondary ms-1">📋 Logged — Enable SMTP in mail_config.php</span>';

                $success = "✓ Blood type updated to <strong>{$new_blood_type}</strong> for donor <strong>{$donor_bt['name']}</strong>. {$emailBadge}";
            } elseif ($donor_bt) {
                // Allow updating any blood type (admin correction)
                $stmt_upd2 = $conn->prepare("UPDATE donors SET blood_type = ? WHERE id = ?");
                $stmt_upd2->bind_param('si', $new_blood_type, $donor_id);
                $stmt_upd2->execute();
                $stmt_upd2->close();
                $success = "Blood type updated to {$new_blood_type} for {$donor_bt['name']}.";
            } else {
                $error = 'Donor not found.';
            }
        }
    }

    // ── Hospital Management ──────────────────────────────────
    if (isset($_POST['activate_hospital'])) {
        $id = (int)$_POST['activate_hospital'];
        $conn->query("UPDATE hospitals SET is_active=1 WHERE id=$id");
        $success = 'Hospital account activated.';
    }
    if (isset($_POST['deactivate_hospital'])) {
        $id = (int)$_POST['deactivate_hospital'];
        $conn->query("UPDATE hospitals SET is_active=0 WHERE id=$id");
        $success = 'Hospital account deactivated.';
    }
    if (isset($_POST['delete_hospital'])) {
        $id = (int)$_POST['delete_hospital'];
        $conn->query("DELETE FROM hospitals WHERE id=$id");
        $success = 'Hospital deleted.';
    }

    // ── Task 2: Upload University Logo ──────────────────────────
    if (isset($_POST['upload_logo'])) {
        if (!empty($_FILES['logo_file']['name'])) {
            $file     = $_FILES['logo_file'];
            $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
            $max_size = 2 * 1024 * 1024; // 2 MB

            if (!in_array($file['type'], $allowed)) {
                $error = 'Invalid file type. Please upload a JPG, PNG, GIF or WEBP image.';
            } elseif ($file['size'] > $max_size) {
                $error = 'File too large. Maximum size is 2 MB.';
            } else {
                /* Save the logo to assets/images/logo/ */
                $logo_dir = __DIR__ . '/assets/images/logo/';
                if (!is_dir($logo_dir)) mkdir($logo_dir, 0755, true);

                $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
                $logo_name = 'university_logo.' . strtolower($ext);
                $logo_path = $logo_dir . $logo_name;

                if (move_uploaded_file($file['tmp_name'], $logo_path)) {
                    /* Store relative web path in system_settings */
                    $rel_path = 'assets/images/logo/' . $logo_name;
                    $rel_path = $conn->real_escape_string($rel_path);
                    $conn->query("INSERT INTO system_settings (setting_name, setting_value)
                                  VALUES ('logo_url', '$rel_path')
                                  ON DUPLICATE KEY UPDATE setting_value='$rel_path', updated_at=NOW()");
                    $success = 'University logo uploaded successfully.';
                } else {
                    $error = 'Upload failed. Check folder permissions for assets/images/logo/';
                }
            }
        } elseif (isset($_POST['remove_logo'])) {
            $conn->query("UPDATE system_settings SET setting_value=NULL WHERE setting_name='logo_url'");
            $success = 'Logo removed. "DBU" text will be shown as fallback.';
        } else {
            $error = 'No file selected.';
        }
    }
}

// ── Stats ─────────────────────────────────────────────────────
$total_donors      = $conn->query("SELECT COUNT(*) c FROM donors")->fetch_assoc()['c'];
$active_donors     = $conn->query("SELECT COUNT(*) c FROM donors WHERE is_active=1")->fetch_assoc()['c'];
$total_requests    = $conn->query("SELECT COUNT(*) c FROM blood_requests")->fetch_assoc()['c'];
$pending_req       = $conn->query("SELECT COUNT(*) c FROM blood_requests WHERE status='pending'")->fetch_assoc()['c'];
$new_messages      = $conn->query("SELECT COUNT(*) c FROM contact_messages WHERE status='new'")->fetch_assoc()['c'];
$total_hospitals   = $conn->query("SELECT COUNT(*) c FROM hospitals")->fetch_assoc()['c'];
$pending_hospitals = $conn->query("SELECT COUNT(*) c FROM hospitals WHERE is_active=0")->fetch_assoc()['c'];

// ── Donor search / filter ─────────────────────────────────────
$search       = sanitize($conn, $_GET['search'] ?? '');
$blood_filter = sanitize($conn, $_GET['blood_type'] ?? '');
$where        = "WHERE 1=1";
if ($search)       $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR dbu_id LIKE '%$search%')";
if ($blood_filter) $where .= " AND blood_type='$blood_filter'";
$donors = $conn->query("SELECT * FROM donors $where ORDER BY created_at DESC");

// ── Table A: Registered Non-Donors (Task 5) ───────────────────
$non_donors_res = $conn->query(
    "SELECT d.id, d.name, d.email, d.blood_type, d.phone, d.dbu_id, d.created_at
     FROM donors d
     WHERE d.is_active = 1
       AND d.id NOT IN (SELECT donor_id FROM test_results)
     ORDER BY d.created_at ASC"
);
$non_donors = $non_donors_res ? $non_donors_res->fetch_all(MYSQLI_ASSOC) : [];

// ── Table B: Verified Blood Donors (has test_results) ─────────
$verified_donors_res = $conn->query(
    "SELECT d.id, d.name, d.email, d.blood_type, d.phone, d.dbu_id,
            tr.overall_outcome, tr.recorded_at AS screened_at,
            tr.hiv_status, tr.hepatitis_status, tr.syphilis_status
     FROM donors d
     INNER JOIN test_results tr ON tr.donor_id = d.id
     ORDER BY tr.recorded_at DESC"
);
$verified_donors = $verified_donors_res ? $verified_donors_res->fetch_all(MYSQLI_ASSOC) : [];

// ── Blood Requests ────────────────────────────────────────────
$requests = $conn->query("SELECT * FROM blood_requests ORDER BY created_at DESC");

// ── Blood Stock ───────────────────────────────────────────────
$stock_res = $conn->query("SELECT * FROM blood_stock ORDER BY blood_type");

// ── Gallery Photos ────────────────────────────────────────────
$photos = $conn->query("SELECT * FROM donation_photos ORDER BY created_at DESC LIMIT 20");

// ── Contact Messages ──────────────────────────────────────────
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

// ── System Settings ───────────────────────────────────────────
$current_logo = '';
$logo_res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_name='logo_url'");
if ($logo_res && $logo_row = $logo_res->fetch_assoc()) {
    $current_logo = $logo_row['setting_value'] ?? '';
}

$blood_types = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
require 'includes/head.php';
?>
<style>
  body { background: linear-gradient(160deg,#f0f4ff,#fdf4ff,#ecfdf5); background-attachment: fixed; }

  /* Stat cards — each a unique gradient */
  .stat-card {
    border-radius: 18px; border: none;
    color: #fff; padding: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,.13);
    transition: .22s;
    position: relative; overflow: hidden;
  }
  .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 32px rgba(0,0,0,.18); }
  .stat-card::after {
    content: '';
    position: absolute; right: -16px; top: -16px;
    width: 90px; height: 90px; border-radius: 50%;
    background: rgba(255,255,255,.1);
  }
  .sc-blue   { background: linear-gradient(135deg,#003087,#4338ca); }
  .sc-green  { background: linear-gradient(135deg,#059669,#0891b2); }
  .sc-red    { background: linear-gradient(135deg,#CC0000,#e11d48); }
  .sc-amber  { background: linear-gradient(135deg,#d97706,#F59E0B); }
  .sc-purple { background: linear-gradient(135deg,#7c3aed,#db2777); }

  /* Section cards */
  .section-card {
    border-radius: 18px; border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
    background: #fff;
  }

  /* Tabs */
  .admin-nav-tab {
    background: transparent; border: none;
    padding: 10px 18px; border-radius: 12px;
    cursor: pointer; font-weight: 600;
    color: #6b7280; text-decoration: none;
    display: inline-block; transition: .18s;
    font-size: .88rem;
  }
  .admin-nav-tab:hover { background: rgba(0,48,135,.07); color: #003087; }
  .admin-nav-tab.active {
    background: linear-gradient(135deg,#003087,#7c3aed);
    color: #fff;
    box-shadow: 0 4px 14px rgba(124,58,237,.3);
  }

  /* Logo preview */
  .logo-preview {
    width: 100px; height: 100px; border-radius: 50%;
    object-fit: cover;
    border: 4px solid #7c3aed;
    box-shadow: 0 4px 20px rgba(124,58,237,.3);
  }

  /* Table header */
  .table thead th { background: linear-gradient(135deg,#003087,#4338ca); color: #fff; border: none; }
  .table thead th:first-child { border-radius: 10px 0 0 0; }
  .table thead th:last-child  { border-radius: 0 10px 0 0; }
</style>
</head>
<body>

<!-- ADMIN NAV -->
<nav class="navbar navbar-dark px-4 py-2"
     style="background:linear-gradient(135deg,#003087 0%,#1e1b5e 50%,#4c1d95 100%);
            box-shadow:0 4px 20px rgba(0,0,0,.35);">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <?php if ($current_logo): ?>
        <img src="<?= htmlspecialchars($current_logo) ?>" alt="DBU Logo"
             style="width:38px;height:38px;border-radius:50%;object-fit:cover;
                    border:2px solid #F59E0B;flex-shrink:0;
                    box-shadow:0 0 0 3px rgba(245,158,11,.3);">
      <?php else: ?>
        <div style="width:38px;height:38px;border-radius:50%;flex-shrink:0;
             background:linear-gradient(135deg,#CC0000,#7c3aed);
             border:2px solid #F59E0B;
             box-shadow:0 0 0 3px rgba(245,158,11,.3);
             display:flex;align-items:center;justify-content:center;
             font-size:11px;font-weight:900;color:#fff;">DBU</div>
      <?php endif; ?>
      <span class="fw-bold">
        DBU <span style="color:#F59E0B;">Blood</span>Bank
        <small style="color:rgba(255,255,255,.6);font-size:11px;display:block;line-height:1;">Admin Panel</small>
      </span>
    </a>
    <div class="d-flex align-items-center gap-3">
      <a href="index.php" class="text-decoration-none small"
         style="color:rgba(255,255,255,.6);">← Main Site</a>
      <span style="color:#F59E0B;" class="small fw-semibold">🛡 <?= htmlspecialchars($_SESSION['admin_email']) ?></span>
      <a href="logout.php" class="btn btn-sm rounded-pill fw-semibold"
         style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border:none;">Logout</a>
    </div>
  </div>
</nav>

<!-- Page Header -->
<div class="text-white py-4 px-4"
     style="background:linear-gradient(135deg,#003087 0%,#1e1b5e 50%,#4c1d95 80%,#7c3aed 100%);
            position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 70% 50%,rgba(245,158,11,.1),transparent 60%);
       pointer-events:none;"></div>
  <div class="container" style="position:relative;">
    <h2 class="fw-black mb-1">🛡 Admin Panel</h2>
    <p class="mb-0 small" style="color:rgba(255,255,255,.65);">
      Manage donors, blood requests, stock, gallery, messages and system settings
    </p>
  </div>
</div>

<div class="container py-4">

  <?php if ($success): ?>
    <div class="alert alert-success rounded-3 border-0 shadow-sm">✓ <?= $success ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger rounded-3 border-0 shadow-sm">✗ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- STATS ROW -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card sc-blue">
        <div style="font-size:1.9rem;font-weight:900;line-height:1;"><?= $total_donors ?></div>
        <div class="small text-uppercase fw-bold mt-1" style="color:rgba(255,255,255,.75);letter-spacing:.8px;">👥 Total Donors</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card sc-green">
        <div style="font-size:1.9rem;font-weight:900;line-height:1;"><?= $active_donors ?></div>
        <div class="small text-uppercase fw-bold mt-1" style="color:rgba(255,255,255,.75);letter-spacing:.8px;">✅ Active Donors</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card sc-red">
        <div style="font-size:1.9rem;font-weight:900;line-height:1;"><?= $total_requests ?></div>
        <div class="small text-uppercase fw-bold mt-1" style="color:rgba(255,255,255,.75);letter-spacing:.8px;">🩸 Blood Requests</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card sc-amber">
        <div style="font-size:1.9rem;font-weight:900;line-height:1;"><?= $pending_req ?></div>
        <div class="small text-uppercase fw-bold mt-1" style="color:rgba(255,255,255,.75);letter-spacing:.8px;">⏳ Pending Req.</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card sc-purple">
        <div style="font-size:1.9rem;font-weight:900;line-height:1;"><?= $total_hospitals ?></div>
        <div class="small text-uppercase fw-bold mt-1" style="color:rgba(255,255,255,.75);letter-spacing:.8px;">🏨 Hospitals<?= $pending_hospitals > 0 ? " <span style='font-size:1rem;'>($pending_hospitals pending)</span>" : '' ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card" style="background:linear-gradient(135deg,#0891b2,#059669);">
        <div style="font-size:1.9rem;font-weight:900;line-height:1;"><?= $new_messages ?></div>
        <div class="small text-uppercase fw-bold mt-1" style="color:rgba(255,255,255,.75);letter-spacing:.8px;">📞 New Messages</div>
      </div>
    </div>
  </div>

  <!-- TAB NAVIGATION -->
  <div class="d-flex flex-wrap gap-2 mb-4 p-3 rounded-3 shadow-sm align-items-center"
       style="background:#fff;border-top:4px solid;
              border-image:linear-gradient(90deg,#003087,#7c3aed,#059669,#F59E0B,#CC0000) 1;">
    <?php
    $tabs = [
      'donors'    => '👥 Donors',
      'requests'  => '🩸 Requests' . ($pending_req > 0 ? " <span class='badge bg-danger'>$pending_req</span>" : ''),
      'stock'     => '🏥 Blood Stock',
      'gallery'   => '🖼 Gallery',
      'messages'  => '📞 Messages' . ($new_messages > 0 ? " <span class='badge bg-danger'>$new_messages</span>" : ''),
      'hospitals' => '🏨 Hospitals' . ($pending_hospitals > 0 ? " <span class='badge bg-warning text-dark'>$pending_hospitals</span>" : ''),
      'settings'  => '⚙️ Settings',
    ];
    $active_tab = $_GET['tab'] ?? 'donors';
    foreach ($tabs as $slug => $label): ?>
      <a href="admin_panel.php?tab=<?= $slug ?>" class="admin-nav-tab <?= $active_tab === $slug ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <div class="ms-auto d-flex gap-2 align-items-center flex-shrink-0">
      <a href="test_results.php"
         class="btn btn-sm rounded-pill fw-bold px-3"
         style="background:linear-gradient(135deg,#059669,#0891b2);color:#fff;border:none;">
        🔬 Lab Screening
      </a>
      <a href="notifications_log.php"
         class="btn btn-sm rounded-pill fw-bold px-3"
         style="background:linear-gradient(135deg,#7c3aed,#db2777);color:#fff;border:none;">
        📧 Notifications
      </a>
      <a href="pdf_report.php" target="_blank"
         class="btn btn-sm rounded-pill fw-bold px-3"
         style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border:none;">
        📄 PDF Report
      </a>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- TAB: DONORS -->
  <!-- ═══════════════════════════════════════════ -->
  <?php if ($active_tab === 'donors'): ?>
  <div class="card section-card p-4">
    <h5 class="fw-bold mb-3" style="color:#003087;">👥 Donor Management</h5>
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
      <input type="hidden" name="tab" value="donors">
      <input type="text" name="search" class="form-control" style="max-width:260px;" placeholder="Search name, email, DBU ID..."
             value="<?= htmlspecialchars($search) ?>">
      <select name="blood_type" class="form-select" style="max-width:180px;">
        <option value="">All Blood Types</option>
        <?php foreach ($blood_types as $bt): ?>
          <option value="<?= $bt ?>" <?= $blood_filter === $bt ? 'selected' : '' ?>><?= $bt ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-dbu-blue">Search</button>
      <a href="admin_panel.php?tab=donors" class="btn btn-secondary">Clear</a>
    </form>
    <div class="table-responsive">
      <table class="table table-hover align-middle" style="font-size:.875rem;">
        <thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>Blood</th><th>Phone</th><th>DBU ID</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if ($donors->num_rows === 0): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No donors found.</td></tr>
          <?php else: ?>
            <?php while ($d = $donors->fetch_assoc()): ?>
            <tr>
              <td><?= $d['id'] ?></td>
              <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
              <td><?= htmlspecialchars($d['email']) ?></td>
              <td><span class="badge badge-blood px-2"><?= $d['blood_type'] ?></span></td>
              <td><?= htmlspecialchars($d['phone']) ?></td>
              <td><?= htmlspecialchars($d['dbu_id']) ?></td>
              <td><span class="badge <?= $d['is_active'] ? 'bg-success' : 'bg-danger' ?>"><?= $d['is_active'] ? 'Active' : 'Inactive' ?></span></td>
              <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="toggle_active" value="<?= $d['id'] ?>">
                    <input type="hidden" name="current_active" value="<?= $d['is_active'] ?>">
                    <button type="submit" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size:.78rem;"><?= $d['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                  </form>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this donor permanently?');">
                    <input type="hidden" name="delete_donor" value="<?= $d['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size:.78rem;">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Task 3: Blood Type Update Panel for 'Unknown' Donors -->
  <?php
  $unknown_donors_res = $conn->query("SELECT id, name, email, blood_type FROM donors WHERE LOWER(blood_type) = 'unknown' AND is_active = 1 ORDER BY name");
  $unknown_donors = $unknown_donors_res ? $unknown_donors_res->fetch_all(MYSQLI_ASSOC) : [];
  if (!empty($unknown_donors)):
  ?>
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="border-left:5px solid #F59E0B!important;background:#fffbeb;">
    <h5 class="fw-bold mb-3" style="color:#92400e;">🩺 Task 3 — Update Unknown Blood Types</h5>
    <p class="text-muted small mb-3">The following donors registered with <strong>"Unknown"</strong> blood type. Update their blood type after laboratory verification to activate their donor status and send them an automated notification.</p>
    <div class="row g-3">
      <?php foreach ($unknown_donors as $ud): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 p-3" style="background:#fff;">
          <div class="fw-semibold mb-1"><?= htmlspecialchars($ud['name']) ?></div>
          <div class="text-muted small mb-2"><?= htmlspecialchars($ud['email']) ?></div>
          <form method="POST" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="update_blood_type" value="1">
            <input type="hidden" name="update_bt_donor_id" value="<?= $ud['id'] ?>">
            <select name="new_blood_type" class="form-select form-select-sm" required>
              <option value="">Blood Type...</option>
              <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt): ?>
              <option value="<?= $bt ?>"><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm fw-bold rounded-pill px-3"
                    style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;white-space:nowrap;">
              ✓ Verify
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════ -->
  <!-- TABLE A: Registered Non-Donors (Task 5) -->
  <!-- ═══════════════════════════════════════════════════ -->
  <div class="card section-card p-4 mb-4" style="border: 2px solid #CC0000; box-shadow: 0 10px 30px rgba(204,0,0,0.15);">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
      <div>
        <span class="badge bg-danger text-white px-3 py-2 fs-6 mb-2" style="letter-spacing: 1px;">⚠ ACTION REQUIRED: REGISTERED BUT NOT DONATED</span>
        <h4 class="fw-black mb-0" style="color:#CC0000;">Registered Non-Donors</h4>
        <p class="text-muted mb-0 mt-1" style="font-size: .95rem; font-weight: 500;">
          These users have created an account but <strong>HAVE NOT</strong> donated blood yet. Send them a reminder!
          <span class="badge bg-danger ms-1"><?= count($non_donors) ?> pending</span>
        </p>
      </div>
      <form method="POST" action="notifications_log.php"
            onsubmit="return confirm('Send reminder emails to all <?= count($non_donors) ?> non-donor(s)?');">
        <button type="submit" name="send_reminders" value="1"
                class="btn btn-lg fw-bold rounded-pill px-4"
                style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border:none;box-shadow: 0 4px 15px rgba(204,0,0,0.3);">
          📧 Send Reminders Now (<?= count($non_donors) ?>)
        </button>
      </form>
    </div>
    <?php if (empty($non_donors)): ?>
      <div class="alert alert-success rounded-3 border-0 mb-0">
        🎉 All registered donors have at least one test record. No reminders needed!
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle" style="font-size:.875rem;">
        <thead><tr>
          <th>#</th><th>Name</th><th>Email</th>
          <th>Blood Type</th><th>Phone</th><th>DBU ID</th><th>Registered</th>
        </tr></thead>
        <tbody>
          <?php foreach ($non_donors as $nd): ?>
          <tr>
            <td><?= $nd['id'] ?></td>
            <td><strong><?= htmlspecialchars($nd['name']) ?></strong></td>
            <td class="small"><?= htmlspecialchars($nd['email']) ?></td>
            <td><span class="badge badge-blood px-2"><?= htmlspecialchars($nd['blood_type'] ?: '—') ?></span></td>
            <td><?= htmlspecialchars($nd['phone'] ?? '—') ?></td>
            <td><?= htmlspecialchars($nd['dbu_id'] ?? '—') ?></td>
            <td class="small text-muted"><?= date('d M Y', strtotime($nd['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ═══════════════════════════════════════════════════ -->
  <!-- TABLE B: Verified Blood Donors Ledger (Task 4) -->
  <!-- ═══════════════════════════════════════════════════ -->
  <div class="card section-card p-4 mb-4" style="border: 2px solid #059669; box-shadow: 0 10px 30px rgba(5,150,105,0.15);">
    <div class="mb-3">
      <span class="badge bg-success text-white px-3 py-2 fs-6 mb-2" style="letter-spacing: 1px;">🎉 SUCCESS: COMPLETED DONATIONS</span>
      <h4 class="fw-black mb-0" style="color:#059669;">Verified Blood Donors Ledger</h4>
      <p class="text-muted mb-0 mt-1" style="font-size: .95rem; font-weight: 500;">
        These users have successfully <strong>DONATED</strong> and their blood has been tested in the lab.
        <span class="badge bg-success ms-1"><?= count($verified_donors) ?> verified</span>
      </p>
    </div>
    <?php if (empty($verified_donors)): ?>
      <div class="alert alert-info rounded-3 border-0 mb-0">
        No verified donors yet. Once a donor's lab results are saved in Test Results, they appear here automatically.
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle" style="font-size:.875rem;">
        <thead><tr>
          <th>#</th><th>Name</th><th>Email</th>
          <th>Verified Blood Type</th><th>Outcome</th>
          <th>HIV</th><th>Hepatitis</th><th>Syphilis</th>
          <th>Screened At</th>
        </tr></thead>
        <tbody>
          <?php foreach ($verified_donors as $vd): ?>
          <?php $safe = ($vd['overall_outcome'] ?? '') === 'safe'; ?>
          <tr style="<?= !$safe ? 'background:#fff5f5;' : '' ?>">
            <td><?= $vd['id'] ?></td>
            <td><strong><?= htmlspecialchars($vd['name']) ?></strong></td>
            <td class="small"><?= htmlspecialchars($vd['email']) ?></td>
            <td><span class="badge badge-blood px-2"><?= htmlspecialchars($vd['blood_type'] ?: '—') ?></span></td>
            <td>
              <span class="badge <?= $safe ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                <?= $safe ? '✓ SAFE' : '⚠ UNSAFE' ?>
              </span>
            </td>
            <?php foreach (['hiv_status','hepatitis_status','syphilis_status'] as $marker): ?>
            <td>
              <span class="badge <?= ($vd[$marker] ?? '') === 'negative'
                ? 'bg-success-subtle text-success border border-success'
                : 'bg-danger' ?>" style="font-size:.7rem;">
                <?= ucfirst($vd[$marker] ?? '—') ?>
              </span>
            </td>
            <?php endforeach; ?>
            <td class="small text-muted"><?= date('d M Y H:i', strtotime($vd['screened_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- TAB: REQUESTS -->
  <!-- ═══════════════════════════════════════════ -->
  <?php elseif ($active_tab === 'requests'): ?>
  <div class="card section-card p-4">
    <h5 class="fw-bold mb-3" style="color:#003087;">🩸 Blood Requests</h5>
    <div class="table-responsive">
      <table class="table table-hover align-middle" style="font-size:.875rem;">
        <thead class="table-light"><tr><th>#</th><th>Patient</th><th>Blood</th><th>Hospital</th><th>Requester</th><th>Phone</th><th>Urgency</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
          <?php if ($requests->num_rows === 0): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">No blood requests found.</td></tr>
          <?php else: ?>
            <?php while ($r = $requests->fetch_assoc()): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
              <td><span class="badge badge-blood"><?= $r['blood_type'] ?></span></td>
              <td><?= htmlspecialchars($r['hospital']) ?></td>
              <td><?= htmlspecialchars($r['requester_name']) ?></td>
              <td><?= htmlspecialchars($r['requester_phone']) ?></td>
              <td>
                <?php $urg_cls = ['urgent'=>'danger','medium'=>'warning','normal'=>'info']; ?>
                <span class="badge bg-<?= $urg_cls[$r['urgency']] ?? 'secondary' ?>"><?= ucfirst($r['urgency']) ?></span>
              </td>
              <td><span class="badge <?= $r['status']==='approved'?'bg-success':'bg-secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
              <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              <td>
                <div class="d-flex gap-1">
                  <?php if ($r['status'] === 'pending'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="approve_request" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-outline-success py-1 px-2" style="font-size:.78rem;">Approve</button>
                  </form>
                  <?php endif; ?>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this request?');">
                    <input type="hidden" name="delete_request" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size:.78rem;">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- TAB: BLOOD STOCK -->
  <!-- ═══════════════════════════════════════════ -->
  <?php elseif ($active_tab === 'stock'): ?>
  <div class="row g-4">
    <div class="col-md-5">
      <div class="card section-card p-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">🏥 Update Blood Stock</h5>
        <form method="POST">
          <input type="hidden" name="update_stock" value="1">
          <div class="mb-3">
            <label class="form-label fw-semibold">Blood Type</label>
            <select name="blood_type_stock" class="form-select" required>
              <option value="">Select Blood Type</option>
              <?php foreach ($blood_types as $bt): ?>
                <option value="<?= $bt ?>"><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Units Available</label>
            <input type="number" name="units" class="form-control" min="0" max="9999" placeholder="Number of units" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select name="stock_status" class="form-select" required>
              <option value="available">✅ Available</option>
              <option value="low">⚠ Low</option>
              <option value="critical">🚨 Critical</option>
              <option value="unavailable">❌ Unavailable</option>
            </select>
          </div>
          <button type="submit" class="btn btn-dbu-blue w-100 rounded-pill">Update Stock</button>
        </form>
      </div>
    </div>
    <div class="col-md-7">
      <div class="card section-card p-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">Current Stock Levels</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Blood Type</th><th>Units</th><th>Status</th><th>Last Updated</th></tr></thead>
            <tbody>
              <?php
              $stock_res->data_seek(0);
              if ($stock_res->num_rows === 0):
              ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No stock records yet.</td></tr>
              <?php else: ?>
                <?php while ($s = $stock_res->fetch_assoc()):
                  $sc = ['available'=>'success','low'=>'warning','critical'=>'danger','unavailable'=>'secondary'];
                ?>
                <tr>
                  <td><span class="badge fs-6 badge-blood px-3"><?= $s['blood_type'] ?></span></td>
                  <td><strong><?= $s['units'] ?></strong> units</td>
                  <td><span class="badge bg-<?= $sc[$s['status']] ?? 'secondary' ?>"><?= ucfirst($s['status']) ?></span></td>
                  <td><?= date('d M Y H:i', strtotime($s['updated_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- TAB: GALLERY -->
  <!-- ═══════════════════════════════════════════ -->
  <?php elseif ($active_tab === 'gallery'): ?>

  <!-- Admin Gallery Upload Form -->
  <div class="card section-card p-4 mb-4">
    <h5 class="fw-bold mb-3" style="color:#003087;">📤 Upload Gallery Photo</h5>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="admin_upload_photo" value="1">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Photo File <span class="text-danger">*</span></label>
          <input type="file" name="gallery_photo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
          <div class="form-text">JPG, PNG, GIF or WEBP — max 5 MB</div>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Donor / Label Name</label>
          <input type="text" name="gallery_donor_name" class="form-control" placeholder="e.g. Admin, Event Name">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Caption (Optional)</label>
          <input type="text" name="gallery_caption" class="form-control" placeholder="Brief description...">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-dbu-blue w-100 rounded-pill">📤 Upload</button>
        </div>
      </div>
    </form>
  </div>

  <!-- Gallery Photo Grid -->
  <div class="card section-card p-4">
    <h5 class="fw-bold mb-3" style="color:#003087;">🖼 Donation Gallery Management</h5>
    <?php if ($photos->num_rows === 0): ?>
      <div class="text-center text-muted py-5">
        <div style="font-size:52px;">📷</div>
        <p class="mt-3">No gallery photos yet. Upload one above!</p>
      </div>
    <?php else: ?>
    <div class="row g-3">
      <?php while ($photo = $photos->fetch_assoc()): ?>
      <div class="col-md-3 col-sm-4 col-6">
        <div class="card h-100" style="border-radius:12px;overflow:hidden;">
          <img src="<?= htmlspecialchars($photo['photo_path']) ?>" alt="Gallery"
               style="height:140px;object-fit:cover;width:100%;display:block;"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="bg-light align-items-center justify-content-center" style="height:140px;color:#aaa;display:none;">
            📷 Image not found
          </div>
          <div class="card-body p-2">
            <div class="fw-semibold small"><?= htmlspecialchars($photo['donor_name']) ?></div>
            <?php if ($photo['caption']): ?>
              <div class="text-muted" style="font-size:.78rem;">"<?= htmlspecialchars($photo['caption']) ?>"</div>
            <?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.72rem;"><?= date('d M Y', strtotime($photo['created_at'])) ?></div>
            <form method="POST" class="mt-2" onsubmit="return confirm('Delete this photo permanently?');">
              <input type="hidden" name="delete_photo" value="<?= $photo['id'] ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm py-0 w-100" style="font-size:.75rem;">🗑 Delete</button>
            </form>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- TAB: MESSAGES -->
  <!-- ═══════════════════════════════════════════ -->
  <?php elseif ($active_tab === 'messages'): ?>
  <div class="card section-card p-4">
    <h5 class="fw-bold mb-3" style="color:#003087;">📞 Contact Messages</h5>
    <?php if ($messages->num_rows === 0): ?>
      <div class="text-center text-muted py-5">No contact messages yet.</div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle" style="font-size:.875rem;">
        <thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
          <?php while ($m = $messages->fetch_assoc()): ?>
          <tr class="<?= $m['status']==='new' ? 'table-warning' : '' ?>">
            <td><?= $m['id'] ?></td>
            <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td><?= htmlspecialchars($m['subject']) ?></td>
            <td style="max-width:200px;" class="text-truncate"><?= htmlspecialchars($m['message']) ?></td>
            <td><span class="badge <?= $m['status']==='new'?'bg-warning text-dark':'bg-secondary' ?>"><?= ucfirst($m['status']) ?></span></td>
            <td><?= date('d M Y', strtotime($m['created_at'])) ?></td>
            <td>
              <?php if ($m['status']==='new'): ?>
              <button class="btn btn-sm btn-outline-primary py-0" style="font-size:.78rem;"
                      data-bs-toggle="collapse" data-bs-target="#reply-<?= $m['id'] ?>">Reply</button>
              <div class="collapse mt-2" id="reply-<?= $m['id'] ?>">
                <form method="POST">
                  <input type="hidden" name="send_reply" value="<?= $m['id'] ?>">
                  <textarea name="reply_text" class="form-control mb-1" rows="3" placeholder="Type your reply…" style="font-size:.8rem;" required></textarea>
                  <button type="submit" class="btn btn-sm btn-primary py-0 w-100" style="font-size:.78rem;">Send Reply</button>
                </form>
              </div>
              <?php else: ?>
                <span class="text-success small">✓ Replied</span>
                <?php if (!empty($m['admin_reply'])): ?>
                <div class="text-muted mt-1" style="font-size:.75rem;max-width:180px;"><?= htmlspecialchars($m['admin_reply']) ?></div>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- TAB: SYSTEM SETTINGS (Dynamic Logo Upload)  -->
  <!-- ═══════════════════════════════════════════ -->
  <?php elseif ($active_tab === 'settings'): ?>
  <div class="row g-4">

    <!-- Logo Upload Card -->
    <div class="col-md-6">
      <div class="card section-card p-4">
        <h5 class="fw-bold mb-1" style="color:#003087;">⚙️ University Logo</h5>
        <p class="text-muted small mb-4">Upload the DBU university logo. It will replace the "DBU" text in the navigation bar and footer across the entire site. Supported formats: JPG, PNG, GIF, WEBP (max 2 MB).</p>

        <!-- Current logo preview -->
        <div class="text-center mb-4">
          <div class="mb-2">
            <?php if ($current_logo && file_exists(__DIR__ . '/' . $current_logo)): ?>
              <img src="<?= htmlspecialchars($current_logo) ?>" alt="Current Logo" class="logo-preview">
              <div class="text-success small mt-2 fw-semibold">✓ Logo is active</div>
            <?php else: ?>
              <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-black text-white border border-warning"
                   style="width:100px;height:100px;background:#CC0000;font-size:20px;font-weight:900;">DBU</div>
              <div class="text-muted small mt-2">No logo uploaded — showing fallback "DBU" text</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Upload form -->
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="upload_logo" value="1">
          <div class="mb-3">
            <label class="form-label fw-semibold">Choose Logo Image</label>
            <input type="file" name="logo_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
            <div class="form-text">Recommended: square image, at least 100×100 px. The image will be displayed in a circle.</div>
          </div>
          <button type="submit" class="btn btn-dbu-blue w-100 rounded-pill mb-2">Upload Logo</button>
        </form>

        <!-- Remove logo form -->
        <?php if ($current_logo): ?>
        <form method="POST" onsubmit="return confirm('Remove the uploaded logo? The site will show the DBU text fallback.');">
          <input type="hidden" name="upload_logo" value="1">
          <input type="hidden" name="remove_logo" value="1">
          <button type="submit" class="btn btn-outline-danger w-100 rounded-pill btn-sm">Remove Logo (Restore DBU Text)</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Instructions Card -->
    <div class="col-md-6">
      <div class="card section-card p-4 h-100" style="background:linear-gradient(135deg,rgba(0,48,135,.03),rgba(204,0,0,.03));">
        <h5 class="fw-bold mb-3" style="color:#003087;">How It Works</h5>
        <ul class="list-unstyled" style="font-size:.9rem;">
          <li class="mb-3 d-flex gap-2">
            <span style="color:#CC0000;font-weight:700;">①</span>
            <span>Choose an image file from your computer (JPG or PNG recommended).</span>
          </li>
          <li class="mb-3 d-flex gap-2">
            <span style="color:#CC0000;font-weight:700;">②</span>
            <span>Click <strong>Upload Logo</strong>. The file is saved to <code>assets/images/logo/</code> and its path is recorded in the database.</span>
          </li>
          <li class="mb-3 d-flex gap-2">
            <span style="color:#CC0000;font-weight:700;">③</span>
            <span>The logo appears immediately in the navbar and footer on all pages — replacing the red "DBU" circle text.</span>
          </li>
          <li class="mb-3 d-flex gap-2">
            <span style="color:#CC0000;font-weight:700;">④</span>
            <span>If no logo is uploaded, the site automatically falls back to the original red "DBU" text circle.</span>
          </li>
        </ul>
        <div class="mt-auto pt-3" style="border-top:1px solid rgba(0,0,0,.07);font-size:.8rem;color:#6b7280;">
          Logo stored in: <code>assets/images/logo/</code><br>
          Path tracked in: <code>system_settings</code> table
        </div>
      </div>
    </div>

  </div>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════ -->
  <!-- TAB: HOSPITAL REQUESTS -->
  <!-- ═══════════════════════════════════════════ -->
  <?php if ($active_tab === 'hospitals'): ?>
  <?php
  $hosp_reqs = $conn->query("SELECT br.*, h.hospital_name, h.primary_contact AS hosp_phone
                              FROM blood_requests br
                              LEFT JOIN hospitals h ON h.hospital_name = br.hospital
                              ORDER BY br.created_at DESC");
  $hospitals_list = $conn->query("SELECT * FROM hospitals ORDER BY hospital_name");
  ?>
  <div class="card section-card p-4">
    <h5 class="fw-bold mb-3" style="color:#003087;">🏨 Hospital Portal — Blood Requests &amp; Registered Hospitals</h5>

    <div class="row g-4">
      <div class="col-lg-8">
        <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.78rem;letter-spacing:1px;">All Blood Requests From Hospitals</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Patient</th><th>Blood</th><th>Hospital</th>
                <th>Contact</th><th>Urgency</th><th>Status</th><th>Date</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $urg_map = ['urgent'=>'danger','medium'=>'warning','normal'=>'success'];
              while ($r = $hosp_reqs->fetch_assoc()):
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
                <td><span class="badge" style="background:rgba(204,0,0,.12);color:#CC0000;"><?= $r['blood_type'] ?></span></td>
                <td style="font-size:.85rem;"><?= htmlspecialchars($r['hospital']) ?></td>
                <td style="font-size:.8rem;"><?= htmlspecialchars($r['requester_name']) ?><br>
                  <span class="text-muted"><?= htmlspecialchars($r['requester_phone']) ?></span></td>
                <td><span class="badge bg-<?= $urg_map[$r['urgency']] ?? 'secondary' ?>"><?= ucfirst($r['urgency']) ?></span></td>
                <td>
                  <?php if ($r['status'] === 'approved'): ?>
                    <span class="badge bg-success">Approved</span>
                  <?php else: ?>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="approve_request" value="<?= $r['id'] ?>">
                      <button class="btn btn-xs btn-outline-success rounded-pill px-2 py-0" style="font-size:.75rem;">✓ Approve</button>
                    </form>
                  <?php endif; ?>
                </td>
                <td style="font-size:.8rem;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td>
                  <form method="POST" class="d-inline"
                        onsubmit="return confirm('Delete this request?')">
                    <input type="hidden" name="delete_request" value="<?= $r['id'] ?>">
                    <button class="btn btn-xs btn-outline-danger rounded-pill px-2 py-0" style="font-size:.75rem;">✕</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="col-lg-4">
        <h6 class="fw-semibold mb-3 text-muted text-uppercase" style="font-size:.78rem;letter-spacing:1px;">Registered Hospitals</h6>
        <?php if ($hospitals_list->num_rows === 0): ?>
          <div class="text-center text-muted py-3">No hospitals registered yet.</div>
        <?php else: ?>
        <?php while ($h = $hospitals_list->fetch_assoc()): ?>
        <div class="p-3 rounded-3 mb-3"
             style="background:<?= $h['is_active'] ? '#f0fdf4' : '#fff7ed' ?>;
                    border:1px solid <?= $h['is_active'] ? '#bbf7d0' : '#fed7aa' ?>;
                    font-size:.88rem;">
          <div class="fw-bold" style="color:#003087;"><?= htmlspecialchars($h['hospital_name']) ?></div>
          <div class="text-muted small"><?= htmlspecialchars($h['email']) ?></div>
          <div class="mt-1">📞 <?= htmlspecialchars($h['primary_contact']) ?></div>
          <?php if (!empty($h['address'])): ?>
          <div class="mt-1 text-muted small">📍 <?= htmlspecialchars($h['address']) ?></div>
          <?php endif; ?>
          <div class="mt-1 mb-2">
            <span class="badge <?= $h['is_active'] ? 'bg-success' : 'bg-warning text-dark' ?>">
              <?= $h['is_active'] ? '✓ Active' : '⏳ Pending Approval' ?>
            </span>
          </div>
          <div class="d-flex gap-1 flex-wrap">
            <?php if (!$h['is_active']): ?>
            <form method="POST" class="d-inline">
              <input type="hidden" name="activate_hospital" value="<?= $h['id'] ?>">
              <button class="btn btn-xs btn-success py-0 px-2" style="font-size:.75rem;">✓ Activate</button>
            </form>
            <?php else: ?>
            <form method="POST" class="d-inline">
              <input type="hidden" name="deactivate_hospital" value="<?= $h['id'] ?>">
              <button class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:.75rem;">Deactivate</button>
            </form>
            <?php endif; ?>
            <form method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this hospital account permanently?');">
              <input type="hidden" name="delete_hospital" value="<?= $h['id'] ?>">
              <button class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:.75rem;">🗑 Delete</button>
            </form>
          </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<div class="text-center py-4 mt-3" style="color:#6b7280;font-size:.82rem;
     background:linear-gradient(160deg,#f0f4ff,#fdf4ff);
     border-top:2px solid;
     border-image:linear-gradient(90deg,#003087,#7c3aed,#059669,#d97706,#CC0000) 1;">
  &copy; <?= date('Y') ?> DBU Blood Bank &mdash; Admin Panel
  
  &nbsp;|&nbsp; Instructor: <strong>Mr.Getachew</strong>
  &nbsp;|&nbsp; Powered by the
  <strong style="background:linear-gradient(90deg,#e11d48,#7c3aed);-webkit-background-clip:text;
                 -webkit-text-fill-color:transparent;background-clip:text;">
    Feminist Group — DBU
  </strong>
</div>

<!-- Bootstrap 5 JS — LOCAL copy (offline-ready) -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
