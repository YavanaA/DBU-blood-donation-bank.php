<?php
$page_title = 'Photo Gallery';
require_once 'config.php';

// Filter by donor (optional)
$donor_filter = sanitize($conn, $_GET['donor'] ?? '');
$where = '';
if ($donor_filter) $where = " AND donor_name LIKE '%$donor_filter%'";

$gallery = $conn->query("SELECT * FROM donation_photos WHERE 1=1$where ORDER BY created_at DESC");
$total   = $conn->query("SELECT COUNT(*) c FROM donation_photos")->fetch_assoc()['c'];
require 'includes/head.php';
?>
<style>
  .gallery-item { border-radius: 16px; overflow: hidden; box-shadow: 0 4px 18px rgba(0,48,135,.07); transition: .2s; cursor: pointer; }
  .gallery-item:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,48,135,.12); }
  .gallery-item img { width: 100%; height: 200px; object-fit: cover; display: block; }
  .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.88); display: none; align-items: center; justify-content: center; z-index: 9999; }
  .overlay.active { display: flex; }
  .overlay img { max-width: 90vw; max-height: 85vh; border-radius: 12px; }
</style>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="dbu-hero text-white py-5 text-center">
  <h1 class="fw-black display-5">🖼️ Photo Gallery</h1>
  <p style="color:rgba(255,255,255,.8);">Moments of compassion and community shared by DBU donors</p>
</div>

<div class="container py-5">

  <!-- HEADER ROW -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <span class="badge px-3 py-2" style="background:rgba(0,48,135,.1);color:#003087;font-size:.9rem;">
        <?= $total ?> total photo<?= $total!=1?'s':'' ?>
      </span>
    </div>
    <div class="d-flex gap-2">
      <form method="GET" class="d-flex gap-2">
        <input type="text" name="donor" class="form-control" style="max-width:200px;" placeholder="Search by donor..." value="<?= htmlspecialchars($donor_filter) ?>">
        <button type="submit" class="btn btn-dbu-blue rounded-pill px-3">Search</button>
        <?php if ($donor_filter): ?>
          <a href="gallery.php" class="btn btn-outline-secondary rounded-pill px-3">Clear</a>
        <?php endif; ?>
      </form>
      <?php if (is_logged_in()): ?>
        <a href="tips.php#upload" class="btn btn-dbu-red rounded-pill px-3">+ Upload</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline-danger rounded-pill px-3">Login to Upload</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($gallery->num_rows === 0): ?>
    <div class="text-center py-5 text-muted">
      <div style="font-size:70px;">📷</div>
      <h4 class="mt-3">No photos yet</h4>
      <p><?= $donor_filter ? 'No photos found for that donor.' : 'Be the first to share your donation moment!' ?></p>
      <a href="tips.php" class="btn btn-dbu-red rounded-pill px-4 mt-2">Upload Your Photo</a>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php while ($photo = $gallery->fetch_assoc()): ?>
      <div class="col-md-3 col-sm-4 col-6">
        <div class="gallery-item" onclick="openLightbox('<?= htmlspecialchars($photo['photo_path']) ?>')">
          <img src="<?= htmlspecialchars($photo['photo_path']) ?>"
               alt="Donation photo by <?= htmlspecialchars($photo['donor_name']) ?>"
               onerror="this.closest('.gallery-item').style.display='none'">
          <div class="p-3 bg-white">
            <div class="fw-semibold small">👤 <?= htmlspecialchars($photo['donor_name']) ?></div>
            <?php if ($photo['caption']): ?>
              <div class="text-muted mt-1" style="font-size:.8rem;">"<?= htmlspecialchars($photo['caption']) ?>"</div>
            <?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.72rem;">📅 <?= date('d M Y', strtotime($photo['created_at'])) ?></div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

  <!-- CALL TO ACTION -->
  <div class="text-center mt-5 p-4 rounded-4" style="background:linear-gradient(135deg,rgba(204,0,0,.05),rgba(0,48,135,.05));">
    <h4 class="fw-bold" style="color:#003087;">Share Your Donation Story!</h4>
    <p class="text-muted">Inspire others by uploading photos from your donation experience.</p>
    <?php if (is_logged_in()): ?>
      <a href="tips.php" class="btn btn-dbu-red rounded-pill px-4">Upload Your Photos</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-dbu-blue rounded-pill px-4 me-2">Login</a>
      <a href="register.php" class="btn btn-dbu-red rounded-pill px-4">Register</a>
    <?php endif; ?>
  </div>

</div>

<!-- LIGHTBOX OVERLAY -->
<div class="overlay" id="lightbox" onclick="closeLightbox()">
  <img id="lightbox-img" src="" alt="Photo">
  <button onclick="closeLightbox()" style="position:absolute;top:20px;right:24px;background:none;border:none;color:#fff;font-size:36px;cursor:pointer;">✕</button>
</div>

<script>
function openLightbox(src) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox').classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('active');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeLightbox(); });
</script>

<?php require 'includes/footer.php'; ?>
</body>
</html>
