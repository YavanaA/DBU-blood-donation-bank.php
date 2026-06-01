<?php
$current = basename($_SERVER['PHP_SELF']);
function nav_active($page) {
    global $current;
    return $current === $page ? 'active' : '';
}
$nav_logo_url = '';
if (isset($conn)) {
    $logo_res = $conn->query("SELECT setting_value FROM system_settings WHERE setting_name='logo_url'");
    if ($logo_res && $logo_row = $logo_res->fetch_assoc()) {
        $nav_logo_url = $logo_row['setting_value'] ?? '';
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top"
     style="background:linear-gradient(135deg,#003087 0%,#1e1b5e 40%,#4c1d95 70%,#1e3a6e 100%);
            box-shadow:0 4px 20px rgba(0,0,0,.4);">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2 py-2" href="index.php">
      <?php if ($nav_logo_url): ?>
        <img src="<?= htmlspecialchars($nav_logo_url) ?>" alt="DBU Logo"
             style="width:42px;height:42px;border-radius:50%;object-fit:cover;
                    border:2px solid #F59E0B;flex-shrink:0;
                    box-shadow:0 0 0 3px rgba(245,158,11,.3);">
      <?php else: ?>
        <div style="width:42px;height:42px;border-radius:50%;flex-shrink:0;
                    background:linear-gradient(135deg,#CC0000,#7c3aed);
                    border:2px solid #F59E0B;
                    box-shadow:0 0 0 3px rgba(245,158,11,.3);
                    display:flex;align-items:center;justify-content:center;
                    font-size:11px;font-weight:900;color:#fff;letter-spacing:-.5px;">
          DBU
        </div>
      <?php endif; ?>
      <div class="lh-sm">
        <div class="fw-bold text-white" style="font-size:15px;letter-spacing:.2px;">
          DBU <span style="color:#F59E0B;">Blood</span>Bank
        </div>
        <div style="font-size:9px;letter-spacing:1.5px;text-transform:uppercase;
                    color:rgba(255,255,255,.6);">Debre Berhan University</div>
      </div>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

        <li class="nav-item">
          <a class="nav-link <?= nav_active('index.php') ?>" href="index.php">🏠 Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= nav_active('about.php') ?>" href="about.php">ℹ️ About</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">🩸 Blood Services</a>
          <ul class="dropdown-menu border-0 shadow-lg py-2"
              style="background:linear-gradient(160deg,#1e1b5e,#4c1d95);border-radius:14px;min-width:200px;">
            <li><a class="dropdown-item text-white-50 fw-bold" style="font-size:.72rem;letter-spacing:1px;pointer-events:none;">SERVICES</a></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('search.php') ?>" href="search.php"
                   style="border-radius:8px;">🔍 Find a Donor</a></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('request.php') ?>" href="request.php"
                   style="border-radius:8px;">📋 Request Blood</a></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('blood_stock.php') ?>" href="blood_stock.php"
                   style="border-radius:8px;">🏥 Blood Stock</a></li>
            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.15);"></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('hospital_login.php') ?>" href="hospital_login.php"
                   style="border-radius:8px;">🏨 Hospital Portal</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">📚 Resources</a>
          <ul class="dropdown-menu border-0 shadow-lg py-2"
              style="background:linear-gradient(160deg,#064e3b,#059669);border-radius:14px;min-width:200px;">
            <li><a class="dropdown-item text-white-50 fw-bold" style="font-size:.72rem;letter-spacing:1px;pointer-events:none;">LEARN</a></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('tips.php') ?>" href="tips.php"
                   style="border-radius:8px;">💡 Tips & Gallery</a></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('eligibility.php') ?>" href="eligibility.php"
                   style="border-radius:8px;">✅ Eligibility Check</a></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('gallery.php') ?>" href="gallery.php"
                   style="border-radius:8px;">🖼️ Photo Gallery</a></li>
            <li><a class="dropdown-item text-white py-2 <?= nav_active('certificate.php') ?>" href="certificate.php"
                   style="border-radius:8px;">🏅 My Certificate</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= nav_active('contact.php') ?>" href="contact.php">📞 Contact</a>
        </li>

        <?php if (is_admin()): ?>
          <li class="nav-item">
            <a class="nav-link fw-bold" style="color:#F59E0B;" href="admin_panel.php">🛡️ Admin</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm px-3 ms-2 nav-link fw-bold"
               style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border-radius:20px;"
               href="logout.php">Logout</a>
          </li>
        <?php elseif (is_hospital()): ?>
          <li class="nav-item">
            <a class="nav-link <?= nav_active('hospital_dashboard.php') ?>" href="hospital_dashboard.php">🏥 My Dashboard</a>
          </li>
          <li class="nav-item">
            <span class="nav-link text-warning fw-semibold" style="font-size:.8rem;">
              🏨 <?= htmlspecialchars($_SESSION['hospital_name'] ?? '') ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm px-3 ms-2 nav-link fw-bold"
               style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border-radius:20px;"
               href="logout.php">Logout</a>
          </li>
        <?php elseif (is_logged_in()): ?>
          <li class="nav-item">
            <a class="nav-link <?= nav_active('dashboard.php') ?>" href="dashboard.php">👤 Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= nav_active('certificate.php') ?>" href="certificate.php">🏅 Certificate</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm px-3 ms-2 nav-link"
               style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border-radius:20px;"
               href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= nav_active('login.php') ?>" href="login.php">🔑 Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-sm px-3 ms-2 fw-bold"
               style="background:linear-gradient(135deg,#F59E0B,#ea580c);color:#fff;border-radius:20px;border:none;"
               href="register.php">✚ Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
