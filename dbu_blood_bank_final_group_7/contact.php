<?php
/**
 * Contact Us — DBU Blood Bank
 * Optimized by: Mihret Alemayehu
 */
$page_title = 'Contact Us';
require_once 'config.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize input data
    $name    = sanitize($conn, $_POST['name']    ?? '');
    $email   = sanitize($conn, $_POST['email']   ?? '');
    $subject = sanitize($conn, $_POST['subject'] ?? '');
    $message = sanitize($conn, $_POST['message'] ?? '');

    // 2. Validation Logic
    if (!$name || !$email || !$subject || !$message) {
        $error = 'All fields are required.';
    } 
    // Validate name (letters and spaces only)
    elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = 'Full name should only contain letters and spaces.';
    }
    // Name must be at least 3 characters
    elseif (strlen($name) < 3) {
        $error = 'Full name must be at least 3 characters long.';
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // 3. Database Operation
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = 'Thank you for contacting us! We will respond within 24–48 hours.';
            // Clear post data after success
            $_POST = array();
        } else {
            $error = 'System error: Could not send message. Please try again.';
        }
        $stmt->close();
    }
}
require 'includes/head.php';
?>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="dbu-hero text-white py-5 text-center">
  <h1 class="fw-black display-5">📞 Contact Us</h1>
  <p style="color:rgba(255,255,255,.8);">Have a question? We'd love to hear from you.</p>
</div>

<div class="container py-5">
  <div class="row g-4 align-items-start">

    <div class="col-lg-7">
      <div class="card card-dbu p-4 p-md-5">
        <h5 class="fw-bold mb-4" style="color:#003087;">📬 Send Us a Message</h5>

        <?php if ($success): ?>
          <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Your Full Name</label>
              <input type="text" name="name" class="form-control"
                     placeholder="John Doe"
                     pattern="[a-zA-Z\s]+" title="Please enter only letters and spaces"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email Address</label>
              <input type="email" name="email" class="form-control"
                     placeholder="your@email.com"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Subject</label>
              <select name="subject" class="form-select" required>
                <option value="">Select a subject...</option>
                <option value="Blood Request" <?= (($_POST['subject'] ?? '') === 'Blood Request') ? 'selected' : '' ?>>🩸 Blood Request</option>
                <option value="Donor Registration" <?= (($_POST['subject'] ?? '') === 'Donor Registration') ? 'selected' : '' ?>>💉 Donor Registration</option>
                <option value="Blood Stock Inquiry" <?= (($_POST['subject'] ?? '') === 'Blood Stock Inquiry') ? 'selected' : '' ?>>🏥 Blood Stock Inquiry</option>
                <option value="Technical Support" <?= (($_POST['subject'] ?? '') === 'Technical Support') ? 'selected' : '' ?>>🛠 Technical Support</option>
                <option value="Partnership" <?= (($_POST['subject'] ?? '') === 'Partnership') ? 'selected' : '' ?>>🤝 Partnership / Collaboration</option>
                <option value="Other" <?= (($_POST['subject'] ?? '') === 'Other') ? 'selected' : '' ?>>📝 Other</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Your Message</label>
              <textarea name="message" class="form-control" rows="6"
                        placeholder="Type your message here..."
                        required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <div class="col-12 mt-2">
              <button type="submit" class="btn btn-dbu-red w-100 py-3 rounded-pill fw-bold fs-5">📨 Send Message</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card card-dbu p-4 mb-4">
        <h5 class="fw-bold mb-3" style="color:#003087;">📍 Find Us</h5>
        <ul class="list-unstyled mb-0">
          <?php $info = [
            ['📍','Address','DBU Health Center, Main Campus<br>Debre Berhan University<br>Debre Berhan, Ethiopia'],
            ['📞','Phone','+251 11 681 2345<br>+251 92 345 6789 (Emergency)'],
            ['✉️','Email','bloodbank@dbu.edu.et<br>info@dbu.edu.et'],
            ['🕐','Hours','Mon–Fri: 8:00 AM – 5:00 PM<br>Saturday: 9:00 AM – 1:00 PM<br>Sunday: Closed'],
          ]; foreach ($info as [$icon,$label,$val]): ?>
          <li class="d-flex gap-3 mb-3">
            <span style="font-size:1.3rem;flex-shrink:0;"><?= $icon ?></span>
            <div>
              <div class="fw-semibold small text-muted"><?= $label ?></div>
              <div><?= $val ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="card p-4 text-white" style="border-radius:16px;background:linear-gradient(135deg,#CC0000,#7a0000);border:none;">
        <div class="fs-2 mb-2">🆘</div>
        <h5 class="fw-bold">Emergency Blood Need?</h5>
        <p class="mb-3" style="color:rgba(255,255,255,.85);">For life-threatening situations requiring immediate blood, submit an emergency request or call us directly.</p>
        <a href="request.php" class="btn btn-light fw-bold rounded-pill px-4">Submit Urgent Request</a>
      </div>

      <div class="card card-dbu p-4 mt-4">
        <h6 class="fw-bold mb-2" style="color:#003087;">💬 Response Time</h6>
        <p class="text-muted small mb-0">We typically respond to messages within <strong>24–48 hours</strong> during business hours. For urgent blood requests, please call our emergency line directly.</p>
      </div>

    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
</body>
</html>