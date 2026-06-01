<?php
$page_title = 'PDF Report';
require_once 'config.php';
require_admin();

// Gather data
$total_donors   = $conn->query("SELECT COUNT(*) c FROM donors")->fetch_assoc()['c'];
$active_donors  = $conn->query("SELECT COUNT(*) c FROM donors WHERE is_active=1")->fetch_assoc()['c'];
$total_requests = $conn->query("SELECT COUNT(*) c FROM blood_requests")->fetch_assoc()['c'];
$pending_req    = $conn->query("SELECT COUNT(*) c FROM blood_requests WHERE status='pending'")->fetch_assoc()['c'];
$approved_req   = $conn->query("SELECT COUNT(*) c FROM blood_requests WHERE status='approved'")->fetch_assoc()['c'];
$new_msgs       = $conn->query("SELECT COUNT(*) c FROM contact_messages WHERE status='new'")->fetch_assoc()['c'];

$donors_list = $conn->query("SELECT name, email, blood_type, phone, dbu_id, is_active, created_at FROM donors ORDER BY created_at DESC LIMIT 50");
$requests    = $conn->query("SELECT patient_name, blood_type, hospital, requester_name, urgency, status, created_at FROM blood_requests ORDER BY created_at DESC LIMIT 30");
$stock_list  = $conn->query("SELECT blood_type, units, status, updated_at FROM blood_stock ORDER BY blood_type");

