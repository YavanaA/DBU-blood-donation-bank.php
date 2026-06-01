<?php
$page_title = 'Find a Donor';
require_once 'config.php';

$results     = [];
$searched    = false;
$blood_type  = sanitize($conn, $_GET['blood_type'] ?? '');
$blood_types = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];

if ($blood_type) {
    $searched = true;
    $stmt = $conn->prepare("SELECT name, phone, blood_type FROM donors WHERE blood_type = ? AND is_active = 1 ORDER BY name");
    $stmt->bind_param('s', $blood_type);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $results[] = $row;
    $stmt->close();
}

// Total count per type for quick stats
$bt_counts = [];
$stmt_cnt = $conn->prepare("SELECT COUNT(*) AS c FROM donors WHERE blood_type = ? AND is_active = 1");
foreach ($blood_types as $bt) {
    $stmt_cnt->bind_param('s', $bt);
    $stmt_cnt->execute();
    $bt_counts[$bt] = (int)$stmt_cnt->get_result()->fetch_assoc()['c'];
}
$stmt_cnt->close();

$bt_colors = [
  'A+'=>'#CC0000','A-'=>'#7c3aed','B+'=>'#059669','B-'=>'#0891b2',
  'AB+'=>'#d97706','AB-'=>'#e11d48','O+'=>'#4338ca','O-'=>'#ea580c',
];

require 'includes/head.php';
?>
<style>
  .bt-quick { border-radius:12px;padding:12px 8px;text-align:center;cursor:pointer;
    transition:.18s;border:2px solid transparent;background:#fff;
    box-shadow:0 2px 10px rgba(0,48,135,.06);text-decoration:none;display:block; }
  .bt-quick:hover { transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,48,135,.12); }

  .donor-card {
    border-radius:16px;border:none;background:#fff;
    box-shadow:0 3px 16px rgba(0,48,135,.07);
    transition:.2s;overflow:hidden;
    border-left:5px solid;
  }
  .donor-card:hover { transform:translateY(-3px);box-shadow:0 8px 28px rgba(0,48,135,.12); }
</style>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<!-- Hero -->
<div class="text-white py-5 text-center"
     style="background:linear-gradient(135deg,#003087 0%,#4c1d95 40%,#059669 75%,#0891b2 100%);
            position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 70% 40%,rgba(245,158,11,.12),transparent 60%),
                 radial-gradient(ellipse at 20% 70%,rgba(204,0,0,.08),transparent 50%);
       pointer-events:none;"></div>
  <div style="position:relative;">
    <div style="font-size:52px;margin-bottom:10px;">🔍</div>
    <h1 class="fw-black display-4">Find a Donor</h1>
    <p style="color:rgba(255,255,255,.85);font-size:1.05rem;max-width:520px;margin:0 auto 16px;">
      Search our registry of active voluntary blood donors by blood type.
    </p>
    <div style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);
                display:inline-block;padding:8px 20px;border-radius:20px;font-size:.88rem;color:rgba(255,255,255,.85);">
      🔒 Only name and phone number are shown publicly — donor privacy is protected
    </div>
  </div>
</div>

