<?php
/**
 * Lab Screening Portal — DBU Blood Bank
 * Task 4: Automated Health Screening Notification
 * Admin only — records test results and locks unsafe donors
 */
$page_title = 'Lab Screening';
require_once 'config.php';
require_admin();

$success = '';
$error   = '';

// ── Save Test Results (Task 4) ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_results'])) {
    $donor_id        = (int)($_POST['donor_id'] ?? 0);
    $screening_date  = sanitize($conn, $_POST['screening_date'] ?? date('Y-m-d'));
    $hiv             = in_array($_POST['hiv_status'] ?? '', ['negative','positive']) ? $_POST['hiv_status'] : 'negative';
    $hepatitis       = in_array($_POST['hepatitis_status'] ?? '', ['negative','positive']) ? $_POST['hepatitis_status'] : 'negative';
    $syphilis        = in_array($_POST['syphilis_status'] ?? '', ['negative','positive']) ? $_POST['syphilis_status'] : 'negative';
    $notes           = sanitize($conn, $_POST['notes'] ?? '');

    if ($donor_id <= 0) {
        $error = 'Please select a valid donor.';
    } else {
        // Update verified blood type if submitted (Task 3)
        $verified_bt = sanitize($conn, $_POST['verified_blood_type'] ?? '');
        if ($verified_bt) {
            // Fetch donor current blood type to see if it was 'Unknown'
            $stmt_chk = $conn->prepare("SELECT name, email, blood_type FROM donors WHERE id = ? LIMIT 1");
            $stmt_chk->bind_param('i', $donor_id);
            $stmt_chk->execute();
            $donor_bt = $stmt_chk->get_result()->fetch_assoc();
            $stmt_chk->close();

            if ($donor_bt) {
                // Update blood type, status, and eligibility
                $stmt_upd = $conn->prepare(
                    "UPDATE donors SET blood_type = ?, eligibility_status = 'eligible', status = 'active' WHERE id = ?"
                );
                $stmt_upd->bind_param('si', $verified_bt, $donor_id);
                $stmt_upd->execute();
                $stmt_upd->close();

                if (strtolower($donor_bt['blood_type']) === 'unknown') {
                    // Task 3: Dispatch real email via PHPMailer + audit log
                    $subject = 'DBU Blood Bank — Your Blood Type Has Been Verified';
                    $body    = "Dear {$donor_bt['name']},\n\nThank you for your recent visit to the DBU Blood Bank laboratory. Your serological blood typing analysis is now finalized.\n\nYour verified blood group is: {$verified_bt}\n\nYou are now fully activated in our donor network and eligible to save lives. Please log in to your donor dashboard to view your complete profile.\n\nWith gratitude,\nDBU Blood Bank Medical Team\nDebre Berhan University";
                    $db->logNotification($donor_id, $donor_bt['email'], $subject, $body, 'blood_type_verified', $donor_bt['name']);
                }
            }
        }

        // Determine overall outcome
        $overall = ($hiv === 'positive' || $hepatitis === 'positive' || $syphilis === 'positive') ? 'unsafe' : 'safe';

        // Insert test result
        $stmt = $conn->prepare(
            "INSERT INTO test_results (donor_id, screening_date, hiv_status, hepatitis_status, syphilis_status, overall_outcome, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issssss', $donor_id, $screening_date, $hiv, $hepatitis, $syphilis, $overall, $notes);
        $stmt->execute();
        $stmt->close();

        // Get donor info for notification (re-fetched so it has the updated verified blood type)
        $stmt_d = $conn->prepare("SELECT name, email, blood_type FROM donors WHERE id = ? LIMIT 1");
        $stmt_d->bind_param('i', $donor_id);
        $stmt_d->execute();
        $donor = $stmt_d->get_result()->fetch_assoc();
        $stmt_d->close();

        if ($overall === 'unsafe') {
            // Task 4: Lock donor eligibility to 'unsafe'
            $stmt_lock = $conn->prepare("UPDATE donors SET eligibility_status = 'unsafe', is_active = 0, status = 'blocked' WHERE id = ?");
            $stmt_lock->bind_param('i', $donor_id);
            $stmt_lock->execute();
            $stmt_lock->close();

            // Build disease list for notification
            $diseases = [];
            if ($hiv === 'positive')       $diseases[] = 'HIV';
            if ($hepatitis === 'positive') $diseases[] = 'Hepatitis';
            if ($syphilis === 'positive')  $diseases[] = 'Syphilis';
            $disease_str = implode(', ', $diseases);

            // Task 4 (Unsafe): Real SMTP email + audit trail — including details of diseases found
            $subject = 'DBU Blood Bank — Urgent: Medical Update Required';
            $body    = "Dear {$donor['name']},\n\nYour laboratory screening is complete. Unfortunately, your blood sample has tested POSITIVE for the following screened marker(s): {$disease_str} (በምርመራው ወቅት የሚከተሉት በሽታዎች/ቫይረሶች ተገኝተውብሃል: {$disease_str}).\n\nDue to this, your donor account has been blocked to protect the blood supply. Please report directly to the lead supervisor at the Debre Berhan University Campus Clinic laboratory with your Student ID card to receive confidential counseling and your medical report.\n\nDBU Blood Bank Medical Division\nDebre Berhan University";
            $emailSent = $db->logNotification($donor_id, $donor['email'], $subject, $body, 'health_alert', $donor['name']);
            $emailNote = $emailSent ? ' Confidential email dispatched to donor.' : ' Alert logged (enable SMTP in mail_config.php for real emails).';

            $success = "⚠ Screening saved. Donor <strong>{$donor['name']}</strong> flagged as UNSAFE and account locked. Confidential alert logged for: <em>{$disease_str}</em>.{$emailNote}";
        } else {
            // Task 4: Update eligibility to safe
            $stmt_safe = $conn->prepare("UPDATE donors SET eligibility_status = 'eligible' WHERE id = ?");
            $stmt_safe->bind_param('i', $donor_id);
            $stmt_safe->execute();
            $stmt_safe->close();

            // Automatically add 1 unit of blood to stock
            $db->addStockOnSafeDonation($donor['blood_type'], 1);

            // Task 4 (Safe): Real SMTP email + audit trail — exact master prompt text
            $subject = 'DBU Blood Bank — Laboratory Screening Results: All Clear!';
            $body    = "Dear {$donor['name']},\n\nYour health screening is SAFE. Your verified blood type is {$donor['blood_type']}. You are healthy and fully eligible to donate blood. Please log into your dashboard to download your appreciation certificate.\n\nDBU Blood Bank Medical Team\nDebre Berhan University";
            $emailSent = $db->logNotification($donor_id, $donor['email'], $subject, $body, 'health_clear', $donor['name']);

            $success = "✓ Screening saved for <strong>{$donor['name']}</strong>. All tests negative — donor remains eligible and 1 unit of {$donor['blood_type']} added to stock.";
        }
    }
}

