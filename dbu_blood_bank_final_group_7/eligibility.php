<?php
$page_title = 'Eligibility Check';
require_once 'config.php';

$result = '';
$result_type = '';
$next_date_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age        = (int)($_POST['age'] ?? 0);
    $weight     = (float)($_POST['weight'] ?? 0);
    $health     = $_POST['health'] ?? '';
    $hgb        = $_POST['hemoglobin'] ?? '';
    $pregnant   = $_POST['pregnant'] ?? '';
    $surgery    = $_POST['surgery'] ?? '';
    $hiv        = $_POST['hiv'] ?? '';
    $donated    = $_POST['donated'] ?? '';
    $last_date  = $_POST['last_donation_date'] ?? '';
    $bp         = $_POST['blood_pressure'] ?? '';
    $thinners   = $_POST['blood_thinners'] ?? '';

    $disqualifiers = [];

    if ($age < 18 || $age > 65)       $disqualifiers[] = 'Age must be between 18–65 years.';
    if ($weight < 50)                  $disqualifiers[] = 'Weight must be at least 50 kg.';
    if ($health !== 'good')            $disqualifiers[] = 'You must be in good general health.';
    if ($hgb === 'low')                $disqualifiers[] = 'Your hemoglobin level is too low (minimum 12.5 g/dL).';
    if ($pregnant === 'yes')           $disqualifiers[] = 'Pregnant or breastfeeding women cannot donate.';
    if ($surgery === 'yes')            $disqualifiers[] = 'Recent surgery (within 6 months) disqualifies you.';
    if ($hiv === 'yes')                $disqualifiers[] = 'HIV, Hepatitis B or C positive individuals cannot donate.';
    if ($bp === 'abnormal')            $disqualifiers[] = 'Abnormal blood pressure disqualifies you from donating.';
    if ($thinners === 'yes')           $disqualifiers[] = 'Blood thinner medications disqualify you.';

    // Eligibility Tracker: last donation date check
    if ($donated === 'yes' && $last_date) {
        $last_ts   = strtotime($last_date);
        $next_ts   = strtotime('+3 months', $last_ts);
        $now       = time();
        $days_left = ceil(($next_ts - $now) / 86400);

        if ($days_left > 0) {
            $disqualifiers[] = 'You must wait ' . $days_left . ' more day(s) before donating again (3-month interval required).';
            $next_date_msg   = 'Your next eligible donation date is: <strong>' . date('d F Y', $next_ts) . '</strong>';
        } else {
            $next_date_msg = 'You have passed the 3-month waiting period. You are eligible based on donation frequency!';
        }
    } elseif ($donated === 'yes' && !$last_date) {
        $disqualifiers[] = 'You indicated a recent donation — please enter your last donation date to verify.';
    }

    if (empty($disqualifiers)) {
        $result      = 'You appear to be eligible to donate blood! Please visit the DBU Health Center for final verification.';
        $result_type = 'success';
    } else {
        $result      = 'You may not be eligible to donate at this time.';
        $result_type = 'danger';
    }
}
require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="dbu-hero text-white py-5 text-center">
  <h1 class="fw-black display-5">✅ Eligibility Check</h1>
  <p style="color:rgba(255,255,255,.8);">Answer a few quick questions to see if you're eligible to donate blood</p>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card card-dbu p-4 p-md-5 mb-4">
        <div class="alert alert-warning mb-4">
          ⚠ <strong>Disclaimer:</strong> This is a preliminary screening tool only. Final eligibility is determined by a medical professional at the DBU Health Center.
        </div>

        <?php if ($result): ?>
        <div class="alert alert-<?= $result_type ?> mb-4 p-4" style="border-radius:14px;">
          <?php if ($result_type === 'success'): ?>
            <h5 class="fw-bold">🎉 You appear eligible!</h5>
            <p class="mb-0"><?= $result ?></p>
            <?php if ($next_date_msg): ?>
              <div class="mt-2 p-2 rounded-2" style="background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.2);">
                📅 <?= $next_date_msg ?>
              </div>
            <?php endif; ?>
            <div class="mt-3">
              <a href="register.php" class="btn btn-success rounded-pill px-4">Register as Donor</a>
            </div>
          <?php else: ?>
            <h5 class="fw-bold">❌ Not Eligible Right Now</h5>
            <p><?= $result ?></p>
            <?php if (!empty($disqualifiers)): ?>
            <ul class="mb-2">
              <?php foreach ($disqualifiers as $d): ?>
              <li><?= $d ?></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if ($next_date_msg): ?>
              <div class="mt-2 p-2 rounded-2" style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);">
                📅 <?= $next_date_msg ?>
              </div>
            <?php endif; ?>
            <p class="mt-3 mb-0 small">Some conditions are temporary. Check with the health center for clarification.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <h5 class="fw-bold mb-4" style="color:#003087;">📋 Eligibility Questionnaire</h5>
        <form method="POST">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Your Age</label>
              <input type="number" name="age" class="form-control" min="1" max="120"
                     placeholder="e.g. 22" value="<?= (int)($_POST['age'] ?? 0) ?: '' ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Your Weight (kg)</label>
              <input type="number" name="weight" class="form-control" min="1" step="0.1"
                     placeholder="e.g. 60" value="<?= (float)($_POST['weight'] ?? 0) ?: '' ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">General Health</label>
              <select name="health" class="form-select" required>
                <option value="">Select...</option>
                <option value="good"  <?= (($_POST['health'] ?? '') === 'good')  ? 'selected' : '' ?>>✅ Good — No current illness</option>
                <option value="unwell"<?= (($_POST['health'] ?? '') === 'unwell') ? 'selected' : '' ?>>❌ Currently unwell / have fever</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Hemoglobin Level</label>
              <select name="hemoglobin" class="form-select" required>
                <option value="">Select...</option>
                <option value="ok" <?= (($_POST['hemoglobin'] ?? '') === 'ok')  ? 'selected' : '' ?>>✅ Normal / Don't know (assume OK)</option>
                <option value="low"<?= (($_POST['hemoglobin'] ?? '') === 'low') ? 'selected' : '' ?>>❌ Told it's below 12.5 g/dL</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Blood Pressure</label>
              <select name="blood_pressure" class="form-select" required>
                <option value="">Select...</option>
                <option value="normal"  <?= (($_POST['blood_pressure'] ?? '') === 'normal')  ? 'selected' : '' ?>>✅ Normal</option>
                <option value="abnormal"<?= (($_POST['blood_pressure'] ?? '') === 'abnormal') ? 'selected' : '' ?>>❌ High / Low / Irregular</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Pregnant or Breastfeeding?</label>
              <select name="pregnant" class="form-select" required>
                <option value="">Select...</option>
                <option value="no" <?= (($_POST['pregnant'] ?? '') === 'no')  ? 'selected' : '' ?>>No</option>
                <option value="yes"<?= (($_POST['pregnant'] ?? '') === 'yes') ? 'selected' : '' ?>>Yes</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Surgery in the Last 6 Months?</label>
              <select name="surgery" class="form-select" required>
                <option value="">Select...</option>
                <option value="no" <?= (($_POST['surgery'] ?? '') === 'no')  ? 'selected' : '' ?>>No</option>
                <option value="yes"<?= (($_POST['surgery'] ?? '') === 'yes') ? 'selected' : '' ?>>Yes</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">HIV / Hepatitis B or C Positive?</label>
              <select name="hiv" class="form-select" required>
                <option value="">Select...</option>
                <option value="no" <?= (($_POST['hiv'] ?? '') === 'no')  ? 'selected' : '' ?>>No</option>
                <option value="yes"<?= (($_POST['hiv'] ?? '') === 'yes') ? 'selected' : '' ?>>Yes</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Donated Blood in Last 3 Months?</label>
              <select name="donated" class="form-select" required id="donatedSelect">
                <option value="">Select...</option>
                <option value="no" <?= (($_POST['donated'] ?? '') === 'no')  ? 'selected' : '' ?>>No</option>
                <option value="yes"<?= (($_POST['donated'] ?? '') === 'yes') ? 'selected' : '' ?>>Yes</option>
              </select>
            </div>

            <!-- Eligibility Tracker: Last donation date -->
            <div class="col-md-6" id="lastDonationField" style="<?= (($_POST['donated'] ?? '') === 'yes') ? '' : 'display:none;' ?>">
              <label class="form-label fw-semibold">📅 Last Donation Date</label>
              <input type="date" name="last_donation_date" class="form-control"
                     max="<?= date('Y-m-d') ?>"
                     value="<?= htmlspecialchars($_POST['last_donation_date'] ?? '') ?>">
              <div class="text-muted small mt-1">We'll calculate your next eligible donation date.</div>
            </div>

            <div class="col-<?= (($_POST['donated'] ?? '') === 'yes') ? '12' : 'md-6' ?>">
              <label class="form-label fw-semibold">Currently on Blood Thinners?</label>
              <select name="blood_thinners" class="form-select" required>
                <option value="">Select...</option>
                <option value="no" <?= (($_POST['blood_thinners'] ?? '') === 'no')  ? 'selected' : '' ?>>No</option>
                <option value="yes"<?= (($_POST['blood_thinners'] ?? '') === 'yes') ? 'selected' : '' ?>>Yes</option>
              </select>
            </div>
            <div class="col-12 mt-3">
              <button type="submit" class="btn btn-dbu-red w-100 py-3 rounded-pill fw-bold fs-5">✓ Check My Eligibility</button>
            </div>
          </div>
        </form>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <div class="card card-dbu p-3 text-center h-100">
            <div class="fs-2">🎂</div>
            <div class="fw-bold mt-2">Age</div>
            <div class="text-muted small">18 – 65 years</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-dbu p-3 text-center h-100">
            <div class="fs-2">⚖️</div>
            <div class="fw-bold mt-2">Weight</div>
            <div class="text-muted small">At least 50 kg</div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-dbu p-3 text-center h-100">
            <div class="fs-2">⏱️</div>
            <div class="fw-bold mt-2">Frequency</div>
            <div class="text-muted small">Every 3 months max</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
document.getElementById('donatedSelect').addEventListener('change', function() {
  var field = document.getElementById('lastDonationField');
  field.style.display = this.value === 'yes' ? '' : 'none';
});
</script>

<?php require 'includes/footer.php'; ?>
</body>
</html>
