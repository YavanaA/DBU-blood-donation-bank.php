<?php
/**
 * Notifications Log — DBU Blood Bank
 * Tasks 3, 4, 5: View simulated Gmail alert history
 */
$page_title = 'Notification Logs';
require_once 'config.php';
require_admin();

// ── Task 5: Trigger non-donor reminders via PHPMailer SMTP ───
$reminder_result = null;
if (isset($_POST['send_reminders'])) {
    $reminder_result = $db->sendNonDonorReminders();
}

// ── Fetch all notifications ───────────────────────────────────
$type_filter = sanitize($conn, $_GET['type'] ?? '');
$where_type  = $type_filter ? "WHERE nl.type = '$type_filter'" : '';

$logs_res = $conn->query("
    SELECT nl.*, d.name AS donor_name, d.blood_type
    FROM notifications_log nl
    LEFT JOIN donors d ON d.id = nl.donor_id
    $where_type
    ORDER BY nl.sent_at DESC
    LIMIT 100
");
$logs = $logs_res ? $logs_res->fetch_all(MYSQLI_ASSOC) : [];

// ── Stats ─────────────────────────────────────────────────────
$total_logs    = $conn->query("SELECT COUNT(*) c FROM notifications_log")->fetch_assoc()['c'];
$health_alerts = $conn->query("SELECT COUNT(*) c FROM notifications_log WHERE type='health_alert'")->fetch_assoc()['c'];
$reminders     = $conn->query("SELECT COUNT(*) c FROM notifications_log WHERE type='reminder'")->fetch_assoc()['c'];
$verifications = $conn->query("SELECT COUNT(*) c FROM notifications_log WHERE type='blood_type_verified'")->fetch_assoc()['c'];

// ── Non-donor count ───────────────────────────────────────────
$non_donors = $db->getNonDonors();

require 'includes/head.php';
?>
</head>
<body>
<nav class="navbar navbar-dark px-4 py-2"
     style="background:linear-gradient(135deg,#003087 0%,#1e1b5e 50%,#4c1d95 100%);
            box-shadow:0 4px 20px rgba(0,0,0,.35);">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#CC0000,#7c3aed);
           border:2px solid #F59E0B;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;color:#fff;">DBU</div>
      <span class="fw-bold">DBU <span style="color:#F59E0B;">Blood</span>Bank
        <small style="color:rgba(255,255,255,.6);font-size:11px;display:block;line-height:1;">Notification Logs</small>
      </span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a href="admin_panel.php" class="btn btn-sm rounded-pill" style="background:rgba(255,255,255,.1);color:#fff;border:none;">← Admin Panel</a>
      <a href="logout.php" class="btn btn-sm rounded-pill fw-semibold" style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;border:none;">Logout</a>
    </div>
  </div>
</nav>

<div style="background:linear-gradient(135deg,#003087,#1e1b5e,#4c1d95);padding:30px;">
  <div class="container">
    <h2 class="text-white fw-black mb-1">📧 Simulated Notification Log</h2>
    <p style="color:rgba(255,255,255,.7);">All automated alerts dispatched by the system (Tasks 3, 4 &amp; 5)</p>
  </div>
</div>

<div class="container py-4">

  <?php if ($reminder_result !== null): ?>
  <?php $r_total = $reminder_result['total']; $r_sent = $reminder_result['emailed']; ?>
  <div class="alert border-0 rounded-3 mb-4" style="background:<?= $r_total > 0 ? '#f0fdf4' : '#fffbeb' ?>;border-left:4px solid <?= $r_total > 0 ? '#059669' : '#d97706' ?>!important;">
    <?php if ($r_total === 0): ?>
      🎉 <strong>No action needed.</strong> All registered donors have at least one donation or test record.
    <?php else: ?>
      📧 <strong>Reminders dispatched to <?= $r_total ?> non-donor(s).</strong>
      <?php if ($r_sent > 0): ?>
        <span class="badge bg-success ms-1">✓ <?= $r_sent ?> Email<?= $r_sent > 1 ? 's' : '' ?> Sent via Gmail SMTP</span>
      <?php else: ?>
        <span class="badge bg-secondary ms-1">📋 Logged Only — Enable SMTP in mail_config.php to send real emails</span>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Stats Row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 p-3 text-center" style="background:linear-gradient(135deg,#003087,#4338ca);color:#fff;">
        <div class="fw-black fs-3"><?= $total_logs ?></div>
        <div class="small opacity-75">Total Alerts</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 p-3 text-center" style="background:linear-gradient(135deg,#CC0000,#e11d48);color:#fff;">
        <div class="fw-black fs-3"><?= $health_alerts ?></div>
        <div class="small opacity-75">Health Alerts (Task 4)</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 p-3 text-center" style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;">
        <div class="fw-black fs-3"><?= $reminders ?></div>
        <div class="small opacity-75">Donor Reminders (Task 5)</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm rounded-4 p-3 text-center" style="background:linear-gradient(135deg,#059669,#0891b2);color:#fff;">
        <div class="fw-black fs-3"><?= $verifications ?></div>
        <div class="small opacity-75">Blood Type Verifications (Task 3)</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- Task 5: Non-Donor Reminder Panel -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">⏰ Task 5 — Non-Donor Reminders</h5>
        <p class="text-muted small mb-3">
          Scans for registered donors who have never donated. Dispatches automated reminder notifications to encourage their first donation.
        </p>

        <?php if (!empty($non_donors)): ?>
        <div class="mb-3 rounded-3 p-3" style="background:#fffbeb;border:1px solid #fde68a;">
          <div class="fw-semibold mb-2" style="color:#92400e;">⚠ <?= count($non_donors) ?> Non-Donor(s) Found:</div>
          <ul class="list-unstyled mb-0 small">
            <?php foreach (array_slice($non_donors, 0, 8) as $nd): ?>
            <li class="mb-1">
              <span class="fw-semibold"><?= htmlspecialchars($nd['name']) ?></span>
              <span class="text-muted"> — <?= htmlspecialchars($nd['email']) ?></span>
            </li>
            <?php endforeach; ?>
            <?php if (count($non_donors) > 8): ?>
            <li class="text-muted">... and <?= count($non_donors) - 8 ?> more</li>
            <?php endif; ?>
          </ul>
        </div>

        <form method="POST">
          <input type="hidden" name="send_reminders" value="1">
          <button type="submit" class="btn w-100 fw-bold rounded-pill"
                  style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;"
                  onclick="return confirm('Send reminder notifications to <?= count($non_donors) ?> non-donor(s)?');">
            📧 Send Reminders Now (<?= count($non_donors) ?>)
          </button>
        </form>
        <?php else: ?>
        <div class="text-center py-3 text-muted">
          <div style="font-size:2.5rem;">🎉</div>
          <p class="small">All registered donors have at least one donation or test record. No reminders needed!</p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Notification Log Table -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-4 p-4">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <h5 class="fw-bold mb-0" style="color:#003087;">📋 Alert History (Last 100)</h5>
          <div class="d-flex gap-2 flex-wrap">
            <a href="notifications_log.php" class="btn btn-sm rounded-pill <?= !$type_filter ? 'btn-dark' : 'btn-outline-secondary' ?>">All</a>
            <a href="?type=health_alert" class="btn btn-sm rounded-pill <?= $type_filter === 'health_alert' ? 'btn-danger' : 'btn-outline-danger' ?>">Health Alerts</a>
            <a href="?type=blood_type_verified" class="btn btn-sm rounded-pill <?= $type_filter === 'blood_type_verified' ? 'btn-success' : 'btn-outline-success' ?>">Verifications</a>
            <a href="?type=reminder" class="btn btn-sm rounded-pill <?= $type_filter === 'reminder' ? 'btn-warning' : 'btn-outline-warning' ?>">Reminders</a>
          </div>
        </div>

        <?php if (empty($logs)): ?>
        <div class="text-center text-muted py-5">
          <div style="font-size:3rem;">📭</div>
          <p>No notifications logged yet.</p>
          <small>Alerts appear here when blood types are verified, lab results are recorded, or reminders are sent.</small>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr style="background:linear-gradient(135deg,#003087,#4c1d95);color:#fff;">
                <th>Recipient</th>
                <th>Type</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Sent</th>
                <th>Preview</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
              <?php
              $type_badges = [
                'health_alert'       => ['bg-danger',  '⚠ Health Alert'],
                'health_clear'       => ['bg-success', '✓ Health Clear'],
                'blood_type_verified'=> ['bg-primary', '🩸 Blood Verified'],
                'reminder'           => ['bg-warning text-dark', '⏰ Reminder'],
                'general'            => ['bg-secondary','📧 General'],
              ];
              [$badge_cls, $badge_label] = $type_badges[$log['type']] ?? ['bg-secondary', $log['type']];
              ?>
              <tr>
                <td>
                  <div class="fw-semibold small"><?= htmlspecialchars($log['donor_name'] ?? '—') ?></div>
                  <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($log['recipient']) ?></div>
                </td>
                <td><span class="badge <?= $badge_cls ?>"><?= $badge_label ?></span></td>
                <td>
                  <?php if (!empty($log['email_sent'])): ?>
                    <span class="badge bg-success-subtle text-success border border-success" style="font-size:.7rem;">✓ Sent</span>
                  <?php else: ?>
                    <span class="badge bg-light text-secondary border" style="font-size:.7rem;">📋 Logged</span>
                  <?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars(substr($log['subject'], 0, 40)) ?><?= strlen($log['subject']) > 40 ? '…' : '' ?></td>
                <td class="small text-muted"><?= date('d M Y H:i', strtotime($log['sent_at'])) ?></td>
                <td>
                  <button class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:.75rem;"
                          data-bs-toggle="modal" data-bs-target="#modal_<?= $log['id'] ?>">
                    View
                  </button>
                  <!-- Modal -->
                  <div class="modal fade" id="modal_<?= $log['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0" style="background:linear-gradient(135deg,#003087,#4c1d95);">
                          <h5 class="modal-title text-white fw-bold small"><?= htmlspecialchars($log['subject']) ?></h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-2"><strong>To:</strong> <?= htmlspecialchars($log['recipient']) ?></div>
                          <div class="mb-2"><strong>Date:</strong> <?= date('d M Y H:i', strtotime($log['sent_at'])) ?></div>
                          <hr>
                          <pre class="small" style="white-space:pre-wrap;font-family:inherit;"><?= htmlspecialchars($log['body']) ?></pre>
                        </div>
                      </div>
                    </div>
                  </div>
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

<?php require 'includes/footer.php'; ?>
</body>
</html>
