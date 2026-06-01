<?php
$page_title = 'Donor Certificate';
require_once 'config.php';

$donor = null;
if (is_logged_in()) {
    $id  = (int)$_SESSION['donor_id'];
    $res = $conn->query("SELECT * FROM donors WHERE id=$id LIMIT 1");
    $donor = $res->fetch_assoc();
}

// Guest certificate preview by donor ID
if (!$donor && isset($_GET['id'])) {
    $id  = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM donors WHERE id=$id AND is_active=1 LIMIT 1");
    $donor = $res->fetch_assoc();
}
require 'includes/head.php';
?>
<style>
  @media print {
    .no-print { display:none !important; }
    body { background:#fff !important; }
    .certificate-box {
      box-shadow: none !important;
      border: 3px solid #CC0000 !important;
      break-inside: avoid;
    }
  }
</style>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="text-white py-5 text-center no-print"
     style="background:linear-gradient(135deg,#003087 0%,#7c3aed 60%,#CC0000 100%);
            position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 50% 50%,rgba(245,158,11,.1),transparent 70%);
       pointer-events:none;"></div>
  <div style="position:relative;">
    <div style="font-size:48px;margin-bottom:8px;">🏅</div>
    <h1 class="fw-black fs-2">Donor Certificate</h1>
    <p style="color:rgba(255,255,255,.8);">Your recognition for saving lives through voluntary blood donation</p>
  </div>
</div>