<div class="container py-5">

  <!-- Search Form -->
  <div class="card card-dbu p-4 p-md-5 mb-5">
    <h5 class="fw-bold mb-4" style="color:#003087;">🩸 Select Blood Type to Search</h5>
    <form method="GET" id="searchForm">
      <div class="row g-3 align-items-end">
        <div class="col-md-8">
          <label class="form-label fw-semibold">Required Blood Type</label>
          <select name="blood_type" class="form-select form-select-lg"
                  style="border-radius:12px;border:2px solid #e5e7eb;" required
                  onchange="document.getElementById('searchForm').submit()">
            <option value="">— Choose Blood Type —</option>
            <?php foreach ($blood_types as $bt): ?>
              <option value="<?= $bt ?>" <?= $blood_type === $bt ? 'selected' : '' ?>>
                <?= $bt ?> — <?= $bt_counts[$bt] ?> donor<?= $bt_counts[$bt]!=1?'s':'' ?> available
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-lg w-100 fw-bold rounded-pill"
                  style="background:linear-gradient(135deg,#003087,#7c3aed);color:#fff;border:none;
                         box-shadow:0 4px 16px rgba(0,48,135,.25);">
            🔍 Search Donors
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Blood Type Quick Stats -->
  <div class="mb-5">
    <h5 class="fw-bold mb-3" style="color:#003087;">📊 Quick Availability Overview</h5>
    <div class="row g-2 row-cols-4 row-cols-sm-4 row-cols-md-8">
      <?php foreach ($blood_types as $bt):
        $col = $bt_colors[$bt] ?? '#003087';
        $cnt = $bt_counts[$bt];
        $bg  = $cnt > 0 ? "{$col}14" : '#f9fafb';
        $border_col = $cnt > 0 ? "{$col}55" : '#e5e7eb';
      ?>
      <div class="col">
        <a href="?blood_type=<?= urlencode($bt) ?>" class="bt-quick" style="border-color:<?= $border_col ?>;background:<?= $bg ?>">
          <div class="fw-black" style="font-size:1.2rem;color:<?= $col ?>;"><?= $bt ?></div>
          <div class="fw-semibold" style="font-size:.72rem;color:<?= $cnt>0?$col:'#9ca3af' ?>;">
            <?= $cnt > 0 ? "✓ $cnt" : "⚠ 0" ?>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Results -->
  <?php if ($searched): ?>
    <?php $col = $bt_colors[$blood_type] ?? '#003087'; ?>
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <div style="width:50px;height:50px;border-radius:50%;
             background:<?= $col ?>1a;border:3px solid <?= $col ?>55;
             display:flex;align-items:center;justify-content:center;
             font-size:1.1rem;font-weight:900;color:<?= $col ?>;">
          <?= htmlspecialchars($blood_type) ?>
        </div>
        <div>
          <h5 class="fw-bold mb-0">Blood Type <span style="color:<?= $col ?>;"><?= htmlspecialchars($blood_type) ?></span> Donors</h5>
          <div class="text-muted small">Active registered donors ready to help</div>
        </div>
      </div>
      <span class="badge px-3 py-2 rounded-pill fw-bold"
            style="background:<?= $col ?>1a;color:<?= $col ?>;border:2px solid <?= $col ?>44;font-size:.92rem;">
        <?= count($results) ?> donor<?= count($results)!=1?'s':'' ?> found
      </span>
    </div>

    <?php if (empty($results)): ?>
      <div class="card card-dbu p-5 text-center">
        <div style="font-size:60px;margin-bottom:16px;">🩸</div>
        <h4 class="fw-bold" style="color:#003087;">No Donors Found for <?= htmlspecialchars($blood_type) ?></h4>
        <p class="text-muted mb-4">There are no active donors with this blood type right now. You can submit a formal blood request instead.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
          <a href="request.php" class="btn rounded-pill px-4 fw-bold"
             style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border:none;">
            📋 Submit Blood Request
          </a>
          <a href="register.php" class="btn rounded-pill px-4 fw-semibold"
             style="background:linear-gradient(135deg,#003087,#7c3aed);color:#fff;border:none;">
            💉 Register as Donor
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($results as $i => $d):
          $col = $bt_colors[$d['blood_type']] ?? '#003087';
          $initials = strtoupper(substr($d['name'], 0, 1));
          $avatar_colors = ['#CC0000','#7c3aed','#059669','#0891b2','#d97706','#e11d48','#4338ca','#ea580c'];
          $av_bg = $avatar_colors[$i % count($avatar_colors)];
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="donor-card p-4 d-flex align-items-center gap-3" style="border-color:<?= $col ?>;">
            <div style="width:56px;height:56px;border-radius:50%;flex-shrink:0;
                 background:linear-gradient(135deg,<?= $av_bg ?>,<?= $col ?>);
                 display:flex;align-items:center;justify-content:center;
                 font-size:1.4rem;font-weight:900;color:#fff;
                 box-shadow:0 4px 12px <?= $av_bg ?>44;">
              <?= $initials ?>
            </div>
            <div class="flex-grow-1 overflow-hidden">
              <div class="fw-bold text-truncate" style="font-size:.95rem;">
                <?= htmlspecialchars($d['name']) ?>
              </div>
              <div class="mt-1">
                <span class="badge fw-semibold px-2 py-1" style="background:<?= $col ?>1a;color:<?= $col ?>;font-size:.78rem;">
                  🩸 <?= $d['blood_type'] ?>
                </span>
              </div>
              <div class="fw-semibold mt-2" style="color:#003087;font-size:.88rem;">
                📞 <?= htmlspecialchars($d['phone']) ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-5 p-4 rounded-4 text-center"
           style="background:linear-gradient(135deg,rgba(0,48,135,.04),rgba(124,58,237,.06));">
        <p class="text-muted mb-2">
          Found <strong><?= count($results) ?></strong> donor<?= count($results)!=1?'s':'' ?> with blood type
          <strong style="color:<?= $col ?>;"><?= htmlspecialchars($blood_type) ?></strong>.
          Contact them directly or submit a formal request for admin coordination.
        </p>
        <a href="request.php" class="btn rounded-pill px-4 fw-bold"
           style="background:linear-gradient(135deg,#CC0000,#7c3aed);color:#fff;border:none;">
          📋 Submit a Formal Request
        </a>
      </div>
    <?php endif; ?>
  <?php endif; ?>

</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
