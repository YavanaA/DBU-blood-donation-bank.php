<?php
$page_title = 'About Us';
require_once 'config.php';
require 'includes/head.php';
?>
<style>
  .mission-card {
    border-radius: 18px;
    border: none;
    background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
    overflow: hidden;
    position: relative;
    transition: .25s;
  }
  .mission-card:hover { transform: translateY(-5px); box-shadow: 0 12px 36px rgba(0,0,0,.1); }
  .mission-card .card-top { height: 6px; width: 100%; }

  .timeline-item { position: relative; padding-left: 54px; padding-bottom: 32px; }
  .timeline-item::before { content:''; position:absolute; left:18px; top:0; bottom:0; width:2px;
    background: linear-gradient(180deg,#CC0000,#7c3aed,#059669,#0891b2); }
  .timeline-dot {
    position: absolute; left: 4px; top: 4px;
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 11px; font-weight: 800;
  }

  .value-icon { width: 64px; height: 64px; border-radius: 16px; display: flex;
    align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 14px; }

  .team-section {
    border-radius: 24px;
    padding: 48px 32px;
    background: linear-gradient(160deg,#f5f3ff 0%,#ede9fe 40%,#fce7f3 100%);
    position: relative; overflow: hidden;
  }
  .team-section::before {
    content:'';
    position:absolute; top:-80px; right:-80px;
    width:200px; height:200px; border-radius:50%;
    background:linear-gradient(135deg,rgba(124,58,237,.1),rgba(219,39,119,.06));
    pointer-events:none;
  }
  .team-member-photo {
    width: 120px; height: 120px;
    border-radius: 50%; object-fit: cover;
    display: block; margin: 0 auto 12px;
    transition: .25s;
    background: linear-gradient(135deg,#e5e7eb,#f3f4f6);
  }
  .team-member-photo:hover { transform: scale(1.07); }
  .team-member-name { font-weight: 800; font-size: .92rem; margin-bottom: 2px; }
  .team-member-role { font-size: .76rem; color: #6b7280; }

  .mem1 { border: 4px solid #CC0000; box-shadow: 0 4px 16px rgba(204,0,0,.25); }
  .mem2 { border: 4px solid #7c3aed; box-shadow: 0 4px 16px rgba(124,58,237,.25); }
  .mem3 { border: 4px solid #059669; box-shadow: 0 4px 16px rgba(5,150,105,.25); }
  .mem4 { border: 4px solid #0891b2; box-shadow: 0 4px 16px rgba(8,145,178,.25); }
  .mem5 { border: 4px solid #d97706; box-shadow: 0 4px 16px rgba(217,119,6,.25); }
  .mem1-name { color: #CC0000; }
  .mem2-name { color: #7c3aed; }
  .mem3-name { color: #059669; }
  .mem4-name { color: #0891b2; }
  .mem5-name { color: #d97706; }

  .member-avatar {
    width:120px;height:120px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:36px;font-weight:900;color:#fff;
    margin:0 auto 12px;
  }
</style>
</head>
<body>
<?php require 'includes/nav.php'; ?>

<div class="text-white py-5 text-center" style="position:relative;overflow:hidden;
     background:linear-gradient(135deg,#003087 0%,#4c1d95 50%,#CC0000 100%);">
  <div style="position:absolute;inset:0;
       background:radial-gradient(ellipse at 50% 50%,rgba(245,158,11,.1) 0%,transparent 70%);
       pointer-events:none;"></div>
  <div style="position:relative;">
    <div style="font-size:52px;margin-bottom:12px;">🏥</div>
    <h1 class="fw-black display-5">About DBU Blood Bank</h1>
    <p style="color:rgba(255,255,255,.8);max-width:560px;margin:8px auto 0;">
      Our mission is to save lives through voluntary blood donation at Debre Berhan University.
    </p>
  </div>
</div>

<div class="container py-5">

  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="mission-card h-100 p-4">
        <div class="card-top" style="background:linear-gradient(90deg,#CC0000,#e11d48);"></div>
        <div style="width:52px;height:52px;border-radius:14px;
             background:linear-gradient(135deg,#CC0000,#e11d48);
             display:flex;align-items:center;justify-content:center;
             font-size:22px;margin:16px 0 12px;">🎯</div>
        <h5 class="fw-bold" style="color:#CC0000;">Our Mission</h5>
        <p class="text-muted small">To facilitate a safe and efficient voluntary blood donation system within Debre Berhan University, ensuring a steady supply of blood for medical emergencies across the university and surrounding community.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mission-card h-100 p-4">
        <div class="card-top" style="background:linear-gradient(90deg,#4338ca,#7c3aed);"></div>
        <div style="width:52px;height:52px;border-radius:14px;
             background:linear-gradient(135deg,#4338ca,#7c3aed);
             display:flex;align-items:center;justify-content:center;
             font-size:22px;margin:16px 0 12px;">🌟</div>
        <h5 class="fw-bold" style="color:#4338ca;">Our Vision</h5>
        <p class="text-muted small">To become a model blood donation program in Ethiopian universities, fostering a culture of compassion, health awareness, and community solidarity among DBU students and staff.</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="mission-card h-100 p-4">
        <div class="card-top" style="background:linear-gradient(90deg,#d97706,#F59E0B);"></div>
        <div style="width:52px;height:52px;border-radius:14px;
             background:linear-gradient(135deg,#d97706,#F59E0B);
             display:flex;align-items:center;justify-content:center;
             font-size:22px;margin:16px 0 12px;">💛</div>
        <h5 class="fw-bold" style="color:#d97706;">Our Values</h5>
        <p class="text-muted small">Compassion, transparency, community service, health education, and voluntary participation guide everything we do. We believe every drop of blood given freely can save lives.</p>
      </div>
    </div>
  </div>

  <div class="card card-dbu p-5 mb-5" style="background:linear-gradient(160deg,#fff 60%,#f5f3ff 100%);">
    <div class="row align-items-center g-4">
      <div class="col-md-6">
        <h2 class="section-title mb-3">What We Do</h2>
        <p class="text-muted mb-3">The DBU Blood Bank Management System is a comprehensive digital platform that connects blood donors with patients in need across Debre Berhan University.</p>
        <ul class="list-unstyled">
          <?php
          $services = [
            ['🔍','Donor Registry','Maintain an up-to-date database of all willing blood donors','#CC0000'],
            ['📋','Blood Requests','Process emergency blood requests from hospitals and patients','#7c3aed'],
            ['🏥','Blood Stock','Track and manage blood inventory levels by type','#0891b2'],
            ['📚','Health Education','Provide donation tips and eligibility information','#059669'],
            ['🖼️','Community Gallery','Share donation moments to inspire others','#db2777'],
            ['📞','24/7 Support','Provide emergency contact for urgent blood needs','#ea580c'],
          ];
          foreach ($services as [$icon,$title,$desc,$color]): ?>
          <li class="d-flex align-items-start gap-3 mb-3">
            <div style="width:38px;height:38px;border-radius:10px;flex-shrink:0;
                 background:<?= $color ?>22;
                 display:flex;align-items:center;justify-content:center;font-size:16px;">
              <?= $icon ?>
            </div>
            <div>
              <div class="fw-bold" style="color:<?= $color ?>;"><?= $title ?></div>
              <div class="text-muted small"><?= $desc ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-md-6 text-center">
        <div class="p-4 rounded-4"
             style="background:linear-gradient(135deg,rgba(0,48,135,.05),rgba(124,58,237,.07),rgba(204,0,0,.05));">
          <div style="font-size:80px;" class="anim-pulse">🩸</div>
          <h4 class="fw-black mt-3" style="color:#CC0000;">Every Drop Counts</h4>
          <p class="text-muted">One pint of blood can save up to 3 lives. At DBU, we make it easy to donate, request, and track blood — all in one system.</p>
          <a href="register.php" class="btn rounded-pill px-5 fw-bold"
             style="background:linear-gradient(135deg,#CC0000,#7c3aed);color:#fff;border:none;
                    box-shadow:0 4px 16px rgba(124,58,237,.3);">Become a Donor Today</a>
        </div>
      </div>
    </div>
  </div>

  <!-- UPDATED 2026 TIMELINE -->
  <div class="mb-5">
    <div class="text-center mb-4">
      <h2 class="section-title">Our Journey — 2026 Timeline</h2>
      <p class="text-muted">The development phases of the DBU Blood Bank System — 2026 Semester</p>
    </div>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <?php
        $colors = ['#CC0000','#7c3aed','#059669','#0891b2','#d97706'];
        $history = [
          ['March 2026',  'Phase 1 — Project Initialization & Research',
           'Project kickoff at Debre Berhan University. Conducted research on blood bank needs, defined system requirements, and formed the Feminist Group development team.'],
          ['April 2026',  'Phase 2 — Core Development',
           'Implemented the core PHP server logic, MySQL database schema, and the Colorful v3 user interface. Built donor registration, login, blood request, and search features.'],
          ['Mid-April 2026','Phase 3 — Advanced Features Integration',
           'Integrated the Hospital Portal for requesting blood, PDF Reporting for admin, and applied the Glassmorphism visual design effects across the system.'],
          ['May 2026',    'Phase 4 — Final Review & IS Department Presentation',
           'Comprehensive system testing, academic integrity review, final UI polish, and official presentation to the Information Systems Department at Debre Berhan University.'],
          ['May 2026',    'System Delivery',
           'Full-featured, offline-capable blood bank management system delivered. Includes donor certification, eligibility tracker, hospital portal, and PDF reporting.'],
        ];
        foreach ($history as $i => [$year,$title,$desc]):
          $c = $colors[$i % count($colors)];
        ?>
        <div class="timeline-item">
          <div class="timeline-dot" style="background:<?= $c ?>;">
            <?= ($i+1) ?>
          </div>
          <div class="fw-bold" style="color:<?= $c ?>;"><?= $year ?> — <?= $title ?></div>
          <div class="text-muted small mt-1"><?= $desc ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card card-dbu p-5 mb-5"
       style="background:linear-gradient(160deg,#fff 0%,#eff6ff 50%,#ecfdf5 100%);">
    <div class="text-center mb-4">
      <h2 class="section-title">Our Core Values</h2>
    </div>
    <div class="row g-4">
      <?php $values = [
        ['❤️','Compassion','We act from empathy, putting the needs of patients and community first.',
         'linear-gradient(135deg,#CC0000,#e11d48)'],
        ['🤝','Community','We believe in the power of collective action and mutual support.',
         'linear-gradient(135deg,#4338ca,#7c3aed)'],
        ['🌿','Health','Promoting health awareness and safe donation practices is central to our mission.',
         'linear-gradient(135deg,#059669,#10b981)'],
        ['🔒','Integrity','We maintain full transparency in donor data management and operations.',
         'linear-gradient(135deg,#d97706,#F59E0B)'],
      ]; foreach ($values as [$icon,$title,$desc,$grad]): ?>
      <div class="col-md-3 col-sm-6 text-center">
        <div class="value-icon mx-auto" style="background:<?= $grad ?>;box-shadow:0 4px 16px rgba(0,0,0,.12);">
          <?= $icon ?>
        </div>
        <h6 class="fw-bold"><?= $title ?></h6>
        <p class="text-muted small"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- OUR TEAM — local photos only, no external fallbacks -->
  <div class="team-section mb-5">
    <div class="text-center mb-4">
      <h2 class="section-title gradient-heading" style="-webkit-text-fill-color:transparent;">Our Team</h2>
      <p class="fw-bold mb-1"
         style="background:linear-gradient(90deg,#7c3aed,#db2777);-webkit-background-clip:text;
                -webkit-text-fill-color:transparent;background-clip:text;">
        Feminist Group — Debre Berhan University
      </p>
      <p class="text-muted" style="max-width:540px;margin:0 auto;">The passionate individuals who built and maintain the DBU Blood Bank system, dedicated to empowering communities through technology.</p>
    </div>

    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-4 justify-content-center">

      <div class="col text-center">
        <?php if (file_exists('uploads/member1.jpg')): ?>
          <img src="uploads/member1.jpg" alt="Mihret Alemayehu" class="team-member-photo mem1">
        <?php else: ?>
          <div class="member-avatar mem1" style="background:linear-gradient(135deg,#CC0000,#e11d48);">M</div>
        <?php endif; ?>
        <div class="team-member-name mem1-name">Kalkidan Lemma</div>
        
      </div>

      <div class="col text-center">
        <?php if (file_exists('uploads/member2.jpg')): ?>
          <img src="uploads/member2.jpg" alt="Abebech Nega" class="team-member-photo mem2">
        <?php else: ?>
          <div class="member-avatar mem2" style="background:linear-gradient(135deg,#7c3aed,#4338ca);">A</div>
        <?php endif; ?>
        <div class="team-member-name mem2-name">Abebech Nega</div>
        
      </div>

      <div class="col text-center">
        <?php if (file_exists('uploads/member3.jpg')): ?>
          <img src="uploads/member3.jpg" alt="Mihret Alemayehu" class="team-member-photo mem3">
        <?php else: ?>
          <div class="member-avatar mem3" style="background:linear-gradient(135deg,#059669,#10b981);">S</div>
        <?php endif; ?>
        <div class="team-member-name mem3-name">Mihret Alemayehu</div>
        
      </div>

      <div class="col text-center">
        <?php if (file_exists('uploads/member4.jpg')): ?>
          <img src="uploads/member4.jpg" alt="Hiwot Tadesse" class="team-member-photo mem4">
        <?php else: ?>
          <div class="member-avatar mem4" style="background:linear-gradient(135deg,#0891b2,#0e7490);">H</div>
        <?php endif; ?>
        <div class="team-member-name mem4-name">Gebeyanesh Smegnew</div>
        
      </div>

      <div class="col text-center">
        <?php if (file_exists('uploads/member5.jpg')): ?>
          <img src="uploads/member5.jpg" alt="Tigist Alemu" class="team-member-photo mem5">
        <?php else: ?>
          <div class="member-avatar mem5" style="background:linear-gradient(135deg,#d97706,#F59E0B);">T</div>
        <?php endif; ?>
        <div class="team-member-name mem5-name">Fantayenesh Worku</div>
        
      </div>

    </div>

    <div class="text-center mt-4 pt-3" style="border-top:2px solid;
         border-image:linear-gradient(90deg,#CC0000,#7c3aed,#059669,#d97706) 1;">
      
    </div>
  </div>

  <div class="text-center py-5 px-4 rounded-4 text-white"
       style="background:linear-gradient(135deg,#003087 0%,#4c1d95 50%,#CC0000 100%);
              position:relative;overflow:hidden;">
    <div style="position:absolute;inset:0;
         background:radial-gradient(ellipse at 50% 50%,rgba(245,158,11,.12),transparent 70%);
         pointer-events:none;"></div>
    <div style="position:relative;">
      <div style="font-size:52px;margin-bottom:12px;">🌸</div>
      <h3 class="fw-black">Built by the Feminist Group — DBU</h3>
      <p style="color:rgba(255,255,255,.82);margin-bottom:20px;max-width:500px;margin:0 auto 20px;">
        This system was designed and developed by the <strong>Project Development Team</strong> under the
        <strong>Feminist Group</strong> initiative at Debre Berhan University.
      </p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="contact.php" class="btn rounded-pill px-4 fw-bold"
           style="background:rgba(255,255,255,.18);color:#fff;border:1.5px solid rgba(255,255,255,.35);">
          📞 Contact Us
        </a>
        <a href="register.php" class="btn rounded-pill px-4 fw-bold"
           style="background:linear-gradient(135deg,#F59E0B,#ea580c);color:#fff;border:none;">
          💉 Join as Donor
        </a>
      </div>
    </div>
  </div>

</div>
<?php require 'includes/footer.php'; ?>
</body>
</html>
