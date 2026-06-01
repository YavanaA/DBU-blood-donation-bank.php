<?php
$page_title = 'Hospital Dashboard';
require_once 'config.php';

if (!isset($_SESSION['hospital_id'])) redirect('hospital_login.php');

$hospital_id   = (int)$_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];

$success = '';
$error   = '';

// Fetch hospital info
$stmt_h = $conn->prepare("SELECT * FROM hospitals WHERE id = ? LIMIT 1");
$stmt_h->bind_param('i', $hospital_id);
$stmt_h->execute();
$hosp_data = $stmt_h->get_result()->fetch_assoc();
$stmt_h->close();

// Submit blood request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $patient_name    = trim($_POST['patient_name']    ?? '');
    $blood_type      = trim($_POST['blood_type']      ?? '');
    $urgency         = trim($_POST['urgency']         ?? 'normal');
    $requester_name  = trim($_POST['requester_name']  ?? '');
    $requester_phone = trim($_POST['requester_phone'] ?? '');
    $urgency         = in_array($urgency, ['urgent','medium','normal']) ? $urgency : 'normal';
    $units           = max(1, (int)($_POST['units_requested'] ?? 1));

    if (!$patient_name || !$blood_type || !$requester_name || !$requester_phone || !$units) {
        $error = 'All fields are required.';
    } else {
        // Check available stock first
        $stmt_stock = $conn->prepare("SELECT units FROM blood_stock WHERE blood_type = ? LIMIT 1");
        $stmt_stock->bind_param('s', $blood_type);
        $stmt_stock->execute();
        $stock_data = $stmt_stock->get_result()->fetch_assoc();
        $stmt_stock->close();

        $stock_available = (int)($stock_data['units'] ?? 0);

        if ($stock_available < $units) {
            $error = "Action Denied: Insufficient Blood Stock. You requested {$units} unit(s) of {$blood_type}, but only {$stock_available} available in inventory.";
        } else {
            $stmt_ins = $conn->prepare("INSERT INTO blood_requests (patient_name, blood_type, hospital, requester_name, requester_phone, urgency, units_requested, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt_ins->bind_param('ssssssi', $patient_name, $blood_type, $hospital_name, $requester_name, $requester_phone, $urgency, $units);
            $stmt_ins->execute();
            $stmt_ins->close();
            $success = "Blood request submitted successfully. Admin will review and approve the request shortly.";
        }
    }
}

// My requests
$stmt_mr = $conn->prepare("SELECT * FROM blood_requests WHERE hospital = ? ORDER BY created_at DESC LIMIT 20");
$stmt_mr->bind_param('s', $hospital_name);
$stmt_mr->execute();
$my_reqs = $stmt_mr->get_result();
$stmt_mr->close();

// Blood stock
$stock = $conn->query("SELECT * FROM blood_stock ORDER BY blood_type");
$blood_types = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];

require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<!-- Header -->
<div class="text-white py-4"
     style="background:linear-gradient(135deg,#0891b2 0%,#003087 50%,#4c1d95 100%);">
  <div class="container">
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div style="width:60px;height:60px;border-radius:50%;
                  background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.4);
                  display:flex;align-items:center;justify-content:center;font-size:26px;">🏥</div>
      <div>
        <h2 class="fw-black mb-1"><?= htmlspecialchars($hospital_name) ?></h2>
        <div class="d-flex gap-2 flex-wrap">
          <span class="badge px-3 py-2" style="background:rgba(255,255,255,.15);color:#fff;">
            📞 <?= htmlspecialchars($hosp_data['primary_contact'] ?? 'N/A') ?>
          </span>
          <span class="badge px-3 py-2" style="background:rgba(5,150,105,.3);color:#6ee7b7;">
            ✅ Verified Hospital
          </span>
        </div>
      </div>
      <div class="ms-auto">
        <a href="hospital_login.php?logout=1" class="btn btn-sm rounded-pill fw-semibold"
           style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
          Logout
        </a>
      </div>
    </div>
  </div>
</div>

<?php
// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['hospital_id'], $_SESSION['hospital_name']);
    redirect('hospital_login.php');
}
?>

