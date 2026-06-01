<?php
$current_page_name = basename($_SERVER['PHP_SELF']);
?>
<footer class="text-white py-5 mt-5"
        style="background:linear-gradient(160deg,#0d1b2a 0%,#1a0550 40%,#0d1b2a 100%);">
  <div class="container">
    <div class="row g-4">

      <div class="col-md-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php
          $footer_logo = '';
          if (isset($conn)) {
              $logo_row = $conn->query("SELECT setting_value FROM system_settings WHERE setting_name='logo_url'")->fetch_assoc();
              $footer_logo = $logo_row['setting_value'] ?? '';
          }
          if ($footer_logo): ?>
            <img src="<?= htmlspecialchars($footer_logo) ?>" alt="DBU Logo"
                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;
                        border:2px solid #F59E0B;flex-shrink:0;
                        box-shadow:0 0 0 3px rgba(245,158,11,.25);">
          <?php else: ?>
            <div style="width:48px;height:48px;border-radius:50%;flex-shrink:0;
                        background:linear-gradient(135deg,#CC0000,#7c3aed);
                        border:2px solid #F59E0B;
                        box-shadow:0 0 0 3px rgba(245,158,11,.25);
                        display:flex;align-items:center;justify-content:center;
                        font-size:12px;font-weight:900;color:#fff;">DBU</div>
          <?php endif; ?>
          <div>
            <div class="fw-bold" style="font-size:16px;">
              DBU <span style="color:#F59E0B;">Blood</span>Bank
            </div>
            <div style="font-size:10px;color:rgba(255,255,255,.5);letter-spacing:1px;">Debre Berhan University</div>
          </div>
        </div>
        <p style="color:#9ca3af;font-size:.85rem;line-height:1.7;">
          Saving lives through voluntary blood donation at Debre Berhan University. Every drop counts.
        </p>
        <div class="d-flex gap-1 mt-2">
          <div style="height:4px;width:28px;border-radius:4px;background:#CC0000;"></div>
          <div style="height:4px;width:18px;border-radius:4px;background:#7c3aed;"></div>
          <div style="height:4px;width:12px;border-radius:4px;background:#059669;"></div>
          <div style="height:4px;width:8px;border-radius:4px;background:#F59E0B;"></div>
        </div>
      </div>

      <div class="col-md-2">
        <h6 class="fw-bold mb-3" style="font-size:.78rem;letter-spacing:1.5px;text-transform:uppercase;
             background:linear-gradient(90deg,#F59E0B,#ea580c);-webkit-background-clip:text;
             -webkit-text-fill-color:transparent;background-clip:text;">Quick Links</h6>
        <ul class="list-unstyled" style="font-size:.85rem;">
          <li class="mb-2"><a href="index.php" class="text-decoration-none"
              style="color:#9ca3af;transition:.15s;" onmouseover="this.style.color='#F59E0B'"
              onmouseout="this.style.color='#9ca3af'">🏠 Home</a></li>
          <li class="mb-2"><a href="about.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#F59E0B'"
              onmouseout="this.style.color='#9ca3af'">ℹ️ About</a></li>
          <li class="mb-2"><a href="search.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#F59E0B'"
              onmouseout="this.style.color='#9ca3af'">🔍 Find Donor</a></li>
          <li class="mb-2"><a href="request.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#F59E0B'"
              onmouseout="this.style.color='#9ca3af'">📋 Request Blood</a></li>
          <li class="mb-2"><a href="contact.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#F59E0B'"
              onmouseout="this.style.color='#9ca3af'">📞 Contact</a></li>
          <li class="mb-2"><a href="hospital_login.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#F59E0B'"
              onmouseout="this.style.color='#9ca3af'">🏥 Hospital Portal</a></li>
        </ul>
      </div>

      <div class="col-md-2">
        <h6 class="fw-bold mb-3" style="font-size:.78rem;letter-spacing:1.5px;text-transform:uppercase;
             background:linear-gradient(90deg,#10b981,#06b6d4);-webkit-background-clip:text;
             -webkit-text-fill-color:transparent;background-clip:text;">Resources</h6>
        <ul class="list-unstyled" style="font-size:.85rem;">
          <li class="mb-2"><a href="tips.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#10b981'"
              onmouseout="this.style.color='#9ca3af'">💡 Tips & Gallery</a></li>
          <li class="mb-2"><a href="eligibility.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#10b981'"
              onmouseout="this.style.color='#9ca3af'">✅ Eligibility</a></li>
          <li class="mb-2"><a href="blood_stock.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#10b981'"
              onmouseout="this.style.color='#9ca3af'">🏥 Blood Stock</a></li>
          <li class="mb-2"><a href="gallery.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#10b981'"
              onmouseout="this.style.color='#9ca3af'">🖼️ Gallery</a></li>
          <li class="mb-2"><a href="certificate.php" class="text-decoration-none"
              style="color:#9ca3af;" onmouseover="this.style.color='#10b981'"
              onmouseout="this.style.color='#9ca3af'">🏅 My Certificate</a></li>
        </ul>
      </div>

      <div class="col-md-4">
        <h6 class="fw-bold mb-3" style="font-size:.78rem;letter-spacing:1.5px;text-transform:uppercase;
             background:linear-gradient(90deg,#e11d48,#7c3aed);-webkit-background-clip:text;
             -webkit-text-fill-color:transparent;background-clip:text;">Emergency Contact</h6>
        <div style="background:rgba(255,255,255,.06);border-radius:12px;padding:16px;
                    border:1px solid rgba(255,255,255,.08);">
          <p style="color:#9ca3af;font-size:.85rem;line-height:2;margin:0;">
            📍 Debre Berhan University Health Center<br>
            📞 <strong style="color:#F59E0B;">+251 11 681 2345</strong><br>
            ✉️ bloodbank@dbu.edu.et<br>
            🕐 Mon–Fri: 8AM – 5PM
          </p>
        </div>
      </div>
    </div>

    <div style="height:2px;border-radius:2px;margin:32px 0 24px;
         background:linear-gradient(90deg,#CC0000,#7c3aed,#059669,#F59E0B,#0891b2);"></div>

    <div class="text-center" style="color:#6b7280;font-size:.82rem;">
      <p class="mb-1">
        &copy; <?= date('Y') ?>
        <strong class="text-white">DBU Blood Donation Management System</strong>
      </p>
      
      <p class="mb-0" style="color:#e11d48;font-weight:700;font-size:.88rem;letter-spacing:.5px;">
        Created by Feminist Group &mdash; Debre Berhan University
      </p>
    </div>
  </div>
</footer>
<script src="assets/js/bootstrap.bundle.min.js"></script>
