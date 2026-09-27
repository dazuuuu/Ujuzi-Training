<?php
/** Public About Us page. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title>About Us | <?= e(appName()) ?></title>
  <meta name="description" content="Learn about <?= e(appName()) ?> — our mission, vision and the team behind Kenya's leading learning management system."/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/srms.css') ?>">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Inter',sans-serif;background:#fff;color:#111827;}
    a{text-decoration:none;color:inherit;}

    /* NAV — same as other pages */
    .lp-nav{position:sticky;top:0;z-index:200;background:#fff;border-bottom:1.5px solid #f1f1f1;display:flex;align-items:center;justify-content:space-between;padding:0 5%;height:68px;gap:16px;}
    .lp-nav-logo{display:flex;align-items:center;gap:10px;font-family:'Manrope',sans-serif;font-size:1.2rem;font-weight:800;color:#111;white-space:nowrap;flex-shrink:0;}
    .lp-nav-logo span{background:#dc2626;color:#fff;border-radius:10px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
    .lp-nav-center{display:flex;align-items:center;gap:6px;flex:1;justify-content:center;}
    .lp-nav-center a{font-size:.85rem;font-weight:600;color:#374151;padding:.4rem .75rem;border-radius:8px;transition:color .18s,background .18s;white-space:nowrap;}
    .lp-nav-center a:hover{color:#dc2626;background:#fef2f2;}
    .lp-nav-center a.active{color:#dc2626;background:#fef2f2;}
    .lp-nav-actions{display:flex;align-items:center;gap:10px;flex-shrink:0;}
    .btn-login{font-size:.82rem;font-weight:700;color:#374151;padding:.45rem .9rem;border-radius:99px;border:1.5px solid #e5e7eb;transition:all .18s;display:inline-block;white-space:nowrap;}
    .btn-login:hover{border-color:#dc2626;color:#dc2626;}
    .btn-get-started{font-size:.82rem;font-weight:700;background:#dc2626;color:#fff;padding:.45rem 1.1rem;border-radius:99px;border:none;cursor:pointer;display:inline-block;transition:all .18s;white-space:nowrap;}
    .btn-get-started:hover{background:#b91c1c;}
    .lp-dropdown{position:relative;}
    .lp-dropdown-menu{display:none;position:absolute;top:calc(100% + 10px);right:0;background:#fff;border:1.5px solid #f1f5f9;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.14);min-width:270px;z-index:400;overflow:hidden;}
    .lp-dropdown.open .lp-dropdown-menu{display:block;}
    .lp-dropdown-menu a{display:flex;align-items:center;gap:10px;padding:.65rem 1.1rem;font-size:.82rem;font-weight:600;color:#1e293b;border-bottom:1px solid #f8fafc;transition:background .12s;}
    .lp-dropdown-menu a:hover{background:#fef2f2;color:#dc2626;}
    .lp-dropdown-menu a.green:hover{background:#f0fdf4;color:#15803d;}
    .lp-drop-label{padding:.4rem 1.1rem .2rem;font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;background:#f8fafc;border-bottom:1px solid #f1f5f9;}
    .drop-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
    .lp-mobile-toggle{display:none;background:none;border:none;cursor:pointer;color:#374151;padding:.4rem;border-radius:8px;transition:background .18s;flex-shrink:0;}
    .lp-mobile-toggle:hover{background:#f3f4f6;}
    .lp-mobile-toggle svg{display:block;}
    .lp-mobile-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:250;}
    .lp-mobile-overlay.open{display:block;}
    .lp-mobile-nav{position:fixed;top:0;right:0;bottom:0;width:min(320px,85vw);background:#fff;z-index:300;overflow-y:auto;box-shadow:-8px 0 40px rgba(0,0,0,.15);transform:translateX(100%);transition:transform .3s ease;display:flex;flex-direction:column;}
    .lp-mobile-nav.open{transform:translateX(0);}
    .lp-mobile-header{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.25rem;border-bottom:1px solid #f1f1f1;}
    .lp-mobile-close{background:none;border:none;cursor:pointer;font-size:1.4rem;color:#374151;line-height:1;}
    .lp-mobile-body{padding:.75rem 1.25rem;flex:1;}
    .lp-mobile-body a{display:flex;align-items:center;gap:10px;padding:.7rem .5rem;font-size:.92rem;font-weight:600;color:#374151;border-bottom:1px solid #f3f4f6;transition:color .15s;}
    .lp-mobile-body a:last-of-type{border-bottom:none;}
    .lp-mobile-body a:hover{color:#dc2626;}
    .lp-mobile-section{padding:.8rem .5rem .3rem;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-top:.25rem;}
    .lp-mobile-footer{padding:1rem 1.25rem;border-top:1px solid #f1f1f1;display:flex;flex-direction:column;gap:10px;}
    .lp-mobile-footer .btn-login,.lp-mobile-footer .btn-get-started{display:block;text-align:center;padding:.65rem 1rem;}

    /* PAGE STYLES */
    .about-hero{background:linear-gradient(135deg,#0f172a 0%,#166534 100%);padding:80px 5%;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;max-width:none;}
    .about-hero-left{max-width:560px;}
    .about-badge{display:inline-block;background:rgba(255,255,255,.12);color:rgba(255,255,255,.9);font-size:.72rem;font-weight:700;padding:.35rem .9rem;border-radius:99px;margin-bottom:20px;letter-spacing:.06em;text-transform:uppercase;}
    .about-hero h1{font-family:'Manrope',sans-serif;font-size:2.8rem;font-weight:900;color:#fff;line-height:1.15;margin-bottom:20px;}
    .about-hero h1 em{color:#4ade80;font-style:normal;}
    .about-hero p{color:rgba(255,255,255,.75);font-size:1rem;line-height:1.7;margin-bottom:28px;}
    .about-hero-ctas{display:flex;gap:14px;flex-wrap:wrap;}
    .btn-white{background:#fff;color:#111827;font-weight:700;font-size:.9rem;padding:.75rem 1.6rem;border-radius:99px;display:inline-flex;align-items:center;gap:8px;transition:all .2s;}
    .btn-white:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,255,255,.2);}
    .btn-outline-white{background:transparent;color:#fff;font-weight:700;font-size:.9rem;padding:.75rem 1.6rem;border-radius:99px;border:2px solid rgba(255,255,255,.4);display:inline-flex;align-items:center;gap:8px;transition:all .2s;}
    .btn-outline-white:hover{border-color:#fff;background:rgba(255,255,255,.1);}
    .about-hero-visual{display:flex;flex-direction:column;gap:14px;align-items:flex-end;}
    .about-stat-card{background:rgba(255,255,255,.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:20px 28px;text-align:center;width:100%;}
    .about-stat-card .num{font-family:'Manrope',sans-serif;font-size:2.2rem;font-weight:900;color:#fff;}
    .about-stat-card .lbl{font-size:.75rem;font-weight:600;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.06em;margin-top:4px;}
    .about-stat-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;width:100%;}

    .section{padding:80px 5%;}
    .section-inner{max-width:1200px;margin:0 auto;}
    .section-label{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#dc2626;margin-bottom:10px;}
    .section-title{font-family:'Manrope',sans-serif;font-size:2rem;font-weight:900;color:#111827;line-height:1.2;margin-bottom:16px;}
    .section-sub{font-size:.95rem;color:#6b7280;line-height:1.65;max-width:560px;}

    /* MVV */
    .mvv-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:48px;}
    .mvv-card{border-radius:20px;padding:32px 28px;position:relative;overflow:hidden;}
    .mvv-card-icon{width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:20px;}
    .mvv-card h3{font-size:1.15rem;font-weight:800;margin-bottom:12px;}
    .mvv-card p{font-size:.88rem;line-height:1.65;}

    /* FEATURES */
    .feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:48px;}
    .feat-card{background:#fff;border:1.5px solid #f1f5f9;border-radius:18px;padding:28px;transition:all .2s;}
    .feat-card:hover{box-shadow:0 12px 30px rgba(0,0,0,.07);transform:translateY(-3px);}
    .feat-card-icon{width:50px;height:50px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:18px;}
    .feat-card h3{font-size:.98rem;font-weight:800;color:#111827;margin-bottom:8px;}
    .feat-card p{font-size:.84rem;color:#6b7280;line-height:1.6;}

    /* TEAM */
    .team-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px;}
    .team-card{background:#fff;border:1.5px solid #f1f5f9;border-radius:18px;padding:24px;text-align:center;transition:all .2s;}
    .team-card:hover{box-shadow:0 12px 30px rgba(0,0,0,.07);transform:translateY(-3px);}
    .team-av{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:900;color:#fff;margin:0 auto 14px;}
    .team-name{font-size:.98rem;font-weight:800;color:#111827;margin-bottom:4px;}
    .team-role{font-size:.78rem;color:#6b7280;font-weight:600;}

    /* CTA */
    .cta-section{background:linear-gradient(135deg,#0f172a 0%,#166534 100%);padding:80px 5%;text-align:center;}
    .cta-section h2{font-family:'Manrope',sans-serif;font-size:2.2rem;font-weight:900;color:#fff;margin-bottom:14px;}
    .cta-section p{color:rgba(255,255,255,.7);font-size:1rem;margin-bottom:32px;max-width:520px;margin-left:auto;margin-right:auto;}
    .cta-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;}

    /* FOOTER */
    .lp-footer{background:#0f172a;color:#fff;padding:56px 5% 24px;}
    .lp-footer-grid{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px;}
    .lp-footer-brand p{font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.65;margin-top:12px;max-width:240px;}
    .lp-footer-col h4{font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.9);margin-bottom:16px;}
    .lp-footer-col ul{list-style:none;display:flex;flex-direction:column;gap:9px;}
    .lp-footer-col ul li a{font-size:.84rem;color:rgba(255,255,255,.5);font-weight:500;transition:color .15s;}
    .lp-footer-col ul li a:hover{color:#fff;}
    .lp-footer-col ul li a.red{color:#f87171;}
    .lp-footer-bottom{max-width:1200px;margin:0 auto;border-top:1px solid rgba(255,255,255,.08);padding-top:20px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;}
    .lp-footer-bottom p{font-size:.78rem;color:rgba(255,255,255,.4);}

    @media(max-width:1024px){
      .lp-footer-grid{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:768px){
      .about-hero{grid-template-columns:1fr;padding:48px 5%;}
      .about-hero-visual{display:none;}
      .about-hero h1{font-size:2rem;}
      .mvv-grid,.feat-grid{grid-template-columns:1fr;}
      .team-grid{grid-template-columns:1fr 1fr;}
      .lp-nav-center,.lp-nav-actions{display:none;}
      .lp-mobile-toggle{display:flex;align-items:center;}
      .lp-footer-grid{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:480px){.team-grid{grid-template-columns:1fr;}.lp-footer-grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>

<header style="position:relative;">
  <nav class="lp-nav">
    <a href="<?= url('/') ?>" class="lp-nav-logo"><span>🎓</span><?= e(appName()) ?></a>
    <div class="lp-nav-center">
      <a href="<?= url('/') ?>">Home</a>
      <a href="<?= url('/courses') ?>">Courses</a>
      <a href="<?= url('/about') ?>" class="active">About Us</a>
    </div>
    <div class="lp-nav-actions">
      <a href="<?= url('/account/login') ?>" class="btn-login">Log In</a>
      <div class="lp-dropdown" id="signupDrop">
        <button class="btn-get-started" id="signupDropBtn">Get Started ▾</button>
        <div class="lp-dropdown-menu">
          <div class="lp-drop-label">Course Portals</div>
          <a href="<?= url('/account/login/organisation-admin') ?>"><span class="drop-icon" style="background:#fef2f2;">🏢</span>Organisation (Course Provider)</a>
          <a href="<?= url('/account/login/course-branch-admin') ?>"><span class="drop-icon" style="background:#fff7ed;">🏬</span>Branch Admin (Course Org)</a>
          <a href="<?= url('/account/register') ?>" class="green"><span class="drop-icon" style="background:#f0fdf4;">🎓</span>Student</a>
          <a href="<?= url('/account/register/trainer') ?>" class="green"><span class="drop-icon" style="background:#f0fdf4;">👨‍🏫</span>Tutor / Teacher</a>
          <div class="lp-drop-label">Attachment Portals</div>
          <a href="<?= url('/account/register/attachment-trainer') ?>"><span class="drop-icon" style="background:#faf5ff;">🤝</span>Organisation (Attachment)</a>
          <a href="<?= url('/account/login/branch-admin') ?>"><span class="drop-icon" style="background:#f1f5f9;">📍</span>Branch (Attachment Org)</a>
        </div>
      </div>
    </div>
    <button class="lp-mobile-toggle" id="mobileToggle" aria-label="Open menu">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
  </nav>

  <div class="lp-mobile-overlay" id="mobileOverlay"></div>
  <div class="lp-mobile-nav" id="mobileNav" role="dialog" aria-modal="true">
    <div class="lp-mobile-header">
      <a href="<?= url('/') ?>" class="lp-nav-logo" style="font-size:1.1rem;"><span style="width:30px;height:30px;font-size:.95rem;">🎓</span><?= e(appName()) ?></a>
      <button class="lp-mobile-close" id="mobileClose" aria-label="Close">✕</button>
    </div>
    <div class="lp-mobile-body">
      <a href="<?= url('/') ?>">🏠 Home</a>
      <a href="<?= url('/courses') ?>">📚 Courses</a>
      <a href="<?= url('/about') ?>" style="color:#dc2626;">ℹ️ About Us</a>
      <div class="lp-mobile-section">Sign Up As</div>
      <a href="<?= url('/account/login/organisation-admin') ?>">🏢 Organisation (Course Provider)</a>
      <a href="<?= url('/account/login/course-branch-admin') ?>">🏬 Branch Admin (Course Org)</a>
      <a href="<?= url('/account/register') ?>">🎓 Student</a>
      <a href="<?= url('/account/register/trainer') ?>">👨‍🏫 Tutor / Teacher</a>
      <div class="lp-mobile-section">Attachment Portals</div>
      <a href="<?= url('/account/register/attachment-trainer') ?>">🤝 Organisation (Attachment)</a>
      <a href="<?= url('/account/login/branch-admin') ?>">📍 Branch (Attachment Org)</a>
    </div>
    <div class="lp-mobile-footer">
      <a href="<?= url('/account/login') ?>" class="btn-login">Log In</a>
      <a href="<?= url('/account/register') ?>" class="btn-get-started">Get Started Free →</a>
    </div>
  </div>
</header>
<script>
(function(){
  var toggle=document.getElementById('mobileToggle'),nav=document.getElementById('mobileNav'),overlay=document.getElementById('mobileOverlay'),close=document.getElementById('mobileClose');
  function open(){ nav.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden'; }
  function shut(){ nav.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow=''; }
  toggle.addEventListener('click',open); close.addEventListener('click',shut); overlay.addEventListener('click',shut);
})();
(function(){
  var btn=document.getElementById('signupDropBtn'),drop=document.getElementById('signupDrop');
  if(btn) btn.addEventListener('click',function(e){ e.stopPropagation(); drop.classList.toggle('open'); });
  document.addEventListener('click',function(e){ if(drop&&!drop.contains(e.target)) drop.classList.remove('open'); });
})();
</script>

<!-- HERO -->
<section class="about-hero">
  <div class="about-hero-left">
    <span class="about-badge">Our Story</span>
    <h1>Empowering Learners,<br><em>Transforming Futures</em></h1>
    <p><?= e(appName()) ?> is a comprehensive, role-based Learning Management System built to bridge the gap between education, professional training, and real-world attachment experiences — all in one powerful platform.</p>
    <div class="about-hero-ctas">
      <a href="<?= url('/courses') ?>" class="btn-white">Browse Courses</a>
      <a href="<?= url('/account/register') ?>" class="btn-outline-white">Join for Free</a>
    </div>
  </div>
  <div class="about-hero-visual">
    <div class="about-stat-row">
      <div class="about-stat-card"><div class="num">20K+</div><div class="lbl">Active Students</div></div>
      <div class="about-stat-card"><div class="num">500+</div><div class="lbl">Expert Tutors</div></div>
    </div>
    <div class="about-stat-row">
      <div class="about-stat-card"><div class="num">1,200+</div><div class="lbl">Courses</div></div>
      <div class="about-stat-card"><div class="num">95%</div><div class="lbl">Success Rate</div></div>
    </div>
    <div style="background:rgba(255,255,255,.1);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:20px 24px;width:100%;display:flex;align-items:center;gap:16px;">
      <span style="font-size:2rem;">🏆</span>
      <div><div style="color:#fff;font-weight:800;font-size:1rem;">Award-Winning Platform</div><div style="color:rgba(255,255,255,.6);font-size:.8rem;margin-top:3px;">Best EdTech Solution 2024</div></div>
    </div>
  </div>
</section>

<!-- MISSION / VISION / VALUES -->
<section class="section" style="background:#f9fafb;">
  <div class="section-inner">
    <div style="text-align:center;max-width:600px;margin:0 auto;">
      <div class="section-label">What Drives Us</div>
      <div class="section-title">Our Mission, Vision &amp; Values</div>
    </div>
    <div class="mvv-grid">
      <div class="mvv-card" style="background:#fef2f2;border:1.5px solid #fecaca;">
        <div class="mvv-card-icon" style="background:#dc2626;color:#fff;">🎯</div>
        <h3 style="color:#991b1b;">Our Mission</h3>
        <p style="color:#374151;">To make quality, role-based education accessible to every learner, tutor, and organisation — regardless of geography or institution size.</p>
      </div>
      <div class="mvv-card" style="background:#f0fdf4;border:1.5px solid #bbf7d0;">
        <div class="mvv-card-icon" style="background:#16a34a;color:#fff;">🌍</div>
        <h3 style="color:#14532d;">Our Vision</h3>
        <p style="color:#374151;">To be the leading platform connecting learners with courses, organisations with talent, and students with real-world attachment opportunities.</p>
      </div>
      <div class="mvv-card" style="background:#0f172a;border:1.5px solid #1e293b;">
        <div class="mvv-card-icon" style="background:rgba(255,255,255,.12);color:#fff;">💡</div>
        <h3 style="color:#fff;">Our Values</h3>
        <p style="color:rgba(255,255,255,.65);">Excellence, integrity, inclusivity, and innovation drive every feature we build and every learner journey we support on this platform.</p>
      </div>
    </div>
  </div>
</section>

<!-- PLATFORM FEATURES -->
<section class="section" style="background:#fff;">
  <div class="section-inner">
    <div>
      <div class="section-label">Platform Features</div>
      <div class="section-title">What <?= e(appName()) ?> Offers</div>
      <p class="section-sub">A multi-role, multi-organisation platform built for real-world education and training workflows.</p>
    </div>
    <div class="feat-grid">
      <?php
      $features = [
        ['icon'=>'📚','title'=>'Online Courses','desc'=>'Organisations publish courses. Tutors teach. Students learn at their own pace and earn verified certificates.','ibg'=>'#fef2f2','ic'=>'#dc2626'],
        ['icon'=>'🏢','title'=>'Multi-Organisation','desc'=>'Multiple organisations operate independently with their own branches, tutors, and students on the same platform.','ibg'=>'#f0fdf4','ic'=>'#16a34a'],
        ['icon'=>'🏆','title'=>'Certificates','desc'=>'Students receive shareable certificates on completion. Attachment providers issue recommendation letters.','ibg'=>'#0f172a','ic'=>'#fff'],
        ['icon'=>'🤝','title'=>'Attachment Management','desc'=>'Attachment organisations register branches and accept students after they complete relevant courses.','ibg'=>'#fef2f2','ic'=>'#dc2626'],
        ['icon'=>'📍','title'=>'Branch System','desc'=>'Both course organisations and attachment providers manage multiple branches with dedicated branch admins.','ibg'=>'#f0fdf4','ic'=>'#16a34a'],
        ['icon'=>'🔐','title'=>'Role-Based Security','desc'=>'Every user has a specific role with a tailored dashboard and permissions — from student to super admin.','ibg'=>'#0f172a','ic'=>'#fff'],
      ];
      foreach ($features as $f):
      ?>
        <div class="feat-card">
          <div class="feat-card-icon" style="background:<?= $f['ibg'] ?>;color:<?= $f['ic'] ?>;"><?= $f['icon'] ?></div>
          <h3><?= e($f['title']) ?></h3>
          <p><?= e($f['desc']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TEAM -->
<section class="section" style="background:#f9fafb;">
  <div class="section-inner">
    <div style="text-align:center;max-width:500px;margin:0 auto;">
      <div class="section-label">Meet the Team</div>
      <div class="section-title">The People Behind <?= e(appName()) ?></div>
    </div>
    <div class="team-grid">
      <?php
      $team = [
        ['name'=>'David K.','role'=>'Founder & CEO','av'=>'D','bg'=>'#dc2626'],
        ['name'=>'Mary J.','role'=>'Head of Curriculum','av'=>'M','bg'=>'#16a34a'],
        ['name'=>'James O.','role'=>'Lead Engineer','av'=>'J','bg'=>'#0f172a'],
        ['name'=>'Grace A.','role'=>'Head of Partnerships','av'=>'G','bg'=>'#7c3aed'],
      ];
      foreach ($team as $m):
      ?>
        <div class="team-card">
          <div class="team-av" style="background:<?= $m['bg'] ?>;"><?= $m['av'] ?></div>
          <div class="team-name"><?= e($m['name']) ?></div>
          <div class="team-role"><?= e($m['role']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <h2>Ready to Start Learning?</h2>
  <p>Join thousands of learners already using <?= e(appName()) ?> to advance their careers and unlock new opportunities.</p>
  <div class="cta-btns">
    <a href="<?= url('/courses') ?>" class="btn-white">Browse Courses</a>
    <a href="<?= url('/account/register') ?>" class="btn-outline-white">Register as Student</a>
  </div>
</section>

<!-- FOOTER -->
<footer class="lp-footer">
  <div class="lp-footer-grid">
    <div class="lp-footer-brand">
      <div class="lp-nav-logo" style="color:#fff;"><span>🎓</span><?= e(appName()) ?></div>
      <p>Empowering learners with quality education and practical skills for a better future.</p>
    </div>
    <div class="lp-footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= url('/') ?>">Home</a></li>
        <li><a href="<?= url('/courses') ?>">Courses</a></li>
        <li><a href="<?= url('/about') ?>">About Us</a></li>
        <li><a href="<?= url('/account/login') ?>">Sign In</a></li>
      </ul>
    </div>
    <div class="lp-footer-col">
      <h4>Sign Up</h4>
      <ul>
        <li><a href="<?= url('/account/register') ?>">Student</a></li>
        <li><a href="<?= url('/account/register/trainer') ?>">Tutor / Teacher</a></li>
        <li><a href="<?= url('/account/register/attachment-trainer') ?>">Attachment Provider</a></li>
      </ul>
    </div>
    <div class="lp-footer-col">
      <h4>Support</h4>
      <ul>
        <li><a href="#">Terms &amp; Conditions</a></li>
        <li><a href="#">Privacy Policy</a></li>
        <?php if (!empty($needsSetup)): ?>
          <li><a href="<?= url('/setup') ?>" class="red">Run Setup</a></li>
        <?php else: ?>
          <li><a href="<?= url('/admin/login') ?>" class="red">Super Admin</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="lp-footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= e(appName()) ?>. All Rights Reserved.</p>
    <p>Made with ❤️ for Education</p>
  </div>
</footer>
</body>
</html>