<div class="container py-4">

  <?php if ($success): ?>
    <div class="alert alert-success rounded-3">✓ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger rounded-3">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Request Blood Form -->
    <div class="col-lg-5">
      <div class="card card-dbu p-4">
        <h5 class="fw-bold mb-4" style="color:#0891b2;">📋 Submit Blood Request</h5>
        <form method="POST">
          <input type="hidden" name="submit_request" value="1">
          <div class="mb-3">
            <label class="form-label fw-semibold small">Patient Name</label>
            <input type="text" name="patient_name" class="form-control"
                   style="border-radius:10px;" placeholder="Full patient name" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Blood Type Required</label>
            <select name="blood_type" class="form-select" style="border-radius:10px;" required>
              <option value="">Select Blood Type</option>
              <?php foreach ($blood_types as $bt): ?>
                <option value="<?= $bt ?>"><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Units Requested</label>
            <input type="number" name="units_requested" class="form-control"
                   style="border-radius:10px;" min="1" max="10" placeholder="Number of units (1-10)" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Urgency Level</label>
            <select name="urgency" class="form-select" style="border-radius:10px;" required>
              <option value="normal">🟢 Normal</option>
              <option value="medium">🟡 Medium</option>
              <option value="urgent">🔴 Urgent / Emergency</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Contact Person Name</label>
            <input type="text" name="requester_name" class="form-control"
                   style="border-radius:10px;" placeholder="Doctor / nurse name" required>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold small">Contact Phone</label>
            <input type="tel" name="requester_phone" class="form-control"
                   style="border-radius:10px;" placeholder="+251 9xx xxx xxxx" required>
          </div>
          <button type="submit" class="btn w-100 py-3 fw-bold rounded-pill"
                  style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border:none;">
            🩸 Submit Blood Request
          </button>
        </form>
      </div>
    </div>

    <div class="col-lg-7">

      <!-- Blood Stock Overview -->
      <div class="card card-dbu p-4 mb-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">🏥 Current Blood Stock at DBU</h5>
        <div class="row g-2">
          <?php
          $sc_colors = ['available'=>'#059669','low'=>'#d97706','critical'=>'#CC0000','unavailable'=>'#6b7280'];
          $stock->data_seek(0);
          while ($s = $stock->fetch_assoc()):
            $col = $sc_colors[$s['status']] ?? '#6b7280';
          ?>
          <div class="col-6 col-sm-3">
            <div class="text-center p-3 rounded-3" style="background:<?= $col ?>18;border:2px solid <?= $col ?>44;">
              <div class="fw-black" style="color:<?= $col ?>;font-size:1.4rem;"><?= $s['blood_type'] ?></div>
              <div class="fw-bold" style="font-size:.95rem;"><?= $s['units'] ?> units</div>
              <div style="font-size:.72rem;color:<?= $col ?>;"><?= ucfirst($s['status']) ?></div>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- My Requests -->
      <div class="card card-dbu p-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">📝 Our Blood Requests</h5>
        <?php if ($my_reqs->num_rows === 0): ?>
          <div class="text-center text-muted py-4">No requests submitted yet.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle" style="font-size:.875rem;">
            <thead style="background:linear-gradient(135deg,#0891b2,#003087);">
              <tr>
                <th class="text-white">Patient</th>
                <th class="text-white">Blood</th>
                <th class="text-white">Units</th>
                <th class="text-white">Urgency</th>
                <th class="text-white">Status</th>
                <th class="text-white">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $my_reqs->fetch_assoc()):
                $urg_col = ['urgent'=>'danger','medium'=>'warning','normal'=>'success'];
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
                <td><span class="badge" style="background:rgba(204,0,0,.12);color:#CC0000;"><?= $r['blood_type'] ?></span></td>
                <td><strong><?= htmlspecialchars($r['units_requested']) ?></strong> unit<?= $r['units_requested']!=1?'s':'' ?></td>
                <td><span class="badge bg-<?= $urg_col[$r['urgency']] ?? 'secondary' ?>"><?= ucfirst($r['urgency']) ?></span></td>
                <td><span class="badge <?= $r['status']==='approved'?'bg-success':'bg-secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
                <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
