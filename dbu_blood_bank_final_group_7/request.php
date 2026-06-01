<?php
$page_title = 'Request Blood';
require_once 'config.php';

$success = '';
$error   = '';
$blood_types = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize input data
    $patient  = sanitize($conn, $_POST['patient_name']    ?? '');
    $blood    = sanitize($conn, $_POST['blood_type']      ?? '');
    $hospital = sanitize($conn, $_POST['hospital']        ?? '');
    $req_name = sanitize($conn, $_POST['requester_name']  ?? '');
    $req_ph   = sanitize($conn, $_POST['requester_phone'] ?? '');
    $urgency  = sanitize($conn, $_POST['urgency']         ?? 'normal');
    $urgency  = in_array($urgency, ['urgent','medium','normal']) ? $urgency : 'normal';
    $units    = max(1, (int)($_POST['units_requested']   ?? 1));

    // 2. Comprehensive Validation
    if (!$patient || !$blood || !$hospital || !$req_name || !$req_ph) {
        $error = 'All fields are required. Please fill in all information.';
    } 
    // Patient Name Validation (Only letters and spaces)
    elseif (!preg_match("/^[a-zA-Z\s]+$/", $patient)) {
        $error = 'Patient name should only contain letters and spaces.';
    }
    elseif (strlen($patient) < 3) {
        $error = 'Patient name must be at least 3 characters long.';
    }
    // Requester Name Validation (Only letters and spaces)
    elseif (!preg_match("/^[a-zA-Z\s]+$/", $req_name)) {
        $error = 'Requester name should only contain letters and spaces.';
    }
    elseif (strlen($req_name) < 3) {
        $error = 'Requester name must be at least 3 characters long.';
    } 
    // Phone Number Validation (Ethiopian format)
    elseif (!preg_match("/^(\+251|0)9[0-9]{8}$/", str_replace(' ', '', $req_ph))) {
        $error = 'Please enter a valid Ethiopian phone number (e.g., 0911223344).';
    } else {
        // Check available stock first
        $stmt_stock = $conn->prepare("SELECT units FROM blood_stock WHERE blood_type = ? LIMIT 1");
        $stmt_stock->bind_param('s', $blood);
        $stmt_stock->execute();
        $stock_data = $stmt_stock->get_result()->fetch_assoc();
        $stmt_stock->close();

        $stock_available = (int)($stock_data['units'] ?? 0);

        if ($stock_available < $units) {
            $error = "Action Denied: Insufficient Blood Stock. You requested {$units} unit(s) of {$blood}, but only {$stock_available} available in inventory.";
        } else {
            // 3. Database Operation
            $stmt = $conn->prepare("INSERT INTO blood_requests (patient_name, blood_type, hospital, requester_name, requester_phone, urgency, units_requested, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('ssssssi', $patient, $blood, $hospital, $req_name, $req_ph, $urgency, $units);
            
            if ($stmt->execute()) {
                $success = "Your blood request for {$units} unit(s) of {$blood} has been submitted successfully! Admin will review and approve the request shortly.";
                $_POST = array(); // Clear post data after success
            } else {
                $error = 'System error: Could not submit request. Please try again.';
            }
            $stmt->close();
        }
    }
}