// ── Fetch donors for dropdown ─────────────────────────────────
$donors_res = $conn->query("SELECT id, name, blood_type, eligibility_status FROM donors WHERE is_active = 1 ORDER BY name");
$all_donors = $donors_res ? $donors_res->fetch_all(MYSQLI_ASSOC) : [];

// ── Fetch existing results ────────────────────────────────────
$results_res = $conn->query("
    SELECT tr.*, d.name AS donor_name, d.email, d.blood_type
    FROM test_results tr
    JOIN donors d ON d.id = tr.donor_id
    ORDER BY tr.recorded_at DESC
    LIMIT 50
");
$results = $results_res ? $results_res->fetch_all(MYSQLI_ASSOC) : [];

require 'includes/head.php';
?>
</head>
<body>
<!-- Admin Nav reuse -->
<?php
$current_logo = '';
$logo_res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_name='logo_url'");
if ($logo_res && $logo_row = $logo_res->fetch_assoc()) {
    $current_logo = $logo_row['setting_value'] ?? '';
}
?>
<nav class="navbar navbar-dark px-4 py-2"
     style="background:linear-gradient(135deg,#003087 0%,#1e1b5e 50%,#4c1d95 100%);
            box-shadow:0 4px 20px rgba(0,0,0,.35);">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#CC0000,#7c3aed);
           border:2px solid #F59E0B;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:#fff;">DBU</div>
      <span class="fw-bold">DBU <span style="color:#F59E0B;">Blood</span>Bank
        <small style="color:rgba(255,255,255,.6);font-size:11px;display:block;line-height:1;">Lab Screening Portal</small>
      </span>
    </a>
    <div class="d-flex align-items-center gap-3">
      <a href="admin_panel.php" class="btn btn-sm rounded-pill" style="background:rgba(255,255,255,.1);color:#fff;border:none;">← Admin Panel</a>
      <a href="logout.php" class="btn btn-sm rounded-pill fw-semibold" style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border:none;">Logout</a>
    </div>
  </div>
</nav>

<div style="background:linear-gradient(135deg,#003087,#1e1b5e,#4c1d95);padding:30px;">
  <div class="container">
    <h2 class="text-white fw-black mb-1">🔬 Laboratory Screening Portal</h2>
    <p style="color:rgba(255,255,255,.7);">Record and manage post-donation health screening results</p>
  </div>
</div>

<div class="container py-4">

  <?php if ($success): ?>
  <div class="alert border-0 rounded-3 mb-4" style="background:#f0fdf4;border-left:4px solid #059669!important;color:#065f46;">
    <strong>✓</strong> <?= $success ?>
  </div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-danger rounded-3 mb-4">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Enter Results Form -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">📋 Enter Screening Results</h5>
        <form method="POST">
          <input type="hidden" name="save_results" value="1">

          <div class="mb-3">
            <label class="form-label fw-semibold">Select Donor <span class="text-danger">*</span></label>
            <select name="donor_id" class="form-select" required>
              <option value="">— Choose Donor —</option>
              <?php foreach ($all_donors as $d): ?>
              <option value="<?= $d['id'] ?>" data-blood-type="<?= htmlspecialchars($d['blood_type']) ?>" <?= ((int)($_POST['donor_id'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['blood_type']) ?>)
                <?= $d['eligibility_status'] !== 'eligible' ? ' — ⚠ ' . htmlspecialchars($d['eligibility_status']) : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3 d-none" id="blood_type_verification_sec">
            <label class="form-label fw-bold text-warning">Verify Blood Type <span class="text-danger">*</span></label>
            <select name="verified_blood_type" class="form-select border-warning" id="verified_blood_type_select">
              <option value="">— Select Verified Blood Type —</option>
              <option value="A+">A+</option>
              <option value="A-">A-</option>
              <option value="B+">B+</option>
              <option value="B-">B-</option>
              <option value="AB+">AB+</option>
              <option value="AB-">AB-</option>
              <option value="O+">O+</option>
              <option value="O-">O-</option>
            </select>
            <div class="text-muted small mt-1">Since this donor is currently marked as 'Unknown', you must select their laboratory-verified blood type.</div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Screening Date <span class="text-danger">*</span></label>
            <input type="date" name="screening_date" class="form-control"
                   value="<?= htmlspecialchars($_POST['screening_date'] ?? date('Y-m-d')) ?>"
                   max="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">🦠 Test Results</label>
            <div class="rounded-3 p-3" style="background:#f8fafc;border:1px solid #e2e8f0;">

              <?php
              $tests = [
                ['name' => 'hiv_status', 'label' => 'HIV'],
                ['name' => 'hepatitis_status', 'label' => 'Hepatitis B/C'],
                ['name' => 'syphilis_status', 'label' => 'Syphilis'],
              ];
              foreach ($tests as $t):
                $cur = $_POST[$t['name']] ?? 'negative';
              ?>
              <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="fw-semibold"><?= $t['label'] ?></span>
                <div class="d-flex gap-3">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="<?= $t['name'] ?>" id="<?= $t['name'] ?>_neg" value="negative" <?= $cur === 'negative' ? 'checked' : '' ?>>
                    <label class="form-check-label text-success fw-semibold" for="<?= $t['name'] ?>_neg">✓ Negative</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="<?= $t['name'] ?>" id="<?= $t['name'] ?>_pos" value="positive" <?= $cur === 'positive' ? 'checked' : '' ?>>
                    <label class="form-check-label text-danger fw-semibold" for="<?= $t['name'] ?>_pos">✗ Positive</label>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Clinical Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
          </div>

          <div class="alert alert-warning border-0 rounded-3 mb-3 small">
            ⚠ <strong>Warning:</strong> If any result is <strong>Positive</strong>, the donor's account will be automatically locked and a confidential alert will be logged.
          </div>

          <button type="submit" class="btn w-100 fw-bold rounded-pill py-2"
                  style="background:linear-gradient(135deg,#003087,#4c1d95);color:#fff;border:none;">
            🔬 Save Screening Results
          </button>
        </form>
      </div>
    </div>

    <!-- Results History -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">📊 Screening History (Last 50)</h5>
        <?php if (empty($results)): ?>
          <div class="text-center text-muted py-5">
            <div style="font-size:3rem;">🔬</div>
            <p>No screening results recorded yet.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr style="background:linear-gradient(135deg,#003087,#4c1d95);color:#fff;">
                  <th class="rounded-start">Donor</th>
                  <th>Date</th>
                  <th>HIV</th>
                  <th>Hepatitis</th>
                  <th>Syphilis</th>
                  <th class="rounded-end">Outcome</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($r['donor_name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($r['blood_type']) ?></div>
                  </td>
                  <td class="small"><?= htmlspecialchars($r['screening_date']) ?></td>
                  <td>
                    <?php $cl = $r['hiv_status'] === 'positive' ? 'danger' : 'success'; ?>
                    <span class="badge bg-<?= $cl ?>-subtle text-<?= $cl ?> border border-<?= $cl ?>">
                      <?= $r['hiv_status'] === 'positive' ? '✗ POS' : '✓ NEG' ?>
                    </span>
                  </td>
                  <td>
                    <?php $cl = $r['hepatitis_status'] === 'positive' ? 'danger' : 'success'; ?>
                    <span class="badge bg-<?= $cl ?>-subtle text-<?= $cl ?> border border-<?= $cl ?>">
                      <?= $r['hepatitis_status'] === 'positive' ? '✗ POS' : '✓ NEG' ?>
                    </span>
                  </td>
                  <td>
                    <?php $cl = $r['syphilis_status'] === 'positive' ? 'danger' : 'success'; ?>
                    <span class="badge bg-<?= $cl ?>-subtle text-<?= $cl ?> border border-<?= $cl ?>">
                      <?= $r['syphilis_status'] === 'positive' ? '✗ POS' : '✓ NEG' ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($r['overall_outcome'] === 'unsafe'): ?>
                      <span class="badge bg-danger fw-bold">⚠ UNSAFE</span>
                    <?php else: ?>
                      <span class="badge bg-success fw-bold">✓ SAFE</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const donorSelect = document.querySelector('select[name="donor_id"]');
    const verifySec = document.getElementById('blood_type_verification_sec');
    const verifySelect = document.getElementById('verified_blood_type_select');

    function checkDonorType() {
        const selectedOpt = donorSelect.options[donorSelect.selectedIndex];
        if (selectedOpt) {
            const bloodType = selectedOpt.getAttribute('data-blood-type');
            if (bloodType && bloodType.toLowerCase() === 'unknown') {
                verifySec.classList.remove('d-none');
                verifySelect.setAttribute('required', 'required');
            } else {
                verifySec.classList.add('d-none');
                verifySelect.removeAttribute('required');
                verifySelect.value = '';
            }
        } else {
            verifySec.classList.add('d-none');
            verifySelect.removeAttribute('required');
            verifySelect.value = '';
        }
    }

    donorSelect.addEventListener('change', checkDonorType);
    checkDonorType(); // Run initially
});
</script>

<?php require 'includes/footer.php'; ?>
</body>
</html>
