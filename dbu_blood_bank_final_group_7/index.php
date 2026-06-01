<?php
$page_title = 'Home';
require_once 'config.php';
$total_donors   = $conn->query("SELECT COUNT(*) c FROM donors WHERE is_active=1")->fetch_assoc()['c'];
$total_requests = $conn->query("SELECT COUNT(*) c FROM blood_requests")->fetch_assoc()['c'];
$blood_types_list = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$bt_counts  = [];
$stmt_bt    = $conn->prepare("SELECT COUNT(*) AS c FROM donors WHERE blood_type = ? AND is_active = 1");
foreach ($blood_types_list as $bt) {
    $stmt_bt->bind_param('s', $bt);
    $stmt_bt->execute();
    $bt_counts[$bt] = (int)$stmt_bt->get_result()->fetch_assoc()['c'];
}
$stmt_bt->close();
$total_hospitals = (int)$conn->query("SELECT COUNT(*) AS c FROM hospitals WHERE is_active=1")->fetch_assoc()['c'];
$logo_row  = $conn->query("SELECT setting_value FROM system_settings WHERE setting_name='logo_url'")->fetch_assoc();
$hero_logo = $logo_row['setting_value'] ?? '';
require 'includes/head.php';
?>
<style>
  .hero-section {
    background: linear-gradient(135deg,#003087 0%,#1e1b5e 30%,#4c1d95 60%,#7c3aed 80%,#CC0000 100%);
    min-height: 90vh;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
  }
  .hero-section::before {
    content:'';
    position:absolute;inset:0;
    background: radial-gradient(ellipse at 70% 50%,rgba(245,158,11,.12) 0%,transparent 70%),
                radial-gradient(ellipse at 20% 80%,rgba(5,150,105,.1) 0%,transparent 60%);
    pointer-events:none;
  }
  .blood-drop-anim { font-size:90px; animation:pulse 2.2s ease-in-out infinite; }
  @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }

  .blood-type-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:14px; }
  .bt-card {
    border-radius:16px;
    padding:20px 10px;
    text-align:center;
    background:#fff;
    text-decoration:none;
    border:2px solid transparent;
    transition:.22s;
    box-shadow:0 2px 14px rgba(0,48,135,.07);
    display:block;
  }
  .bt-card:hover { transform:translateY(-5px); box-shadow:0 8px 28px rgba(0,48,135,.16); }
  .bt-card .type { font-size:1.7rem; font-weight:900; }
  .bt-card .count { font-size:.82rem; color:#6b7280; margin-top:2px; }
  .bt-card:nth-child(1) { border-top:4px solid #CC0000; } .bt-card:nth-child(1) .type { color:#CC0000; }
  .bt-card:nth-child(2) { border-top:4px solid #7c3aed; } .bt-card:nth-child(2) .type { color:#7c3aed; }
  .bt-card:nth-child(3) { border-top:4px solid #059669; } .bt-card:nth-child(3) .type { color:#059669; }
  .bt-card:nth-child(4) { border-top:4px solid #0891b2; } .bt-card:nth-child(4) .type { color:#0891b2; }
  .bt-card:nth-child(5) { border-top:4px solid #d97706; } .bt-card:nth-child(5) .type { color:#d97706; }
  .bt-card:nth-child(6) { border-top:4px solid #e11d48; } .bt-card:nth-child(6) .type { color:#e11d48; }
  .bt-card:nth-child(7) { border-top:4px solid #4338ca; } .bt-card:nth-child(7) .type { color:#4338ca; }
  .bt-card:nth-child(8) { border-top:4px solid #ea580c; } .bt-card:nth-child(8) .type { color:#ea580c; }

  .step-card {
    border-radius:20px;
    border:none;
    background:#fff;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
    padding:32px 24px;
    text-align:center;
    transition:.25s;
    position:relative;
    overflow:hidden;
  }
  .step-card::before {
    content:'';
    position:absolute;top:0;left:0;right:0;height:5px;
  }
  .step-1::before { background:linear-gradient(90deg,#CC0000,#e11d48); }
  .step-2::before { background:linear-gradient(90deg,#4338ca,#7c3aed); }
  .step-3::before { background:linear-gradient(90deg,#059669,#0891b2); }
  .step-card:hover { transform:translateY(-6px); box-shadow:0 12px 36px rgba(0,0,0,.1); }

  .quick-card {
    border-radius:18px;
    border:none;
    background:#fff;
    box-shadow:0 4px 18px rgba(0,48,135,.07);
    padding:28px 20px;
    height:100%;
    transition:.25s;
    position:relative;
    overflow:hidden;
  }
  .quick-card:hover { transform:translateY(-5px); box-shadow:0 12px 32px rgba(0,48,135,.13); }

  /* Glassmorphism DBU Logo */
  .glass-logo-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 2px solid rgba(255,255,255,.35);
    box-shadow: 0 8px 32px rgba(0,0,0,.25), 0 0 0 4px rgba(245,158,11,.2);
    margin-bottom: 18px;
  }
</style>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<!-- HERO -->
<section class="hero-section text-white">
  <div class="container py-5">
    <div class="row align-items-center gy-5">
      <div class="col-lg-7 fade-up">
        <div class="mb-3">
          <span class="px-3 py-2 rounded-pill fw-bold"
                style="font-size:.78rem;letter-spacing:1.5px;
                       background:rgba(245,158,11,.2);
                       border:1px solid rgba(245,158,11,.5);
                       color:#F59E0B;">
            🏥 DEBRE BERHAN UNIVERSITY
          </span>
        </div>
        <h1 class="display-4 fw-black mb-3">
          Every Drop
          <span style="background:linear-gradient(90deg,#F59E0B,#ea580c);
                       -webkit-background-clip:text;-webkit-text-fill-color:transparent;
                       background-clip:text;">Saves</span>
          a Life
        </h1>
        <p class="lead mb-4" style="color:rgba(255,255,255,.82);max-width:560px;">
          DBU's official blood donation management system. Register as a donor, search for blood, or request emergency blood for patients across our university community.
        </p>
        <div class="d-flex flex-wrap gap-3 mb-5">
          <a href="register.php" class="btn btn-lg fw-bold px-4 rounded-pill"
             style="background:linear-gradient(135deg,#F59E0B,#ea580c);color:#fff;border:none;
                    box-shadow:0 4px 20px rgba(245,158,11,.4);">
            💉 Become a Donor
          </a>
          <a href="request.php" class="btn btn-lg px-4 rounded-pill"
             style="background:rgba(255,255,255,.14);color:#fff;border:1.5px solid rgba(255,255,255,.35);
                    backdrop-filter:blur(8px);">
            🩸 Request Blood
          </a>
        </div>
        <!-- Mini Stats -->
        <div class="row g-3">
          <div class="col-4">
            <div class="stat-pill">
              <div class="fw-black fs-4" style="color:#F59E0B;"><?= $total_donors ?></div>
              <div style="font-size:.78rem;color:rgba(255,255,255,.65);">Active Donors</div>
            </div>
          </div>
          <div class="col-4">
            <div class="stat-pill">
              <div class="fw-black fs-4" style="color:#10b981;"><?= $total_requests ?></div>
              <div style="font-size:.78rem;color:rgba(255,255,255,.65);">Requests</div>
            </div>
          </div>
          <div class="col-4">
            <div class="stat-pill">
              <div class="fw-black fs-4" style="color:#06b6d4;"><?= $total_hospitals ?></div>
              <div style="font-size:.78rem;color:rgba(255,255,255,.65);">Hospitals</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-5 text-center">
        <!-- DBU Logo with Glassmorphism above "Every drop counts" -->
        <?php if ($hero_logo): ?>
          <div class="glass-logo-wrap">
            <img src="<?= htmlspecialchars($hero_logo) ?>" alt="DBU Logo"
                 style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:block;">
          </div>
        <?php else: ?>
          <div class="glass-logo-wrap">
            <div style="width:80px;height:80px;border-radius:50%;
                        background:linear-gradient(135deg,#CC0000,#7c3aed);
                        display:flex;align-items:center;justify-content:center;
                        font-size:22px;font-weight:900;color:#fff;">DBU</div>
          </div>
        <?php endif; ?>
        <p style="color:rgba(255,255,255,.82);font-size:1.1rem;font-weight:700;margin-bottom:8px;letter-spacing:.5px;">
          Every Drop Counts
        </p>
        <div class="blood-drop-anim">🩸</div>
        <p class="mt-3" style="color:rgba(255,255,255,.72);font-size:.95rem;">
          One donation can save<br>
          <strong style="color:#F59E0B;font-size:1.25rem;">up to 3 lives</strong>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- EMERGENCY BAR -->
<div style="background:linear-gradient(90deg,#CC0000,#7c3aed,#4338ca);color:#fff;" class="py-3">
  <div class="container text-center">
    <strong>🆘 Emergency Blood Needed?</strong>&nbsp; Call: <strong>+251 11 681 2345</strong>
    &nbsp;|&nbsp;
    <a href="request.php" class="text-white fw-bold" style="text-decoration:underline;">Submit a Request →</a>
  </div>
</div>

<!-- BLOOD TYPE AVAILABILITY -->
<section class="py-5" style="background:linear-gradient(160deg,#eff6ff 0%,#fdf4ff 50%,#ecfdf5 100%);">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">🩸 Blood Donor Availability</h2>
      <p class="text-muted">Active registered donors by blood type — updated in real time</p>
    </div>
    <div class="blood-type-grid">
      <?php foreach ($blood_types_list as $bt): ?>
      <a href="search.php?blood_type=<?= urlencode($bt) ?>" class="bt-card">
        <div class="type"><?= $bt ?></div>
        <div class="count"><?= $bt_counts[$bt] ?> donor<?= $bt_counts[$bt]!=1?'s':'' ?></div>
        <div class="mt-2 fw-semibold" style="font-size:.75rem;color:<?= $bt_counts[$bt]>0?'#059669':'#e11d48' ?>;">
          <?= $bt_counts[$bt] > 0 ? '✓ Available' : '⚠ Needed' ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="py-5" style="background:#fff;">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">How It Works</h2>
      <p class="text-muted">Three simple steps to help save a life</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="step-card step-1">
          <div class="icon-circle ic-red">📝</div>
          <div class="badge fw-bold px-3 py-1 rounded-pill mb-2"
               style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;">Step 1</div>
          <h5 class="fw-bold mt-1">Register as a Donor</h5>
          <p class="text-muted small">Create your free donor profile with your DBU ID, blood type, and contact information.</p>
          <a href="register.php" class="btn rounded-pill px-4 btn-dbu-red btn-sm">Register Now</a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step-card step-2">
          <div class="icon-circle ic-purple">🔍</div>
          <div class="badge fw-bold px-3 py-1 rounded-pill mb-2"
               style="background:linear-gradient(135deg,#4338ca,#7c3aed);color:#fff;">Step 2</div>
          <h5 class="fw-bold mt-1">Search or Request</h5>
          <p class="text-muted small">Search for available donors by blood type, or submit a blood request for a patient in need.</p>
          <a href="search.php" class="btn rounded-pill px-4 btn-dbu-purple btn-sm">Find Donors</a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step-card step-3">
          <div class="icon-circle ic-emerald">❤️</div>
          <div class="badge fw-bold px-3 py-1 rounded-pill mb-2"
               style="background:linear-gradient(135deg,#059669,#0891b2);color:#fff;">Step 3</div>
          <h5 class="fw-bold mt-1">Donate & Save Lives</h5>
          <p class="text-muted small">Visit our health center to donate and share your experience in the gallery to inspire others.</p>
          <a href="tips.php" class="btn rounded-pill px-4 btn-dbu-emerald btn-sm">Learn More</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- PRE-DONATION & POST-DONATION LIFECYCLE SECTIONS       -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="py-5" style="background:linear-gradient(160deg,#0d1117 0%,#0f172a 40%,#1e1b4b 100%);position:relative;overflow:hidden;">
  <!-- Background blobs -->
  <div style="position:absolute;inset:0;pointer-events:none;
       background:radial-gradient(ellipse at 20% 40%,rgba(204,0,0,.12),transparent 55%),
                  radial-gradient(ellipse at 80% 60%,rgba(124,58,237,.14),transparent 55%),
                  radial-gradient(ellipse at 50% 10%,rgba(5,150,105,.08),transparent 50%);"></div>
  <div class="container" style="position:relative;">

    <div class="text-center mb-5">
      <span class="px-3 py-1 rounded-pill fw-bold d-inline-block mb-3"
            style="font-size:.75rem;letter-spacing:1.5px;background:rgba(245,158,11,.15);
                   border:1px solid rgba(245,158,11,.35);color:#F59E0B;">
        🔄 DONOR LIFECYCLE SERVICES
      </span>
      <h2 class="fw-black text-white" style="font-size:2rem;">Complete Donation Journey</h2>
      <p style="color:rgba(255,255,255,.65);max-width:560px;margin:0 auto;">
        Everything you need — before and after your life-saving donation
      </p>
    </div>

    <div class="row g-4">

      <!-- ── PRE-DONATION CARD ─────────────────────────── -->
      <div class="col-lg-6">
        <div class="rounded-4 p-4 h-100"
             style="background:rgba(255,255,255,.05);
                    border:1.5px solid rgba(204,0,0,.4);
                    backdrop-filter:blur(16px);
                    -webkit-backdrop-filter:blur(16px);
                    box-shadow:0 8px 32px rgba(204,0,0,.15),inset 0 1px 0 rgba(255,255,255,.08);">
          <!-- Header -->
          <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width:52px;height:52px;border-radius:16px;flex-shrink:0;
                 background:linear-gradient(135deg,#CC0000,#e11d48);
                 display:flex;align-items:center;justify-content:center;font-size:22px;
                 box-shadow:0 4px 16px rgba(204,0,0,.4);">💉</div>
            <div>
              <div style="font-size:.7rem;letter-spacing:1.5px;color:#f87171;font-weight:700;text-transform:uppercase;">
                Before Donation
              </div>
              <h4 class="text-white fw-black mb-0" style="font-size:1.25rem;">Pre-Donation Services</h4>
            </div>
          </div>

          <!-- Service items -->
          <div class="d-flex flex-column gap-3">
            <a href="register.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(204,0,0,.12);border:1px solid rgba(204,0,0,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(204,0,0,.22)'" onmouseout="this.style.background='rgba(204,0,0,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#CC0000,#e11d48);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">📝</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">New Donor Registration</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">Create your free donor profile with DBU ID</div>
              </div>
              <span style="color:#f87171;font-size:1.1rem;">→</span>
            </a>

            <a href="eligibility.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(204,0,0,.12);border:1px solid rgba(204,0,0,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(204,0,0,.22)'" onmouseout="this.style.background='rgba(204,0,0,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#059669,#10b981);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">✅</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">Eligibility Check Engine</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">Verify if you qualify to donate blood today</div>
              </div>
              <span style="color:#f87171;font-size:1.1rem;">→</span>
            </a>

            <a href="search.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(204,0,0,.12);border:1px solid rgba(204,0,0,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(204,0,0,.22)'" onmouseout="this.style.background='rgba(204,0,0,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#003087,#4338ca);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">🔍</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">Find Matching Donors</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">Search active donors by blood type in our registry</div>
              </div>
              <span style="color:#f87171;font-size:1.1rem;">→</span>
            </a>

            <a href="tips.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(204,0,0,.12);border:1px solid rgba(204,0,0,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(204,0,0,.22)'" onmouseout="this.style.background='rgba(204,0,0,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#d97706,#F59E0B);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">💡</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">Pre-Donation Tips &amp; Guidelines</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">What to eat, avoid, and prepare before donating</div>
              </div>
              <span style="color:#f87171;font-size:1.1rem;">→</span>
            </a>
          </div>
        </div>
      </div>

      <!-- ── POST-DONATION CARD ────────────────────────── -->
      <div class="col-lg-6">
        <div class="rounded-4 p-4 h-100"
             style="background:rgba(255,255,255,.05);
                    border:1.5px solid rgba(124,58,237,.4);
                    backdrop-filter:blur(16px);
                    -webkit-backdrop-filter:blur(16px);
                    box-shadow:0 8px 32px rgba(124,58,237,.15),inset 0 1px 0 rgba(255,255,255,.08);">
          <!-- Header -->
          <div class="d-flex align-items-center gap-3 mb-4">
            <div style="width:52px;height:52px;border-radius:16px;flex-shrink:0;
                 background:linear-gradient(135deg,#7c3aed,#db2777);
                 display:flex;align-items:center;justify-content:center;font-size:22px;
                 box-shadow:0 4px 16px rgba(124,58,237,.4);">🩸</div>
            <div>
              <div style="font-size:.7rem;letter-spacing:1.5px;color:#c4b5fd;font-weight:700;text-transform:uppercase;">
                After Donation
              </div>
              <h4 class="text-white fw-black mb-0" style="font-size:1.25rem;">Post-Donation Services</h4>
            </div>
          </div>

          <!-- Service items -->
          <div class="d-flex flex-column gap-3">
            <a href="test_results.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(124,58,237,.22)'" onmouseout="this.style.background='rgba(124,58,237,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#059669,#0891b2);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">🔬</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">Lab Screening Results</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">View confidential post-donation health screening</div>
              </div>
              <span style="color:#c4b5fd;font-size:1.1rem;">→</span>
            </a>

            <a href="certificate.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(124,58,237,.22)'" onmouseout="this.style.background='rgba(124,58,237,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#d97706,#F59E0B);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">🏅</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">Appreciation Certificate</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">Download your printable donation certificate</div>
              </div>
              <span style="color:#c4b5fd;font-size:1.1rem;">→</span>
            </a>

            <a href="blood_stock.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(124,58,237,.22)'" onmouseout="this.style.background='rgba(124,58,237,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#0891b2,#06b6d4);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">🏥</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">Blood Inventory Matrix</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">Real-time transfusion blood stock levels by type</div>
              </div>
              <span style="color:#c4b5fd;font-size:1.1rem;">→</span>
            </a>

            <a href="gallery.php" class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
               style="background:rgba(124,58,237,.12);border:1px solid rgba(124,58,237,.2);transition:.2s;"
               onmouseover="this.style.background='rgba(124,58,237,.22)'" onmouseout="this.style.background='rgba(124,58,237,.12)'">
              <div style="width:40px;height:40px;border-radius:12px;flex-shrink:0;
                   background:linear-gradient(135deg,#e11d48,#7c3aed);
                   display:flex;align-items:center;justify-content:center;font-size:18px;">🖼</div>
              <div class="flex-grow-1">
                <div class="fw-bold text-white" style="font-size:.92rem;">Donation Gallery</div>
                <div style="font-size:.78rem;color:rgba(255,255,255,.55);">Share your experience and inspire other donors</div>
              </div>
              <span style="color:#c4b5fd;font-size:1.1rem;">→</span>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- HOSPITAL PORTAL SECTION -->
<section class="py-5" style="background:linear-gradient(160deg,#0d1b2a 0%,#003087 40%,#0891b2 100%);position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 80% 30%,rgba(245,158,11,.12),transparent 60%),
                  radial-gradient(ellipse at 10% 80%,rgba(8,145,178,.18),transparent 55%);
       pointer-events:none;"></div>
  <div class="container" style="position:relative;">

    <div class="text-center mb-5">
      <span class="px-3 py-1 rounded-pill fw-bold mb-3 d-inline-block"
            style="font-size:.76rem;letter-spacing:1.5px;
                   background:rgba(245,158,11,.2);border:1px solid rgba(245,158,11,.4);color:#F59E0B;">
        🏨 FOR HOSPITALS &amp; CLINICS
      </span>
      <h2 class="fw-black text-white" style="font-size:2rem;">Hospital Blood Request Portal</h2>
      <p style="color:rgba(255,255,255,.75);max-width:560px;margin:0 auto;">
        Hospitals and clinics can register, get approved by admin, then request blood for patients directly through the portal.
      </p>
    </div>

    <!-- 3-step flow for hospitals -->
    <div class="row g-4 mb-5">
      <div class="col-md-4">
        <div class="text-center p-4 rounded-4 h-100"
             style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(8px);">
          <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;
               background:linear-gradient(135deg,#F59E0B,#ea580c);
               display:flex;align-items:center;justify-content:center;font-size:24px;">📋</div>
          <div class="badge rounded-pill mb-2" style="background:rgba(245,158,11,.25);color:#F59E0B;font-size:.72rem;letter-spacing:1px;">STEP 1</div>
          <h6 class="fw-bold text-white mb-2">Register Your Hospital</h6>
          <p style="color:rgba(255,255,255,.65);font-size:.85rem;">Fill in your hospital name, email and contact details. Registration is free and takes under a minute.</p>
          <a href="hospital_register.php" class="btn btn-sm rounded-pill fw-bold px-4 mt-2"
             style="background:linear-gradient(135deg,#F59E0B,#ea580c);color:#fff;border:none;">
            Register Now →
          </a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="text-center p-4 rounded-4 h-100"
             style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(8px);">
          <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;
               background:linear-gradient(135deg,#7c3aed,#4338ca);
               display:flex;align-items:center;justify-content:center;font-size:24px;">🛡</div>
          <div class="badge rounded-pill mb-2" style="background:rgba(124,58,237,.25);color:#a78bfa;font-size:.72rem;letter-spacing:1px;">STEP 2</div>
          <h6 class="fw-bold text-white mb-2">Admin Approval</h6>
          <p style="color:rgba(255,255,255,.65);font-size:.85rem;">DBU admin reviews and activates your account. You will receive your login credentials once approved.</p>
          <span class="btn btn-sm rounded-pill px-4 mt-2"
                style="background:rgba(124,58,237,.25);color:#a78bfa;border:1px solid rgba(124,58,237,.4);cursor:default;">
            Pending Review
          </span>
        </div>
      </div>
      <div class="col-md-4">
        <div class="text-center p-4 rounded-4 h-100"
             style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(8px);">
          <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;
               background:linear-gradient(135deg,#059669,#0891b2);
               display:flex;align-items:center;justify-content:center;font-size:24px;">🩸</div>
          <div class="badge rounded-pill mb-2" style="background:rgba(5,150,105,.25);color:#6ee7b7;font-size:.72rem;letter-spacing:1px;">STEP 3</div>
          <h6 class="fw-bold text-white mb-2">Login &amp; Request Blood</h6>
          <p style="color:rgba(255,255,255,.65);font-size:.85rem;">Login with your registered email, view blood stock, and submit blood requests for patients instantly.</p>
          <a href="hospital_login.php" class="btn btn-sm rounded-pill fw-bold px-4 mt-2"
             style="background:linear-gradient(135deg,#059669,#0891b2);color:#fff;border:none;">
            Hospital Login →
          </a>
        </div>
      </div>
    </div>

    <!-- CTA bar -->
    <div class="row g-3 align-items-center justify-content-center">
      <div class="col-auto">
        <a href="hospital_register.php"
           class="btn btn-lg fw-bold px-5 rounded-pill"
           style="background:linear-gradient(135deg,#F59E0B,#ea580c);color:#fff;border:none;
                  box-shadow:0 4px 20px rgba(245,158,11,.35);">
          🏥 Register Your Hospital
        </a>
      </div>
      <div class="col-auto">
        <a href="hospital_login.php"
           class="btn btn-lg px-5 rounded-pill fw-semibold"
           style="background:rgba(255,255,255,.12);color:#fff;
                  border:1.5px solid rgba(255,255,255,.35);">
          🔑 Already Registered? Login
        </a>
      </div>
    </div>

  </div>
</section>

<!-- QUICK LINKS GRID -->
<section class="py-5" style="background:linear-gradient(160deg,#fdf4ff,#eff6ff,#ecfdf5);">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">Explore Our System</h2>
    </div>
    <div class="row g-4">
      <?php
      $cards = [
        ['🔍','Find a Donor','Search active donors by blood type in our registry.','search.php','Search Donors',
         'linear-gradient(135deg,#003087,#4338ca)','linear-gradient(90deg,#003087,#4338ca)'],
        ['📋','Request Blood','Submit an emergency blood request for a patient.','request.php','Request Now',
         'linear-gradient(135deg,#CC0000,#e11d48)','linear-gradient(90deg,#CC0000,#e11d48)'],
        ['🏥','Blood Stock','View current blood stock levels by type.','blood_stock.php','View Stock',
         'linear-gradient(135deg,#0891b2,#06b6d4)','linear-gradient(90deg,#0891b2,#06b6d4)'],
        ['✅','Eligibility','Check if you qualify to donate blood.','eligibility.php','Check Now',
         'linear-gradient(135deg,#059669,#10b981)','linear-gradient(90deg,#059669,#10b981)'],
        ['🏨','Hospital Portal','Hospitals: request blood and manage requests.','hospital_login.php','Hospital Login',
         'linear-gradient(135deg,#0891b2,#003087)','linear-gradient(90deg,#0891b2,#003087)'],
        ['🏅','Donor Certificate','Get your printable blood donation certificate.','certificate.php','View Certificate',
         'linear-gradient(135deg,#d97706,#F59E0B)','linear-gradient(90deg,#d97706,#F59E0B)'],
      ];
      foreach ($cards as [$icon,$title,$desc,$link,$btn,$grad,$topgrad]): ?>
      <div class="col-md-4 col-sm-6">
        <div class="quick-card">
          <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:<?= $topgrad ?>;"></div>
          <div style="width:52px;height:52px;border-radius:14px;
               background:<?= $grad ?>;
               display:flex;align-items:center;justify-content:center;
               font-size:22px;margin-bottom:14px;
               box-shadow:0 4px 14px rgba(0,0,0,.15);"><?= $icon ?></div>
          <h5 class="fw-bold mb-2" style="font-size:1rem;"><?= $title ?></h5>
          <p class="text-muted small flex-grow-1 mb-3"><?= $desc ?></p>
          <a href="<?= $link ?>" class="btn btn-sm rounded-pill px-4 fw-semibold"
             style="background:<?= $grad ?>;color:#fff;border:none;"><?= $btn ?> →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="py-5 text-white text-center"
         style="background:linear-gradient(135deg,#003087 0%,#4c1d95 50%,#CC0000 100%);
                position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 50% 50%,rgba(245,158,11,.12) 0%,transparent 70%);
       pointer-events:none;"></div>
  <div class="container" style="position:relative;">
    <div style="font-size:52px;margin-bottom:16px;">🩸</div>
    <h2 class="fw-black display-6 mb-3">Ready to Save a Life?</h2>
    <p class="mb-4" style="color:rgba(255,255,255,.82);font-size:1.05rem;max-width:520px;margin:0 auto 24px;">
      Join hundreds of DBU donors who have already made a difference.
    </p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="register.php" class="btn btn-lg fw-bold px-5 rounded-pill"
         style="background:linear-gradient(135deg,#F59E0B,#ea580c);color:#fff;border:none;
                box-shadow:0 4px 20px rgba(245,158,11,.35);">💉 Register as Donor</a>
      <a href="about.php" class="btn btn-lg px-5 rounded-pill"
         style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.35);">
        Learn More About Us
      </a>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
</body>
</html>