// Fetch only approved requests for the display table
$approved = $conn->query("SELECT * FROM blood_requests WHERE status='approved' ORDER BY created_at DESC");
require 'includes/head.php';
?>
<style>
  .urgency-card {
    border-radius: 14px; padding: 16px; cursor: pointer;
    border: 2px solid #e5e7eb; transition: .2s; text-align: center;
    background: #fff;
  }
  .urgency-card.selected, .urgency-card:hover { border-color: #7c3aed; background: #f5f3ff; }
  .form-control:focus, .form-select:focus { border-color: #7c3aed; box-shadow: 0 0 0 0.25rem rgba(124, 58, 237, 0.1); }
</style>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="text-white py-5 text-center"
     style="background:linear-gradient(135deg,#CC0000 0%,#7c3aed 40%,#003087 75%,#059669 100%);
            position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 30% 60%,rgba(245,158,11,.15),transparent 60%),
                  radial-gradient(ellipse at 80% 30%,rgba(5,150,105,.1),transparent 50%);
       pointer-events:none;"></div>
  <div style="position:relative;">
    <div style="font-size:56px;margin-bottom:12px;">🩸</div>
    <h1 class="fw-black display-4">Request Blood</h1>
    <p style="color:rgba(255,255,255,.85);font-size:1.05rem;max-width:560px;margin:0 auto;">
      Submit an emergency blood request. Our admin team will review and match you with available donors.
    </p>
  </div>
</div>

<div class="py-2 text-center text-white fw-bold"
     style="background:linear-gradient(90deg,#CC0000,#7c3aed,#CC0000);font-size:.88rem;">
  🆘 Life-threatening emergency? Call immediately: <strong>+251 11 681 2345</strong>
</div>

<div class="container py-5">
  <div class="row g-4 align-items-start">

    <div class="col-lg-6">
      <div class="card card-dbu p-4 p-md-5 shadow-sm border-0">
        <div class="d-flex align-items-center gap-3 mb-4">
          <div style="width:48px;height:48px;border-radius:14px;flex-shrink:0;
               background:linear-gradient(135deg,#CC0000,#7c3aed);
               display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;">🩸</div>
          <div>
            <h5 class="fw-bold mb-0" style="color:#003087;">Submit a Blood Request</h5>
            <div class="text-muted small">Fill all fields carefully — accuracy saves lives</div>
          </div>
        </div>

        <?php if ($success): ?>
          <div class="alert border-0 rounded-3 mb-4 p-4"
               style="background:linear-gradient(135deg,rgba(5,150,105,.08),rgba(8,145,178,.06));
                      border-left:4px solid #059669 !important;color:#065f46;">
            <div class="fw-bold mb-1">✅ Request Submitted!</div>
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert border-0 rounded-3 mb-4"
               style="background:rgba(204,0,0,.07);color:#CC0000;border-left:4px solid #CC0000;">
            <div class="fw-bold mb-1">⚠ Error</div>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="color:#374151;">👤 Patient Full Name</label>
            <input type="text" name="patient_name" class="form-control form-control-lg"
                   style="border-radius:12px;border:2px solid #e5e7eb;"
                   placeholder="Full name of the patient"
                   pattern="[a-zA-Z\s]+" title="Please enter only letters and spaces"
                   value="<?= htmlspecialchars($_POST['patient_name'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="color:#374151;">🩸 Required Blood Type</label>
            <select name="blood_type" class="form-select form-select-lg"
                    style="border-radius:12px;border:2px solid #e5e7eb;" required>
              <option value="">Select Blood Type...</option>
              <?php foreach ($blood_types as $bt): ?>
                <option value="<?= $bt ?>" <?= (($_POST['blood_type'] ?? '') === $bt) ? 'selected' : '' ?>><?= $bt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="color:#374151;">📦 Units Requested</label>
            <input type="number" name="units_requested" class="form-control form-control-lg"
                   style="border-radius:12px;border:2px solid #e5e7eb;"
                   min="1" max="10" placeholder="Number of units (1-10)"
                   value="<?= htmlspecialchars($_POST['units_requested'] ?? '1') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="color:#374151;">🏥 Hospital / Clinic Name</label>
            <input type="text" name="hospital" class="form-control form-control-lg"
                   style="border-radius:12px;border:2px solid #e5e7eb;"
                   placeholder="Name of hospital or clinic"
                   value="<?= htmlspecialchars($_POST['hospital'] ?? '') ?>" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="color:#374151;">🙋 Requester Name</label>
              <input type="text" name="requester_name" class="form-control"
                     style="border-radius:12px;border:2px solid #e5e7eb;"
                     placeholder="Your full name"
                     pattern="[a-zA-Z\s]+" title="Please enter only letters and spaces"
                     value="<?= htmlspecialchars($_POST['requester_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" style="color:#374151;">📞 Phone Number</label>
              <input type="tel" name="requester_phone" class="form-control"
                     style="border-radius:12px;border:2px solid #e5e7eb;"
                     placeholder="0911..."
                     value="<?= htmlspecialchars($_POST['requester_phone'] ?? '') ?>" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold" style="color:#374151;">⚡ Urgency Level</label>
            <div class="row g-2">
              <div class="col-4">
                <label style="display:block;">
                  <input type="radio" name="urgency" value="urgent" class="d-none"
                         <?= (($_POST['urgency'] ?? '') === 'urgent') ? 'checked' : '' ?>>
                  <div class="urgency-card" onclick="this.parentElement.querySelector('input').click();selectUrgency(this,'#CC0000')">
                    <div style="font-size:24px;">🔴</div>
                    <div class="fw-bold small" style="color:#CC0000;">Urgent</div>
                  </div>
                </label>
              </div>
              <div class="col-4">
                <label style="display:block;">
                  <input type="radio" name="urgency" value="medium" class="d-none"
                         <?= (($_POST['urgency'] ?? '') === 'medium') ? 'checked' : '' ?>>
                  <div class="urgency-card" onclick="this.parentElement.querySelector('input').click();selectUrgency(this,'#d97706')">
                    <div style="font-size:24px;">🟡</div>
                    <div class="fw-bold small" style="color:#d97706;">Medium</div>
                  </div>
                </label>
              </div>
              <div class="col-4">
                <label style="display:block;">
                  <input type="radio" name="urgency" value="normal" class="d-none"
                         <?= (($_POST['urgency'] ?? 'normal') === 'normal') ? 'checked' : '' ?>>
                  <div class="urgency-card" onclick="this.parentElement.querySelector('input').click();selectUrgency(this,'#059669')">
                    <div style="font-size:24px;">🟢</div>
                    <div class="fw-bold small" style="color:#059669;">Normal</div>
                  </div>
                </label>
              </div>
            </div>
          </div>
          <button type="submit" class="btn w-100 py-3 fw-bold fs-5 rounded-pill border-0"
                  style="background:linear-gradient(135deg,#CC0000,#7c3aed,#003087);color:#fff;
                         box-shadow:0 4px 20px rgba(124,58,237,.3);">
            🩸 Submit Blood Request
          </button>
        </form>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card card-dbu p-4 border-0 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div style="width:8px;height:30px;border-radius:4px;background:linear-gradient(180deg,#059669,#0891b2);flex-shrink:0;"></div>
          <h5 class="fw-bold mb-0" style="color:#003087;">✅ Recently Approved Requests</h5>
        </div>

        <?php if ($approved->num_rows === 0): ?>
          <div class="text-center text-muted py-4">
            <p class="mt-2 small">No approved blood requests yet.</p>
          </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle" style="font-size:.82rem;">
            <thead class="table-light">
              <tr>
                <th>Patient</th>
                <th>Blood</th>
                <th>Units</th>
                <th>Hospital</th>
                <th>Urgency</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $approved->fetch_assoc()):
                $urg_cls = ['urgent'=>'danger','medium'=>'warning','normal'=>'success'];
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
                <td><span class="badge" style="background:rgba(204,0,0,.12);color:#CC0000;"><?= $r['blood_type'] ?></span></td>
                <td><strong><?= htmlspecialchars($r['units_requested']) ?></strong> unit<?= $r['units_requested']!=1?'s':'' ?></td>
                <td><?= htmlspecialchars($r['hospital']) ?></td>
                <td><span class="badge bg-<?= $urg_cls[$r['urgency']] ?? 'secondary' ?>"><?= ucfirst($r['urgency']) ?></span></td>
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

<script>
function selectUrgency(el, color) {
  document.querySelectorAll('.urgency-card').forEach(c => {
    c.style.borderColor = '#e5e7eb';
    c.style.background = '#fff';
    c.classList.remove('selected');
  });
  el.style.borderColor = color;
  el.style.background = color + '11';
  el.classList.add('selected');
}

document.addEventListener('DOMContentLoaded', function() {
  const checked = document.querySelector('input[name="urgency"]:checked');
  if (checked) {
    const colors = {urgent:'#CC0000',medium:'#d97706',normal:'#059669'};
    const card = checked.parentElement.querySelector('.urgency-card');
    if (card) selectUrgency(card, colors[checked.value] || '#7c3aed');
  }
});
</script>

<?php require 'includes/footer.php'; ?>
</body>
</html>