$report_date = date('d F Y, H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DBU Blood Bank — PDF Report</title>
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family:'Segoe UI', Arial, sans-serif; background:#fff; color:#111; }
  .no-print { }
  @media print {
    .no-print { display:none !important; }
    body { font-size:11pt; }
    .page-break { page-break-before: always; }
    table { font-size:9pt; }
    h1,h2,h3 { color:#003087 !important; }
  }
  .report-header { background:linear-gradient(135deg,#003087,#4c1d95,#CC0000); color:#fff; padding:40px; }
  .stat-box { border:2px solid; border-radius:12px; padding:16px; text-align:center; }
  .st-blue   { border-color:#003087; color:#003087; }
  .st-green  { border-color:#059669; color:#059669; }
  .st-red    { border-color:#CC0000; color:#CC0000; }
  .st-amber  { border-color:#d97706; color:#d97706; }
  .section-head { background:#003087; color:#fff; padding:8px 16px; border-radius:8px; font-weight:700; margin:24px 0 12px; font-size:.95rem; }
  table { width:100%; border-collapse:collapse; }
  th { background:#003087; color:#fff; padding:8px 10px; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; }
  td { padding:7px 10px; border-bottom:1px solid #e5e7eb; font-size:.82rem; }
  tr:nth-child(even) td { background:#f8faff; }
  .badge-active { background:#dcfce7; color:#059669; padding:2px 8px; border-radius:10px; font-weight:600; font-size:.75rem; }
  .badge-inactive { background:#fef2f2; color:#CC0000; padding:2px 8px; border-radius:10px; font-weight:600; font-size:.75rem; }
  .badge-pending  { background:#fef3c7; color:#d97706; padding:2px 8px; border-radius:10px; font-weight:600; font-size:.75rem; }
  .badge-approved { background:#dcfce7; color:#059669; padding:2px 8px; border-radius:10px; font-weight:600; font-size:.75rem; }
  .badge-urgent   { background:#fee2e2; color:#CC0000; padding:2px 8px; border-radius:10px; font-weight:600; font-size:.75rem; }
  .badge-medium   { background:#fef3c7; color:#d97706; padding:2px 8px; border-radius:10px; font-weight:600; font-size:.75rem; }
  .badge-normal   { background:#dcfce7; color:#059669; padding:2px 8px; border-radius:10px; font-weight:600; font-size:.75rem; }
</style>
</head>
<body>

<!-- Toolbar (hidden on print) -->
<div class="no-print p-3 d-flex gap-2 align-items-center" style="background:#f1f5f9;border-bottom:2px solid #e2e8f0;">
  <button onclick="window.print()" class="btn fw-bold px-4 rounded-pill"
          style="background:linear-gradient(135deg,#003087,#7c3aed);color:#fff;border:none;">
    🖨️ Print / Save as PDF
  </button>
  <a href="admin_panel.php" class="btn rounded-pill px-4" style="background:#e2e8f0;color:#374151;">← Back to Admin Panel</a>
  <span class="text-muted small ms-auto">Report generated: <?= $report_date ?></span>
</div>

<!-- Report Header -->
<div class="report-header">
  <div style="display:flex;align-items:center;gap:20px;">
    <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.15);
                border:3px solid #F59E0B;display:flex;align-items:center;justify-content:center;
                font-size:16px;font-weight:900;color:#fff;flex-shrink:0;">DBU</div>
    <div>
      <div style="font-size:1.6rem;font-weight:900;letter-spacing:.5px;">DBU Blood Bank Management System</div>
      <div style="opacity:.8;font-size:.9rem;margin-top:2px;">Debre Berhan University &mdash; Official Administrative Report</div>
      <div style="opacity:.6;font-size:.78rem;margin-top:4px;">Generated: <?= $report_date ?> </div>
    </div>
  </div>
</div>

<div style="padding:32px 40px;">

  <!-- Summary Stats -->
  <div class="section-head">📊 Summary Statistics</div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="stat-box st-blue">
      <div style="font-size:2rem;font-weight:900;"><?= $total_donors ?></div>
      <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total Donors</div>
    </div>
    <div class="stat-box st-green">
      <div style="font-size:2rem;font-weight:900;"><?= $active_donors ?></div>
      <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Active Donors</div>
    </div>
    <div class="stat-box st-red">
      <div style="font-size:2rem;font-weight:900;"><?= $total_requests ?></div>
      <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Total Requests</div>
    </div>
    <div class="stat-box st-amber">
      <div style="font-size:2rem;font-weight:900;"><?= $pending_req ?></div>
      <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Pending Requests</div>
    </div>
    <div class="stat-box st-green">
      <div style="font-size:2rem;font-weight:900;"><?= $approved_req ?></div>
      <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Approved Requests</div>
    </div>
    <div class="stat-box st-blue">
      <div style="font-size:2rem;font-weight:900;"><?= $new_msgs ?></div>
      <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">New Messages</div>
    </div>
  </div>

  <!-- Blood Stock -->
  <div class="section-head">🏥 Current Blood Stock</div>
  <table>
    <thead><tr><th>Blood Type</th><th>Units Available</th><th>Status</th><th>Last Updated</th></tr></thead>
    <tbody>
      <?php while ($s = $stock_list->fetch_assoc()):
        $sc = ['available'=>'badge-active','low'=>'badge-medium','critical'=>'badge-urgent','unavailable'=>'badge-inactive'];
      ?>
      <tr>
        <td><strong><?= $s['blood_type'] ?></strong></td>
        <td><?= $s['units'] ?> units</td>
        <td><span class="<?= $sc[$s['status']] ?? 'badge-inactive' ?>"><?= ucfirst($s['status']) ?></span></td>
        <td><?= date('d M Y H:i', strtotime($s['updated_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Donors List -->
  <div class="section-head page-break">👥 Registered Donors (Last 50)</div>
  <table>
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Blood Type</th><th>Phone</th><th>DBU ID</th><th>Status</th><th>Joined</th></tr></thead>
    <tbody>
      <?php $i = 1; while ($d = $donors_list->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
        <td><?= htmlspecialchars($d['email']) ?></td>
        <td style="color:#CC0000;font-weight:700;"><?= $d['blood_type'] ?></td>
        <td><?= htmlspecialchars($d['phone']) ?></td>
        <td><?= htmlspecialchars($d['dbu_id']) ?></td>
        <td><span class="<?= $d['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $d['is_active'] ? 'Active' : 'Inactive' ?></span></td>
        <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Blood Requests -->
  <div class="section-head page-break">🩸 Blood Requests (Last 30)</div>
  <table>
    <thead><tr><th>#</th><th>Patient</th><th>Blood</th><th>Hospital</th><th>Requester</th><th>Urgency</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
      <?php $i = 1; while ($r = $requests->fetch_assoc()): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
        <td style="color:#CC0000;font-weight:700;"><?= $r['blood_type'] ?></td>
        <td><?= htmlspecialchars($r['hospital']) ?></td>
        <td><?= htmlspecialchars($r['requester_name']) ?></td>
        <td><span class="badge-<?= $r['urgency'] ?>"><?= ucfirst($r['urgency']) ?></span></td>
        <td><span class="<?= $r['status']==='approved'?'badge-approved':'badge-pending' ?>"><?= ucfirst($r['status']) ?></span></td>
        <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- Report Footer -->
  <div style="margin-top:40px;padding-top:20px;border-top:2px solid #e5e7eb;
              text-align:center;color:#6b7280;font-size:.78rem;">
    <p style="margin:0;">DBU Blood Bank Management System &mdash; Debre Berhan University, Ethiopia</p>
   
    <p style="margin:4px 0 0;">Report Date: <?= $report_date ?></p>
  </div>

</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