<div class="container py-5">

  <?php if (!$donor): ?>
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card card-dbu p-5 text-center">
        <div style="font-size:52px;margin-bottom:16px;">🏅</div>
        <h4 class="fw-bold mb-3">View Your Certificate</h4>
        <p class="text-muted mb-4">Please log in to access your personalized donor certificate.</p>
        <a href="login.php" class="btn rounded-pill px-5 fw-bold"
           style="background:linear-gradient(135deg,#CC0000,#7c3aed);color:#fff;border:none;">
          🔑 Login to View Certificate
        </a>
        <div class="mt-3">
          <a href="register.php" class="text-muted small">Not a donor yet? Register here</a>
        </div>
      </div>
    </div>
  </div>

  <?php else: ?>

  <!-- Print Button -->
  <div class="text-center mb-4 no-print">
    <button onclick="window.print()" class="btn btn-lg rounded-pill fw-bold px-5"
            style="background:linear-gradient(135deg,#003087,#7c3aed);color:#fff;border:none;
                   box-shadow:0 4px 20px rgba(0,48,135,.3);">
      🖨️ Print Certificate
    </button>
    <a href="dashboard.php" class="btn btn-lg rounded-pill px-5 ms-2"
       style="background:#f3f4f6;color:#374151;border:none;">
      ← Back to Dashboard
    </a>
  </div>

  <!-- CERTIFICATE -->
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="certificate-box"
           style="background:#fff;
                  border-radius:20px;
                  box-shadow:0 16px 60px rgba(0,0,0,.15);
                  padding:0;
                  overflow:hidden;
                  border:1px solid #e5e7eb;">

        <!-- Certificate Top Bar -->
        <div style="height:12px;background:linear-gradient(90deg,#003087,#CC0000,#7c3aed,#059669,#F59E0B);"></div>

        <div style="padding:52px 60px;">
          <!-- Header with University Branding -->
          <div class="text-center mb-4">
            <?php
            $logo_row2 = $conn->query("SELECT setting_value FROM system_settings WHERE setting_name='logo_url'")->fetch_assoc();
            $cert_logo = $logo_row2['setting_value'] ?? '';
            ?>
            <?php if ($cert_logo): ?>
              <div style="display:inline-block;padding:16px;border-radius:50%;
                          background:rgba(255,255,255,.9);
                          backdrop-filter:blur(12px);
                          border:3px solid rgba(245,158,11,.6);
                          box-shadow:0 8px 32px rgba(0,48,135,.2),0 0 0 6px rgba(245,158,11,.15);
                          margin-bottom:16px;">
                <img src="<?= htmlspecialchars($cert_logo) ?>" alt="DBU Logo"
                     style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:block;">
              </div>
            <?php else: ?>
              <div style="display:inline-flex;width:90px;height:90px;border-radius:50%;
                          background:linear-gradient(135deg,#003087,#7c3aed);
                          border:4px solid #F59E0B;
                          box-shadow:0 8px 32px rgba(0,48,135,.3),0 0 0 6px rgba(245,158,11,.15);
                          align-items:center;justify-content:center;
                          font-size:22px;font-weight:900;color:#fff;
                          margin-bottom:16px;">DBU</div>
            <?php endif; ?>

            <div style="font-size:.8rem;letter-spacing:3px;text-transform:uppercase;color:#6b7280;font-weight:600;margin-bottom:4px;">
              DEBRE BERHAN UNIVERSITY
            </div>
            <div style="font-size:.75rem;letter-spacing:2px;text-transform:uppercase;color:#CC0000;font-weight:600;">
              Blood Bank Management System
            </div>
          </div>

          <!-- Decorative Line -->
          <div style="height:2px;background:linear-gradient(90deg,transparent,#CC0000,#7c3aed,transparent);
                      margin:20px 0;border-radius:2px;"></div>

          <!-- Certificate Title -->
          <div class="text-center mb-4">
            <h1 style="font-size:2.2rem;font-weight:900;letter-spacing:2px;text-transform:uppercase;
                       background:linear-gradient(135deg,#003087,#CC0000);
                       -webkit-background-clip:text;-webkit-text-fill-color:transparent;
                       background-clip:text;margin-bottom:8px;">
              Certificate of Appreciation
            </h1>
            <p style="color:#6b7280;font-size:.9rem;letter-spacing:1px;text-transform:uppercase;">
              Blood Donation Recognition
            </p>
          </div>

          <!-- Certificate Body -->
          <div class="text-center mb-4" style="padding:0 20px;">
            <p style="color:#374151;font-size:1rem;line-height:1.8;margin-bottom:8px;">
              This certificate is proudly presented to
            </p>
            <div style="font-size:2rem;font-weight:900;color:#003087;letter-spacing:1px;
                        border-bottom:3px solid;
                        border-image:linear-gradient(90deg,#003087,#CC0000) 1;
                        padding-bottom:8px;margin-bottom:16px;display:inline-block;">
              <?= htmlspecialchars($donor['name']) ?>
            </div>

            <div style="margin:12px 0;">
              <span style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;
                           padding:6px 20px;border-radius:20px;font-weight:700;font-size:.9rem;">
                🩸 Blood Type: <?= htmlspecialchars($donor['blood_type']) ?>
              </span>
              &nbsp;
              <span style="background:linear-gradient(135deg,#003087,#4338ca);color:#fff;
                           padding:6px 20px;border-radius:20px;font-weight:700;font-size:.9rem;">
                🎓 <?= htmlspecialchars($donor['dbu_id']) ?>
              </span>
            </div>
          </div>

          <!-- Gratitude Message -->
          <div class="text-center p-4 mb-4"
               style="background:linear-gradient(135deg,rgba(0,48,135,.04),rgba(124,58,237,.06),rgba(204,0,0,.04));
                      border-radius:16px;border:1px solid rgba(0,48,135,.1);">
            <p style="font-size:1.05rem;line-height:1.8;color:#374151;font-style:italic;margin:0;">
              "Deepest gratitude for saving a human life. Your bravery and kindness are an inspiration.
              Through your selfless act of voluntary blood donation, you have given the most precious gift —
              the gift of life. May your generosity be a beacon of hope for all."
            </p>
          </div>

          <!-- Details Row -->
          <div class="row g-3 mb-4 text-center">
            <div class="col-4">
              <div style="padding:16px;background:#f8faff;border-radius:12px;border:1px solid #e0e7ff;">
                <div style="font-size:1.2rem;margin-bottom:4px;">📅</div>
                <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:1px;">Registered</div>
                <div style="font-weight:700;color:#003087;font-size:.85rem;">
                  <?= date('d F Y', strtotime($donor['created_at'])) ?>
                </div>
              </div>
            </div>
            <div class="col-4">
              <div style="padding:16px;background:#fff8f0;border-radius:12px;border:1px solid #fde8c8;">
                <div style="font-size:1.2rem;margin-bottom:4px;">🎓</div>
                <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:1px;">University</div>
                <div style="font-weight:700;color:#d97706;font-size:.85rem;">Debre Berhan University</div>
              </div>
            </div>
            <div class="col-4">
              <div style="padding:16px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
                <div style="font-size:1.2rem;margin-bottom:4px;">🏆</div>
                <div style="font-size:.72rem;color:#6b7280;text-transform:uppercase;letter-spacing:1px;">Issued</div>
                <div style="font-weight:700;color:#059669;font-size:.85rem;"><?= date('d F Y') ?></div>
              </div>
            </div>
          </div>

          <!-- Decorative Line -->
          <div style="height:2px;background:linear-gradient(90deg,transparent,#CC0000,#7c3aed,transparent);
                      margin:20px 0;border-radius:2px;"></div>

          <!-- Signatures Row -->
          <div class="row text-center">
            <div class="col-6">
              <div style="border-top:2px solid #003087;padding-top:10px;margin:0 20px;">
                <div style="font-weight:700;color:#003087;">Blood Bank Coordinator</div>
                <div style="font-size:.8rem;color:#6b7280;">DBU Health Center</div>
              </div>
            </div>
            <div class="col-6">
              <div style="border-top:2px solid #CC0000;padding-top:10px;margin:0 20px;">
                <div style="font-weight:700;color:#CC0000;">Feminist Group — DBU</div>
                <div style="font-size:.8rem;color:#6b7280;">Project Development Team</div>
              </div>
            </div>
          </div>

          <!-- Bottom Footer -->
          <div class="text-center mt-4"
               style="padding-top:16px;border-top:1px solid #f0f0f0;
                      font-size:.75rem;color:#9ca3af;letter-spacing:.5px;">
            Issued by DBU Blood Bank Management System &nbsp;|&nbsp;
            Debre Berhan University, Ethiopia &nbsp;|&nbsp;
            Created by Feminist Group
          </div>
        </div>

        <!-- Bottom Color Bar -->
        <div style="height:12px;background:linear-gradient(90deg,#F59E0B,#059669,#0891b2,#7c3aed,#CC0000,#003087);"></div>
      </div>
    </div>
  </div>

  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>
