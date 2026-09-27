<?php
/** Public Courses listing page. */
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title>Courses | <?= e(appName()) ?></title>
  <meta name="description" content="Browse all public courses on <?= e(appName()) ?>. Learn from expert instructors and earn certificates."/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/srms.css') ?>">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Inter',sans-serif;background:#f9fafb;color:#111827;}
    a{text-decoration:none;color:inherit;}
    img{display:block;max-width:100%;}

    /* ═══════════════════════════════════
       NAV — fully responsive
    ═══════════════════════════════════ */
    .lp-nav{
      position:sticky;top:0;z-index:200;
      background:#fff;
      border-bottom:1.5px solid #f1f1f1;
      display:flex;align-items:center;justify-content:space-between;
      padding:0 5%;
      height:68px;
      gap:16px;
    }
    /* Logo */
    .lp-nav-logo{
      display:flex;align-items:center;gap:10px;
      font-family:'Manrope',sans-serif;font-size:1.2rem;font-weight:800;
      color:#111;white-space:nowrap;flex-shrink:0;
    }
    .lp-nav-logo span{
      background:#dc2626;color:#fff;border-radius:10px;
      width:36px;height:36px;display:flex;align-items:center;justify-content:center;
      font-size:1.1rem;flex-shrink:0;
    }
    /* Desktop links — center group */
    .lp-nav-center{
      display:flex;align-items:center;gap:6px;flex:1;justify-content:center;
    }
    .lp-nav-center a{
      font-size:.85rem;font-weight:600;color:#374151;
      padding:.4rem .75rem;border-radius:8px;
      transition:color .18s,background .18s;white-space:nowrap;
    }
    .lp-nav-center a:hover{color:#dc2626;background:#fef2f2;}
    .lp-nav-center a.active{color:#dc2626;background:#fef2f2;}
    /* Search */
    .lp-nav-search{
      display:flex;align-items:center;background:#f3f4f6;border-radius:99px;
      padding:0 14px;gap:8px;height:38px;width:200px;flex-shrink:0;
    }
    .lp-nav-search input{border:none;background:transparent;outline:none;font-size:.82rem;color:#374151;width:100%;}
    /* Right actions */
    .lp-nav-actions{display:flex;align-items:center;gap:10px;flex-shrink:0;}
    .btn-login{
      font-size:.82rem;font-weight:700;color:#374151;
      padding:.45rem .9rem;border-radius:99px;border:1.5px solid #e5e7eb;
      transition:all .18s;display:inline-block;white-space:nowrap;
    }
    .btn-login:hover{border-color:#dc2626;color:#dc2626;}
    .btn-get-started{
      font-size:.82rem;font-weight:700;background:#dc2626;color:#fff;
      padding:.45rem 1.1rem;border-radius:99px;transition:all .18s;
      border:none;cursor:pointer;display:inline-block;white-space:nowrap;
    }
    .btn-get-started:hover{background:#b91c1c;transform:translateY(-1px);}
    /* Dropdown */
    .lp-dropdown{position:relative;}
    .lp-dropdown-menu{
      display:none;position:absolute;top:calc(100% + 10px);right:0;
      background:#fff;border:1.5px solid #f1f5f9;border-radius:16px;
      box-shadow:0 20px 60px rgba(0,0,0,.14);min-width:270px;z-index:400;overflow:hidden;
    }
    .lp-dropdown.open .lp-dropdown-menu{display:block;}
    .lp-dropdown-menu a{
      display:flex;align-items:center;gap:10px;padding:.65rem 1.1rem;
      font-size:.82rem;font-weight:600;color:#1e293b;
      border-bottom:1px solid #f8fafc;transition:background .12s;
    }
    .lp-dropdown-menu a:last-child{border-bottom:none;}
    .lp-dropdown-menu a:hover{background:#fef2f2;color:#dc2626;}
    .lp-dropdown-menu a.green:hover{background:#f0fdf4;color:#15803d;}
    .lp-drop-label{
      padding:.4rem 1.1rem .2rem;font-size:.6rem;font-weight:800;
      text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;
      background:#f8fafc;border-bottom:1px solid #f1f5f9;
    }
    .drop-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
    /* Hamburger toggle */
    .lp-mobile-toggle{
      display:none;background:none;border:none;cursor:pointer;
      color:#374151;padding:.4rem;border-radius:8px;
      transition:background .18s;flex-shrink:0;
    }
    .lp-mobile-toggle:hover{background:#f3f4f6;}
    .lp-mobile-toggle svg{display:block;}

    /* ── Mobile drawer ── */
    .lp-mobile-overlay{
      display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:250;
    }
    .lp-mobile-overlay.open{display:block;}
    .lp-mobile-nav{
      position:fixed;top:0;right:0;bottom:0;width:min(320px,85vw);
      background:#fff;z-index:300;overflow-y:auto;
      box-shadow:-8px 0 40px rgba(0,0,0,.15);
      transform:translateX(100%);transition:transform .3s ease;
      display:flex;flex-direction:column;
    }
    .lp-mobile-nav.open{transform:translateX(0);}
    .lp-mobile-header{
      display:flex;align-items:center;justify-content:space-between;
      padding:1.1rem 1.25rem;border-bottom:1px solid #f1f1f1;
    }
    .lp-mobile-close{background:none;border:none;cursor:pointer;font-size:1.4rem;color:#374151;line-height:1;}
    .lp-mobile-body{padding:.75rem 1.25rem;flex:1;}
    .lp-mobile-body a{
      display:flex;align-items:center;gap:10px;
      padding:.7rem .5rem;font-size:.92rem;font-weight:600;color:#374151;
      border-bottom:1px solid #f3f4f6;transition:color .15s;
    }
    .lp-mobile-body a:last-of-type{border-bottom:none;}
    .lp-mobile-body a:hover{color:#dc2626;}
    .lp-mobile-section{
      padding:.8rem .5rem .3rem;font-size:.62rem;font-weight:800;
      text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;margin-top:.25rem;
    }
    .lp-mobile-footer{padding:1rem 1.25rem;border-top:1px solid #f1f1f1;display:flex;flex-direction:column;gap:10px;}
    .lp-mobile-footer .btn-login,.lp-mobile-footer .btn-get-started{display:block;text-align:center;padding:.65rem 1rem;}

    /* ═══════════════════════════════════
       BANNER
    ═══════════════════════════════════ */
    .courses-banner{background:linear-gradient(135deg,#0f172a 0%,#166534 100%);padding:56px 5%;text-align:center;}
    .courses-banner h1{font-family:'Manrope',sans-serif;font-size:2.4rem;font-weight:900;color:#fff;margin-bottom:12px;}
    .courses-banner p{color:rgba(255,255,255,.7);font-size:1rem;max-width:500px;margin:0 auto 28px;}
    .courses-search-bar{display:flex;align-items:center;background:#fff;border-radius:99px;padding:8px 8px 8px 20px;max-width:540px;margin:0 auto;box-shadow:0 8px 30px rgba(0,0,0,.2);}
    .courses-search-bar input{flex:1;border:none;outline:none;font-size:.9rem;color:#111827;background:transparent;min-width:0;}
    .courses-search-bar button{background:#dc2626;color:#fff;font-size:.85rem;font-weight:700;padding:.6rem 1.4rem;border-radius:99px;border:none;cursor:pointer;transition:background .18s;white-space:nowrap;}
    .courses-search-bar button:hover{background:#b91c1c;}

    /* ═══════════════════════════════════
       COURSES GRID
    ═══════════════════════════════════ */
    .courses-container{max-width:1200px;margin:0 auto;padding:48px 5%;}
    .courses-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
    .courses-header h2{font-family:'Manrope',sans-serif;font-size:1.4rem;font-weight:800;color:#111827;}
    .courses-header span{font-size:.83rem;color:#6b7280;}

    .courses-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}

    /* ── Course card ── */
    .c-card{
      background:#fff;border:1.5px solid #f1f5f9;border-radius:20px;
      overflow:hidden;transition:all .22s;display:flex;flex-direction:column;
    }
    .c-card:hover{box-shadow:0 20px 48px rgba(0,0,0,.1);transform:translateY(-4px);border-color:#e2e8f0;}
    /* image area */
    .c-card-img{
      height:180px;position:relative;overflow:hidden;
      background:#e5e7eb;flex-shrink:0;
    }
    .c-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .3s;}
    .c-card:hover .c-card-img img{transform:scale(1.04);}
    .c-card-img-placeholder{
      width:100%;height:100%;display:flex;align-items:center;justify-content:center;
      font-size:4rem;
    }
    /* play overlay for video preview */
    .c-card-play-overlay{
      position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
      background:rgba(0,0,0,.25);opacity:0;transition:opacity .2s;
    }
    .c-card:hover .c-card-play-overlay{opacity:1;}
    .c-card-play-btn{
      width:52px;height:52px;background:rgba(255,255,255,.9);border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      box-shadow:0 4px 16px rgba(0,0,0,.2);
    }
    .c-card-play-btn svg{margin-left:3px;}
    /* badges */
    .c-card-badge{
      position:absolute;top:12px;left:12px;background:#16a34a;color:#fff;
      font-size:.62rem;font-weight:800;padding:3px 10px;border-radius:99px;
      text-transform:uppercase;letter-spacing:.06em;
    }
    .c-card-badge.paid{background:#dc2626;}
    .c-card-org-badge{
      position:absolute;top:12px;right:12px;background:rgba(15,23,42,.75);
      color:#fff;font-size:.58rem;font-weight:700;padding:3px 8px;border-radius:99px;
      backdrop-filter:blur(4px);max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
    }
    /* body */
    .c-card-body{padding:18px;flex:1;display:flex;flex-direction:column;gap:6px;}
    .c-card-cat{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#16a34a;}
    .c-card-title{font-size:.93rem;font-weight:700;color:#111827;line-height:1.38;flex:1;}
    .c-card-desc{font-size:.78rem;color:#6b7280;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
    .c-card-tutor{display:flex;align-items:center;gap:7px;font-size:.76rem;color:#6b7280;margin-top:4px;}
    .c-card-av{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;color:#fff;flex-shrink:0;}
    .c-card-meta{display:flex;align-items:center;gap:10px;font-size:.73rem;color:#9ca3af;flex-wrap:wrap;}
    .c-card-meta span{display:flex;align-items:center;gap:3px;}
    .c-card-footer{
      display:flex;align-items:center;justify-content:space-between;
      padding:14px 18px;border-top:1.5px solid #f3f4f6;gap:10px;
    }
    .c-card-price{font-size:1rem;font-weight:900;color:#111827;}
    .c-card-price.free{color:#16a34a;}
    .c-card-actions{display:flex;align-items:center;gap:8px;}
    .btn-view-more{
      background:#f3f4f6;color:#374151;font-size:.74rem;font-weight:700;
      padding:.4rem .85rem;border-radius:99px;border:1.5px solid #e5e7eb;
      transition:all .18s;cursor:pointer;white-space:nowrap;
    }
    .btn-view-more:hover{background:#e5e7eb;color:#111;}
    .btn-enroll{
      background:#dc2626;color:#fff;font-size:.74rem;font-weight:700;
      padding:.4rem .9rem;border-radius:99px;transition:all .18s;white-space:nowrap;
      display:inline-block;
    }
    .btn-enroll:hover{background:#b91c1c;transform:translateY(-1px);}

    /* ═══════════════════════════════════
       "VIEW MORE" MODAL
    ═══════════════════════════════════ */
    .c-modal-overlay{
      display:none;position:fixed;inset:0;z-index:500;
      background:rgba(15,23,42,.6);backdrop-filter:blur(4px);
      align-items:center;justify-content:center;padding:20px;
    }
    .c-modal-overlay.open{display:flex;}
    .c-modal{
      background:#fff;border-radius:24px;max-width:680px;width:100%;
      max-height:90vh;overflow-y:auto;
      box-shadow:0 40px 80px rgba(0,0,0,.25);
      animation:modalIn .25s ease;
    }
    @keyframes modalIn{from{transform:translateY(20px);opacity:0;}to{transform:translateY(0);opacity:1;}}
    .c-modal-img{height:220px;position:relative;overflow:hidden;border-radius:24px 24px 0 0;background:#e5e7eb;flex-shrink:0;}
    .c-modal-img img{width:100%;height:100%;object-fit:cover;}
    .c-modal-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:5rem;}
    .c-modal-body{padding:28px 28px 24px;}
    .c-modal-cat{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#16a34a;margin-bottom:8px;}
    .c-modal-title{font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:900;color:#111827;margin-bottom:12px;line-height:1.25;}
    .c-modal-meta{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:16px;}
    .c-modal-meta-item{display:flex;align-items:center;gap:5px;font-size:.78rem;color:#6b7280;font-weight:600;}
    .c-modal-desc{font-size:.88rem;color:#374151;line-height:1.7;margin-bottom:20px;}
    .c-modal-tutor{display:flex;align-items:center;gap:10px;padding:14px;background:#f9fafb;border-radius:12px;margin-bottom:20px;}
    .c-modal-tutor-av{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:900;color:#fff;flex-shrink:0;}
    .c-modal-tutor-name{font-size:.88rem;font-weight:700;color:#111827;}
    .c-modal-tutor-label{font-size:.72rem;color:#9ca3af;font-weight:600;}
    .c-modal-video{margin-bottom:20px;}
    .c-modal-video h4{font-size:.82rem;font-weight:800;color:#111827;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;}
    .c-modal-video iframe{width:100%;aspect-ratio:16/9;border-radius:12px;border:none;}
    .c-modal-price-row{display:flex;align-items:center;justify-content:space-between;padding:16px;background:#f9fafb;border-radius:14px;flex-wrap:wrap;gap:12px;}
    .c-modal-price{font-size:1.4rem;font-weight:900;color:#111827;}
    .c-modal-price.free{color:#16a34a;}
    .btn-enroll-lg{
      background:#dc2626;color:#fff;font-size:.9rem;font-weight:700;
      padding:.65rem 1.6rem;border-radius:99px;transition:all .2s;
      display:inline-flex;align-items:center;gap:8px;
    }
    .btn-enroll-lg:hover{background:#b91c1c;transform:translateY(-2px);}
    .c-modal-close{
      position:absolute;top:16px;right:16px;background:rgba(0,0,0,.4);
      color:#fff;border:none;border-radius:50%;width:36px;height:36px;
      cursor:pointer;font-size:1.2rem;display:flex;align-items:center;justify-content:center;
      transition:background .18s;z-index:10;
    }
    .c-modal-close:hover{background:rgba(0,0,0,.65);}

    /* ═══════════════════════════════════
       EMPTY STATE
    ═══════════════════════════════════ */
    .empty-state{text-align:center;padding:80px 20px;grid-column:1/-1;}
    .empty-state .icon{font-size:4rem;display:block;margin-bottom:16px;}
    .empty-state h3{font-size:1.4rem;font-weight:800;color:#111827;margin-bottom:8px;}
    .empty-state p{color:#6b7280;margin-bottom:24px;line-height:1.6;max-width:400px;margin-left:auto;margin-right:auto;}
    .btn-green{background:#16a34a;color:#fff;font-size:.9rem;font-weight:700;padding:.75rem 1.6rem;border-radius:99px;display:inline-block;transition:all .2s;}
    .btn-green:hover{background:#15803d;transform:translateY(-1px);}

    /* ═══════════════════════════════════
       FOOTER
    ═══════════════════════════════════ */
    .lp-footer{background:#0f172a;color:#fff;padding:56px 5% 24px;}
    .lp-footer-inner{max-width:1200px;margin:0 auto;}
    .lp-footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px;}
    .lp-footer-brand p{font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.65;margin-top:12px;max-width:240px;}
    .lp-footer-col h4{font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.9);margin-bottom:16px;}
    .lp-footer-col ul{list-style:none;display:flex;flex-direction:column;gap:9px;}
    .lp-footer-col ul li a{font-size:.84rem;color:rgba(255,255,255,.5);font-weight:500;transition:color .15s;}
    .lp-footer-col ul li a:hover{color:#fff;}
    .lp-footer-col ul li a.red{color:#f87171;}
    .lp-footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding-top:20px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;}
    .lp-footer-bottom p{font-size:.78rem;color:rgba(255,255,255,.4);}

    /* ═══════════════════════════════════
       RESPONSIVE
    ═══════════════════════════════════ */
    @media(max-width:1024px){
      .courses-grid{grid-template-columns:repeat(2,1fr);}
      .lp-footer-grid{grid-template-columns:1fr 1fr;}
      .lp-nav-search{display:none;}
    }
    @media(max-width:768px){
      .lp-nav-center,.lp-nav-actions{display:none;}
      .lp-mobile-toggle{display:flex;align-items:center;}
      .courses-banner h1{font-size:1.8rem;}
      .lp-footer-grid{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:600px){
      .courses-grid{grid-template-columns:1fr;}
      .c-modal-body{padding:20px 20px 18px;}
      .c-modal-title{font-size:1.25rem;}
    }
    @media(max-width:480px){
      .lp-footer-grid{grid-template-columns:1fr;}
      .courses-container{padding:32px 5%;}
    }
  </style>
</head>
<body>

<!-- ════════════════════════════════════════
     NAVBAR
════════════════════════════════════════ -->
<header style="position:relative;">
  <nav class="lp-nav">
    <!-- Logo -->
    <a href="<?= url('/') ?>" class="lp-nav-logo">
      <span>🎓</span><?= e(appName()) ?>
    </a>

    <!-- Desktop centre links -->
    <div class="lp-nav-center">
      <a href="<?= url('/') ?>">Home</a>
      <a href="<?= url('/courses') ?>" class="active">Courses</a>
      <a href="<?= url('/about') ?>">About Us</a>
    </div>

    <!-- Desktop search -->
    <div class="lp-nav-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" placeholder="Search courses..." id="courseSearchNav">
    </div>

    <!-- Desktop actions -->
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

    <!-- Hamburger -->
    <button class="lp-mobile-toggle" id="mobileToggle" aria-label="Open menu">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
  </nav>

  <!-- Mobile overlay -->
  <div class="lp-mobile-overlay" id="mobileOverlay"></div>

  <!-- Mobile drawer -->
  <div class="lp-mobile-nav" id="mobileNav" role="dialog" aria-modal="true" aria-label="Navigation menu">
    <div class="lp-mobile-header">
      <a href="<?= url('/') ?>" class="lp-nav-logo" style="font-size:1.1rem;">
        <span style="width:30px;height:30px;font-size:.95rem;">🎓</span><?= e(appName()) ?>
      </a>
      <button class="lp-mobile-close" id="mobileClose" aria-label="Close menu">✕</button>
    </div>
    <div class="lp-mobile-body">
      <!-- Search inside mobile nav -->
      <div style="display:flex;align-items:center;background:#f3f4f6;border-radius:99px;padding:0 14px;gap:8px;height:40px;margin-bottom:8px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" placeholder="Search courses..." id="courseSearchMobile" style="border:none;background:transparent;outline:none;font-size:.85rem;color:#374151;width:100%;">
      </div>
      <a href="<?= url('/') ?>">🏠 Home</a>
      <a href="<?= url('/courses') ?>" style="color:#dc2626;">📚 Courses</a>
      <a href="<?= url('/about') ?>">ℹ️ About Us</a>
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
      <a href="<?= url('/account/login') ?>" class="btn-login" style="text-align:center;">Log In</a>
      <a href="<?= url('/account/register') ?>" class="btn-get-started" style="text-align:center;">Get Started Free →</a>
    </div>
  </div>
</header>

<!-- ════════════════════════════════════════
     BANNER
════════════════════════════════════════ -->
<section class="courses-banner">
  <h1>Explore All Courses</h1>
  <p>Browse our library of expert-led courses. Learn new skills and advance your career today.</p>
  <div class="courses-search-bar">
    <input type="text" placeholder="What do you want to learn today?" id="courseSearch">
    <button onclick="filterCourses()">Search</button>
  </div>
</section>

<!-- ════════════════════════════════════════
     COURSES GRID
════════════════════════════════════════ -->
<div class="courses-container">
  <div class="courses-header">
    <h2>Public Courses</h2>
    <span id="courseCount"><?= count($courses) ?> courses available</span>
  </div>

  <div class="courses-grid" id="coursesGrid">
    <?php if (empty($courses)): ?>
      <div class="empty-state">
        <span class="icon">📚</span>
        <h3>No Public Courses Yet</h3>
        <p>Public courses will appear here once organisations publish them. Sign in to access your organisation's private courses.</p>
        <a href="<?= url('/account/login') ?>" class="btn-green">Sign In to Access Courses</a>
      </div>
    <?php else: ?>
      <?php
      $gradients = [
        'linear-gradient(135deg,#0f172a,#1d4ed8)',
        'linear-gradient(135deg,#166534,#16a34a)',
        'linear-gradient(135deg,#4c1d95,#7c3aed)',
        'linear-gradient(135deg,#7c2d12,#ea580c)',
        'linear-gradient(135deg,#0f172a,#166534)',
        'linear-gradient(135deg,#7f1d1d,#dc2626)',
      ];
      $icons = ['💻','📊','🎨','📣','📈','🌱','🔬','🎵','✈️','🏗️'];
      $avColors = ['#dc2626','#16a34a','#7c3aed','#ea580c','#0891b2','#ca8a04'];

      foreach ($courses as $idx => $course):
        $grad = $gradients[$idx % count($gradients)];
        $icon = $icons[$idx % count($icons)];
        $avColor = $avColors[$idx % count($avColors)];
        $tutorName = trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? ''));
        if (!$tutorName) $tutorName = $course['trainer_name'] ?? ($course['created_by_name'] ?? 'Expert Instructor');
        $catName = $course['category_name'] ?? 'General';
        $orgName = $course['organisation_name'] ?? '';
        $feeKsh = (float) ($course['enrollment_fee_ksh'] ?? 0);
        $price = $feeKsh > 0 ? 'Ksh ' . number_format($feeKsh) : 'Free';
        $isFree = $feeKsh == 0;
        $coverImage = $course['cover_image'] ?? null;
        $description = $course['description'] ?? '';
        $videoUrl = $course['introduction_embed_url'] ?? ($course['introduction_video_url'] ?? null);
        $lessonCount = $course['lesson_count'] ?? null;
        $durationHours = $course['duration_hours'] ?? null;
        $courseId = $course['id'] ?? $idx;
      ?>
        <div class="c-card"
             data-cat="<?= e($catName) ?>"
             data-title="<?= e(strtolower($course['title'] ?? '')) ?>"
             style="cursor:default;">

          <!-- Image / thumbnail -->
          <div class="c-card-img" style="background:<?= $grad ?>;">
            <?php if ($coverImage): ?>
              <img src="<?= e(imageUrl($coverImage)) ?>" alt="<?= e($course['title'] ?? '') ?>" loading="lazy">
            <?php else: ?>
              <div class="c-card-img-placeholder"><?= $icon ?></div>
            <?php endif; ?>
            <!-- Play overlay hint (if has video) -->
            <?php if ($videoUrl): ?>
              <div class="c-card-play-overlay">
                <div class="c-card-play-btn">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="#dc2626"><path d="M5 3l14 9-14 9V3z"/></svg>
                </div>
              </div>
            <?php endif; ?>
            <!-- Badges -->
            <span class="c-card-badge <?= $isFree ? '' : 'paid' ?>"><?= $isFree ? 'Free' : 'Paid' ?></span>
            <?php if ($orgName): ?>
              <span class="c-card-org-badge"><?= e($orgName) ?></span>
            <?php endif; ?>
          </div>

          <!-- Card body -->
          <div class="c-card-body">
            <div class="c-card-cat"><?= e($catName) ?></div>
            <div class="c-card-title"><?= e($course['title'] ?? 'Untitled Course') ?></div>
            <?php if ($description): ?>
              <div class="c-card-desc"><?= e($description) ?></div>
            <?php endif; ?>
            <div class="c-card-tutor">
              <div class="c-card-av" style="background:<?= $avColor ?>;"><?= strtoupper(substr($tutorName,0,1)) ?></div>
              <?= e($tutorName) ?>
            </div>
            <?php if ($lessonCount || $durationHours): ?>
            <div class="c-card-meta">
              <?php if ($lessonCount): ?><span>📖 <?= $lessonCount ?> lessons</span><?php endif; ?>
              <?php if ($durationHours): ?><span>⏱ <?= $durationHours ?>h</span><?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Card footer -->
          <div class="c-card-footer">
            <div class="c-card-price <?= $isFree ? 'free' : '' ?>"><?= $price ?></div>
            <div class="c-card-actions">
              <button class="btn-view-more"
                      onclick="openCourseModal(<?= $courseId ?>)"
                      data-id="<?= $courseId ?>">
                View More
              </button>
              <a href="<?= url('/account/login') ?>" class="btn-enroll">Enroll</a>
            </div>
          </div>
        </div>

        <!-- Hidden data for modal -->
        <script type="application/json" id="course-data-<?= $courseId ?>">
        <?= json_encode([
          'id'          => $courseId,
          'title'       => $course['title'] ?? 'Untitled Course',
          'description' => $description,
          'category'    => $catName,
          'organisation'=> $orgName,
          'tutor'       => $tutorName,
          'tutorColor'  => $avColor,
          'price'       => $price,
          'isFree'      => $isFree,
          'cover'       => $coverImage ? imageUrl($coverImage) : null,
          'gradient'    => $grad,
          'icon'        => $icon,
          'videoUrl'    => $videoUrl,
          'lessons'     => $lessonCount,
          'hours'       => $durationHours,
          'loginUrl'    => url('/account/login'),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
        </script>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ════════════════════════════════════════
     COURSE DETAIL MODAL
════════════════════════════════════════ -->
<div class="c-modal-overlay" id="courseModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="c-modal" id="courseModalBox">
    <div class="c-modal-img" id="modalImgWrap" style="position:relative;">
      <button class="c-modal-close" onclick="closeModal()" aria-label="Close">✕</button>
      <span id="modalImgContent"></span>
    </div>
    <div class="c-modal-body">
      <div class="c-modal-cat" id="modalCat"></div>
      <div class="c-modal-title" id="modalTitle"></div>
      <div class="c-modal-meta" id="modalMeta"></div>
      <div class="c-modal-tutor" id="modalTutor"></div>
      <div class="c-modal-desc" id="modalDesc"></div>
      <div class="c-modal-video" id="modalVideo" style="display:none;">
        <h4>📽️ Course Preview</h4>
        <iframe id="modalVideoIframe" src="" allowfullscreen></iframe>
      </div>
      <div class="c-modal-price-row" id="modalPriceRow"></div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════
     FOOTER
════════════════════════════════════════ -->
<footer class="lp-footer">
  <div class="lp-footer-inner">
    <div class="lp-footer-grid">
      <div class="lp-footer-brand">
        <div class="lp-nav-logo" style="color:#fff;font-size:1.1rem;"><span style="background:#dc2626;">🎓</span><?= e(appName()) ?></div>
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
          <li><a href="<?= url('/account/forgot-password') ?>">Forgot Password</a></li>
        </ul>
      </div>
      <div class="lp-footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="#">Help Center</a></li>
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
  </div>
</footer>

<script>
/* ── Nav hamburger ── */
(function(){
  var toggle = document.getElementById('mobileToggle');
  var nav    = document.getElementById('mobileNav');
  var overlay= document.getElementById('mobileOverlay');
  var closeBtn = document.getElementById('mobileClose');
  function openMenu(){ nav.classList.add('open'); overlay.classList.add('open'); document.body.style.overflow='hidden'; }
  function closeMenu(){ nav.classList.remove('open'); overlay.classList.remove('open'); document.body.style.overflow=''; }
  toggle.addEventListener('click', openMenu);
  closeBtn.addEventListener('click', closeMenu);
  overlay.addEventListener('click', closeMenu);
})();

/* ── Get-Started dropdown ── */
(function(){
  var btn = document.getElementById('signupDropBtn');
  var drop = document.getElementById('signupDrop');
  if(btn) btn.addEventListener('click', function(e){
    e.stopPropagation();
    drop.classList.toggle('open');
  });
  document.addEventListener('click', function(e){
    if(drop && !drop.contains(e.target)) drop.classList.remove('open');
  });
})();

/* ── Search filter ── */
function filterCourses(){
  var inputs = ['courseSearch','courseSearchNav','courseSearchMobile'];
  var val = '';
  inputs.forEach(function(id){ var el=document.getElementById(id); if(el&&el.value.trim()) val=el.value.trim().toLowerCase(); });
  var cards = document.querySelectorAll('.c-card');
  var visible = 0;
  cards.forEach(function(card){
    var title = card.getAttribute('data-title') || '';
    if(!val || title.indexOf(val) > -1){ card.style.display=''; visible++; }
    else { card.style.display='none'; }
  });
  var c = document.getElementById('courseCount');
  if(c) c.textContent = visible + ' courses found';
}
['courseSearch','courseSearchNav','courseSearchMobile'].forEach(function(id){
  var el = document.getElementById(id);
  if(el){ el.addEventListener('keyup', filterCourses); el.addEventListener('input', filterCourses); }
});

/* ── Course Modal ── */
function openCourseModal(id){
  var raw = document.getElementById('course-data-' + id);
  if(!raw) return;
  var d;
  try { d = JSON.parse(raw.textContent); } catch(e){ return; }

  // Image
  var imgWrap = document.getElementById('modalImgWrap');
  var imgContent = document.getElementById('modalImgContent');
  imgWrap.style.background = d.gradient;
  if(d.cover){
    imgContent.innerHTML = '<img src="'+d.cover+'" alt="'+d.title+'" style="width:100%;height:100%;object-fit:cover;">';
  } else {
    imgContent.innerHTML = '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:5rem;">'+d.icon+'</div>';
  }

  // Category + title
  document.getElementById('modalCat').textContent = d.category;
  document.getElementById('modalTitle').textContent = d.title;

  // Meta
  var metaItems = [];
  if(d.organisation) metaItems.push('<div class="c-modal-meta-item">🏢 '+escHtml(d.organisation)+'</div>');
  if(d.lessons)      metaItems.push('<div class="c-modal-meta-item">📖 '+d.lessons+' lessons</div>');
  if(d.hours)        metaItems.push('<div class="c-modal-meta-item">⏱ '+d.hours+'h</div>');
  document.getElementById('modalMeta').innerHTML = metaItems.join('');

  // Tutor
  document.getElementById('modalTutor').innerHTML =
    '<div class="c-modal-tutor-av" style="background:'+d.tutorColor+';">'+d.tutor.charAt(0).toUpperCase()+'</div>'+
    '<div><div class="c-modal-tutor-name">'+escHtml(d.tutor)+'</div><div class="c-modal-tutor-label">Instructor</div></div>';

  // Description
  document.getElementById('modalDesc').textContent = d.description || 'No description available for this course.';

  // Video
  var videoWrap = document.getElementById('modalVideo');
  var iframe = document.getElementById('modalVideoIframe');
  if(d.videoUrl){
    iframe.src = d.videoUrl;
    videoWrap.style.display = '';
  } else {
    iframe.src = '';
    videoWrap.style.display = 'none';
  }

  // Price row
  document.getElementById('modalPriceRow').innerHTML =
    '<div class="c-modal-price '+(d.isFree?'free':'')+'">'+escHtml(d.price)+'</div>'+
    '<a href="'+d.loginUrl+'" class="btn-enroll-lg">'+
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg>'+
      (d.isFree ? 'Enroll for Free' : 'Login to Enroll')+
    '</a>';

  // Open modal
  document.getElementById('courseModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(){
  document.getElementById('courseModal').classList.remove('open');
  document.getElementById('modalVideoIframe').src = '';
  document.body.style.overflow = '';
}

document.getElementById('courseModal').addEventListener('click', function(e){
  if(e.target === this) closeModal();
});
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });

function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
</body>
</html>
