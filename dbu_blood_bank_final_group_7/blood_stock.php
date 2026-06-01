<?php
$page_title = 'Blood Stock';
require_once 'config.php';

// Fetch all blood stock
$blood_types = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$stock = [];
foreach ($blood_types as $bt) {
    $esc = $conn->real_escape_string($bt);
    $row = $conn->query("SELECT * FROM blood_stock WHERE blood_type='$esc' LIMIT 1")->fetch_assoc();
    $stock[$bt] = $row ?: ['units'=>0,'status'=>'unavailable','updated_at'=>null];
}
require 'includes/head.php';
?>
<style>
  .stock-card { border-radius: 18px; border: none; box-shadow: 0 4px 20px rgba(0,48,135,.08); transition: .2s; cursor: default; }
  .stock-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,48,135,.12); }
  .blood-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 900; margin: 0 auto 12px; }
  .level-bar { height: 8px; border-radius: 4px; overflow: hidden; background: #e5e7eb; }
  .level-fill { height: 100%; border-radius: 4px; }
  .status-available  { background: rgba(22,163,74,.08);  border: 2px solid #86efac; }
  .status-low        { background: rgba(202,138,4,.08);  border: 2px solid #fde047; }
  .status-critical   { background: rgba(204,0,0,.08);    border: 2px solid #fca5a5; }
  .status-unavailable{ background: rgba(107,114,128,.08);border: 2px solid #d1d5db; }
</style>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="dbu-hero text-white py-5 text-center">
  <h1 class="fw-black display-5">🏥 Blood Stock</h1>
  <p style="color:rgba(255,255,255,.8);">Current blood inventory levels at DBU Health Center — updated by our admin team</p>
</div>

<div class="container py-5">

  <!-- LEGEND -->
  <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
    <?php $legends = [
      ['bg-success','✅ Available'  ,'Sufficient supply'],
      ['bg-warning text-dark','⚠ Low','Running low'],
      ['bg-danger', '🚨 Critical'  ,'Urgently needed'],
      ['bg-secondary','❌ Unavailable','Currently out of stock'],
    ]; foreach ($legends as [$cls,$lbl,$desc]): ?>
    <div class="d-flex align-items-center gap-2">
      <span class="badge <?= $cls ?> px-3 py-2"><?= $lbl ?></span>
      <small class="text-muted"><?= $desc ?></small>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- STOCK GRID -->
  <div class="row g-4 mb-5">
    <?php foreach ($stock as $bt => $s):
      $status = $s['status'] ?? 'unavailable';
      $units  = (int)($s['units'] ?? 0);
      $pct    = min(100, $units * 5);
      $fill_color = match($status) {
        'available'   => '#16a34a',
        'low'         => '#ca8a04',
        'critical'    => '#CC0000',
        default       => '#9ca3af',
      };
      $badge_cls = match($status) {
        'available'   => 'bg-success',
        'low'         => 'bg-warning text-dark',
        'critical'    => 'bg-danger',
        default       => 'bg-secondary',
      };
      $blood_cls = match($status) {
        'available'   => 'status-available',
        'low'         => 'status-low',
        'critical'    => 'status-critical',
        default       => 'status-unavailable',
      };
    ?>
    <div class="col-md-3 col-sm-4 col-6">
      <div class="card stock-card p-4 text-center <?= $blood_cls ?>">
        <div class="blood-circle" style="background:rgba(<?= $status==='available'?'22,163,74':($status==='low'?'202,138,4':($status==='critical'?'204,0,0':'107,114,128')) ?>,.12);">
          <span style="color:<?= $fill_color ?>;font-size:1.5rem;"><?= $bt ?></span>
        </div>
        <div class="fw-black" style="font-size:1.8rem;color:#1a1a2e;"><?= $units ?></div>
        <div class="text-muted small mb-2">units</div>
        <div class="level-bar mb-2">
          <div class="level-fill" style="width:<?= $pct ?>%;background:<?= $fill_color ?>;"></div>
        </div>
        <span class="badge <?= $badge_cls ?> mt-1"><?= ucfirst($status) ?></span>
        <?php if ($s['updated_at']): ?>
          <div class="text-muted mt-2" style="font-size:.72rem;">Updated <?= date('d M Y', strtotime($s['updated_at'])) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- URGENT ALERT (if any critical) -->
  <?php
  $critical_types = array_filter($stock, fn($s) => ($s['status'] ?? '') === 'critical');
  if (!empty($critical_types)):
  ?>
  <div class="alert alert-danger d-flex align-items-start gap-3 mb-5" style="border-radius:14px;">
    <div style="font-size:30px;">🚨</div>
    <div>
      <strong>Urgent Blood Needed!</strong><br>
      We critically need: <strong><?= implode(', ', array_keys($critical_types)) ?></strong>.<br>
      If you are eligible, please donate now.
      <a href="register.php" class="btn btn-danger btn-sm rounded-pill ms-2">Donate Now</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- INFORMATION CARDS -->
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card card-dbu p-4 h-100">
        <div class="fs-2 mb-2">📍</div>
        <h6 class="fw-bold" style="color:#003087;">Donation Center</h6>
        <p class="text-muted small">DBU Health Center, Main Campus Building<br>Mon–Fri: 8:00 AM – 5:00 PM<br>Sat: 9:00 AM – 1:00 PM</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-dbu p-4 h-100">
        <div class="fs-2 mb-2">📞</div>
        <h6 class="fw-bold" style="color:#003087;">Emergency Hotline</h6>
        <p class="text-muted small">For urgent blood needs:<br><strong>+251 11 681 2345</strong><br>Available 24 hours for critical cases</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-dbu p-4 h-100">
        <div class="fs-2 mb-2">💉</div>
        <h6 class="fw-bold" style="color:#003087;">Help Restock</h6>
        <p class="text-muted small">Register as a donor and help keep our blood bank fully stocked for patients in need.</p>
        <a href="register.php" class="btn btn-dbu-red btn-sm rounded-pill mt-2">Register as Donor</a>
      </div>
    </div>
  </div>

</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
