<?php
/** Public LMS landing. Requires $roles, $needsSetup. Words, logos and featured courses come from Super Admin → Homepage. */
use App\Services\HomepageContent;

$hp = static fn(string $key): string => e(HomepageContent::get($key));
$homeCourses = HomepageContent::featuredCourses();
$homeLogos = HomepageContent::logos();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php require __DIR__ . "/../partials/pwa-head.php"; ?>
  <title><?= e(appName()) ?> | <?= $hp('hero_title_1') ?> <?= $hp('hero_title_2') ?></title>
  <meta name="description" content="Join thousands of learners on <?= e(appName()) ?>. Expert-led courses, certificates, and real-world attachment opportunities."/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tailwind.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/srms.css') ?>">
  <style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'Inter',sans-serif;background:#fff;color:#111827;}
    a{text-decoration:none;color:inherit;}

    /* ── NAV ── */
    .lp-nav{
      position:sticky;top:0;z-index:200;
      background:#fff;border-bottom:1.5px solid #f1f1f1;
      display:flex;align-items:center;justify-content:space-between;
      padding:0 5%;height:68px;gap:16px;
    }
    .lp-nav-logo{display:flex;align-items:center;gap:10px;font-family:'Manrope',sans-serif;font-size:1.2rem;font-weight:800;color:#111;white-space:nowrap;flex-shrink:0;}
    .lp-nav-logo span{background:#dc2626;color:#fff;border-radius:10px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
    /* Desktop centre links */
    .lp-nav-center{display:flex;align-items:center;gap:6px;flex:1;justify-content:center;}
    .lp-nav-center a{font-size:.85rem;font-weight:600;color:#374151;padding:.4rem .75rem;border-radius:8px;transition:color .18s,background .18s;white-space:nowrap;}
    .lp-nav-center a:hover{color:#dc2626;background:#fef2f2;}
    .lp-nav-center a.active{color:#dc2626;background:#fef2f2;}
    /* Search */
    .lp-nav-search{display:flex;align-items:center;background:#f3f4f6;border-radius:99px;padding:0 14px;gap:8px;height:38px;width:200px;flex-shrink:0;}
    .lp-nav-search input{border:none;background:transparent;outline:none;font-size:.82rem;color:#374151;width:100%;}
    /* Actions */
    .lp-nav-actions{display:flex;align-items:center;gap:10px;flex-shrink:0;}
    .btn-login{font-size:.82rem;font-weight:700;color:#374151;padding:.45rem .9rem;border-radius:99px;border:1.5px solid #e5e7eb;transition:all .18s;display:inline-block;white-space:nowrap;}
    .btn-login:hover{border-color:#dc2626;color:#dc2626;}
    .btn-get-started{font-size:.82rem;font-weight:700;background:#dc2626;color:#fff;padding:.45rem 1.1rem;border-radius:99px;transition:all .18s;border:none;cursor:pointer;display:inline-block;white-space:nowrap;}
    .btn-get-started:hover{background:#b91c1c;transform:translateY(-1px);}
    /* Dropdown */
    .lp-dropdown{position:relative;}
    .lp-dropdown-menu{display:none;position:absolute;top:calc(100% + 10px);right:0;background:#fff;border:1.5px solid #f1f5f9;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.14);min-width:270px;z-index:400;overflow:hidden;}
    .lp-dropdown.open .lp-dropdown-menu{display:block;}
    .lp-dropdown-menu a{display:flex;align-items:center;gap:10px;padding:.65rem 1.1rem;font-size:.82rem;font-weight:600;color:#1e293b;border-bottom:1px solid #f8fafc;transition:background .12s;}
    .lp-dropdown-menu a:last-child{border-bottom:none;}
    .lp-dropdown-menu a:hover{background:#fef2f2;color:#dc2626;}
    .lp-dropdown-menu a.green:hover{background:#f0fdf4;color:#15803d;}
    .lp-drop-label{padding:.4rem 1.1rem .2rem;font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;background:#f8fafc;border-bottom:1px solid #f1f5f9;}
    .drop-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
    /* Hamburger */
    .lp-mobile-toggle{display:none;background:none;border:none;cursor:pointer;color:#374151;padding:.4rem;border-radius:8px;transition:background .18s;flex-shrink:0;}
    .lp-mobile-toggle:hover{background:#f3f4f6;}
    .lp-mobile-toggle svg{display:block;}
    /* Mobile drawer */
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

    /* ── HERO ── */
    .lp-hero{
      padding:80px 5% 80px;
      display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;
      max-width:1200px;margin:0 auto;
    }
    .lp-badge{display:inline-flex;align-items:center;gap:6px;background:#fef2f2;color:#dc2626;font-size:.75rem;font-weight:700;padding:.35rem .9rem;border-radius:99px;margin-bottom:20px;letter-spacing:.03em;}
    .lp-badge::before{content:'#1';background:#dc2626;color:#fff;border-radius:99px;padding:1px 6px;font-size:.65rem;font-weight:900;}
    .lp-hero h1{font-family:'Manrope',sans-serif;font-size:3.2rem;font-weight:900;line-height:1.12;color:#111827;margin-bottom:20px;}
    .lp-hero h1 em{color:#16a34a;font-style:normal;}
    .lp-hero p{font-size:1.05rem;color:#6b7280;line-height:1.7;margin-bottom:32px;max-width:480px;}
    .lp-hero-btns{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:32px;}
    .btn-primary{background:#dc2626;color:#fff;font-weight:700;font-size:.95rem;padding:.8rem 1.8rem;border-radius:99px;display:inline-flex;align-items:center;gap:8px;transition:all .2s;}
    .btn-primary:hover{background:#b91c1c;transform:translateY(-2px);box-shadow:0 8px 20px rgba(220,38,38,.3);}
    .btn-secondary{background:#fff;color:#111827;font-weight:700;font-size:.95rem;padding:.8rem 1.8rem;border-radius:99px;border:2px solid #e5e7eb;display:inline-flex;align-items:center;gap:8px;transition:all .2s;}
    .btn-secondary:hover{border-color:#16a34a;color:#16a34a;}
    .lp-trust{display:flex;align-items:center;gap:12px;}
    .lp-avatars{display:flex;}
    .lp-avatars span{width:34px;height:34px;border-radius:50%;border:3px solid #fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;margin-left:-10px;color:#fff;}
    .lp-avatars span:first-child{margin-left:0;}
    .lp-trust-text{font-size:.82rem;font-weight:600;color:#6b7280;}
    .lp-trust-text strong{color:#111827;}
    .lp-stars{color:#f59e0b;font-size:.9rem;letter-spacing:.1em;}

    /* ── HERO VISUAL ── */
    .lp-hero-visual{position:relative;}
    .lp-hero-card{
      background:linear-gradient(135deg,#0f172a 0%,#166534 60%,#16a34a 100%);
      border-radius:28px;overflow:hidden;
      height:460px;display:flex;align-items:center;justify-content:center;
      box-shadow:0 40px 80px rgba(0,0,0,.2);
      position:relative;
    }
    .lp-hero-card-inner{text-align:center;z-index:1;}
    .lp-hero-card-inner .big-icon{font-size:7rem;display:block;margin-bottom:16px;}
    .lp-hero-card-inner p{color:rgba(255,255,255,.7);font-size:.85rem;font-weight:600;}
    .lp-hero-card-inner h3{color:#fff;font-size:1.5rem;font-weight:800;margin-bottom:8px;}
    /* circle decorations */
    .lp-hero-card::before{content:'';position:absolute;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.05);top:-60px;right:-60px;}
    .lp-hero-card::after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.04);bottom:-40px;left:-40px;}
    /* Floating badges */
    .lp-float{position:absolute;background:#fff;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.12);padding:12px 16px;display:flex;align-items:center;gap:10px;z-index:10;}
    .lp-float-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
    .lp-float-val{font-size:1.1rem;font-weight:900;color:#111827;line-height:1.1;}
    .lp-float-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;}
    .lp-float-1{top:30px;left:-24px;animation:floatUp 3s ease-in-out infinite;}
    .lp-float-2{bottom:60px;right:-24px;animation:floatUp 3s ease-in-out infinite .8s;}
    .lp-float-3{bottom:20px;left:20px;animation:floatUp 3s ease-in-out infinite 1.6s;}
    @keyframes floatUp{0%,100%{transform:translateY(0);}50%{transform:translateY(-6px);}}

    /* ── SECTION HEADERS ── */
    .lp-section{padding:80px 5%;}
    .lp-section-inner{max-width:1200px;margin:0 auto;}
    .lp-section-top{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:40px;flex-wrap:wrap;gap:12px;}
    .lp-section-label{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#dc2626;margin-bottom:8px;}
    .lp-section-title{font-family:'Manrope',sans-serif;font-size:2rem;font-weight:900;color:#111827;line-height:1.2;}
    .lp-section-sub{font-size:.9rem;color:#6b7280;margin-top:6px;}
    .lp-view-all{font-size:.83rem;font-weight:700;color:#dc2626;display:flex;align-items:center;gap:4px;white-space:nowrap;}
    .lp-view-all:hover{color:#b91c1c;}

    /* ── STATS ── */
    .lp-stats{background:linear-gradient(135deg,#0f172a 0%,#166534 100%);padding:56px 5%;}
    .lp-stats-inner{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr);gap:0;text-align:center;}
    .lp-stat-item{padding:24px;border-right:1px solid rgba(255,255,255,.12);}
    .lp-stat-item:last-child{border-right:none;}
    .lp-stat-icon{font-size:2rem;display:block;margin-bottom:10px;}
    .lp-stat-val{font-family:'Manrope',sans-serif;font-size:2.4rem;font-weight:900;color:#fff;line-height:1;}
    .lp-stat-label{font-size:.78rem;font-weight:600;color:rgba(255,255,255,.65);margin-top:6px;text-transform:uppercase;letter-spacing:.06em;}

    /* ── HOW IT WORKS / FEATURES ── */
    .lp-features{background:#f9fafb;padding:80px 5%;}
    .lp-features-inner{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;}
    .lp-feature-device{
      background:linear-gradient(135deg,#0f172a,#166534);
      border-radius:24px;height:420px;display:flex;align-items:center;justify-content:center;
      box-shadow:0 30px 60px rgba(0,0,0,.15);position:relative;overflow:hidden;
    }
    .lp-feature-device::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}
    .lp-device-inner{text-align:center;z-index:1;padding:32px;}
    .lp-device-inner .icon{font-size:5rem;display:block;margin-bottom:20px;}
    .lp-device-badge{display:inline-block;background:rgba(255,255,255,.15);color:#fff;border-radius:8px;padding:8px 16px;font-size:.82rem;font-weight:700;margin-bottom:12px;backdrop-filter:blur(10px);}
    .lp-feature-list{list-style:none;display:flex;flex-direction:column;gap:22px;}
    .lp-feature-item{display:flex;align-items:flex-start;gap:16px;}
    .lp-feature-item-icon{width:48px;height:48px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
    .lp-feature-item-text h4{font-size:1rem;font-weight:700;color:#111827;margin-bottom:4px;}
    .lp-feature-item-text p{font-size:.85rem;color:#6b7280;line-height:1.55;}

    /* ── PORTALS ── */
    .lp-portals{padding:80px 5%;background:#fff;}
    .lp-portals-inner{max-width:1200px;margin:0 auto;}
    .lp-portals-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:40px;}
    .lp-portal-card{
      border-radius:18px;padding:28px 24px;
      display:flex;flex-direction:column;gap:12px;
      transition:all .2s;cursor:pointer;
    }
    .lp-portal-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.08);}
    .lp-portal-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;}
    .lp-portal-card h3{font-size:1rem;font-weight:800;color:#111827;line-height:1.3;}
    .lp-portal-card p{font-size:.82rem;color:#6b7280;line-height:1.55;}
    .lp-portal-card .lp-card-link{font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;margin-top:auto;}

    /* ── TRUST LOGOS ── */
    .lp-trust-bar{background:#f9fafb;padding:40px 5%;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;}
    .lp-trust-bar-inner{max-width:1200px;margin:0 auto;text-align:center;}
    .lp-trust-bar p{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#9ca3af;margin-bottom:24px;}
    .lp-trust-logos{display:flex;align-items:center;justify-content:center;gap:48px;flex-wrap:wrap;}
    .lp-trust-logo{font-size:1.1rem;font-weight:800;color:#9ca3af;letter-spacing:.05em;transition:color .18s;}
    .lp-trust-logo:hover{color:#374151;}
    /* Uploaded partner logos show in their own colours. */
    .lp-trust-logo-img{height:44px;width:auto;max-width:160px;object-fit:contain;}
    /* Popular courses — Super Admin picks them under Homepage. */
    .lp-courses{background:#f9fafb;padding:64px 5%;}
    .lp-courses-inner{max-width:1200px;margin:0 auto;}
    .lp-courses-head{text-align:center;max-width:640px;margin:0 auto 36px;}
    .lp-courses-head p{color:#6b7280;font-size:.95rem;line-height:1.6;margin-top:10px;}
    .lp-course-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
    .lp-course{display:flex;flex-direction:column;background:#fff;border:1px solid #eef0f3;border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 1px 3px rgba(0,0,0,.04);transition:transform .18s,box-shadow .18s;}
    .lp-course:hover{transform:translateY(-3px);box-shadow:0 12px 24px rgba(0,0,0,.08);}
    .lp-course-cover{aspect-ratio:16/9;width:100%;object-fit:cover;display:block;background:#e5e7eb;}
    .lp-course-cover-fallback{aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#166534,#0f172a);color:#fff;font-family:'Manrope',sans-serif;font-size:2.4rem;font-weight:900;}
    .lp-course-body{padding:16px;display:flex;flex-direction:column;gap:6px;flex:1;}
    .lp-course-cat{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#dc2626;}
    .lp-course-title{font-family:'Manrope',sans-serif;font-size:1.02rem;font-weight:800;color:#111827;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.6em;}
    .lp-course-org{font-size:.78rem;color:#6b7280;}
    .lp-course-foot{margin-top:auto;padding-top:10px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f1f5f9;font-size:.85rem;font-weight:800;color:#166534;}
    .lp-courses-more{text-align:center;margin-top:32px;}
    .lp-course-new{position:absolute;top:12px;left:12px;z-index:1;background:#dc2626;color:#fff;font-size:.65rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;padding:3px 9px;border-radius:5px;box-shadow:0 2px 6px rgba(220,38,38,.35);}
    @media(max-width:1024px){.lp-course-grid{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:560px){.lp-course-grid{grid-template-columns:1fr;}}

    /* ── FOOTER ── */
    .lp-footer{background:#0f172a;color:#fff;padding:64px 5% 28px;}
    .lp-footer-inner{max-width:1200px;margin:0 auto;}
    .lp-footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:48px;margin-bottom:48px;}
    .lp-footer-brand .lp-nav-logo{margin-bottom:16px;}
    .lp-footer-brand p{font-size:.85rem;color:rgba(255,255,255,.55);line-height:1.65;max-width:260px;margin-bottom:24px;}
    .lp-footer-social{display:flex;gap:10px;}
    .lp-footer-social a{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:800;color:rgba(255,255,255,.7);transition:all .18s;}
    .lp-footer-social a:hover{background:#dc2626;color:#fff;}
    .lp-footer-col h4{font-size:.83rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.9);margin-bottom:20px;}
    .lp-footer-col ul{list-style:none;display:flex;flex-direction:column;gap:10px;}
    .lp-footer-col ul li a{font-size:.85rem;color:rgba(255,255,255,.5);font-weight:500;transition:color .15s;}
    .lp-footer-col ul li a:hover{color:#fff;}
    .lp-footer-col ul li a.red{color:#f87171;}
    .lp-footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
    .lp-footer-bottom p{font-size:.8rem;color:rgba(255,255,255,.4);}

    /* ── RESPONSIVE ── */
    @media(max-width:1024px){
      .lp-footer-grid{grid-template-columns:1fr 1fr;}
      .lp-nav-search{display:none;}
    }
    @media(max-width:768px){
      .lp-hero{grid-template-columns:1fr;gap:40px;padding:48px 5%;}
      .lp-hero h1{font-size:2.2rem;}
      .lp-hero-visual{display:none;}
      .lp-nav-center,.lp-nav-actions{display:none;}
      .lp-mobile-toggle{display:flex;align-items:center;}
      .lp-stats-inner{grid-template-columns:repeat(2,1fr);}
      .lp-stat-item{border-right:none;border-bottom:1px solid rgba(255,255,255,.12);}
      .lp-features-inner{grid-template-columns:1fr;}
      .lp-feature-device{display:none;}
      .lp-portals-grid{grid-template-columns:1fr 1fr;}
      .lp-footer-grid{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:480px){
      .lp-portals-grid{grid-template-columns:1fr;}
      .lp-footer-grid{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>

<!-- ════════ NAVBAR ════════ -->
<header style="position:relative;">
  <nav class="lp-nav">
    <!-- Logo -->
    <a href="<?= url('/') ?>" class="lp-nav-logo">
      <span>🎓</span><?= e(appName()) ?>
    </a>
    <!-- Desktop centre links -->
    <div class="lp-nav-center">
      <a href="<?= url('/') ?>" class="active">Home</a>
      <a href="<?= url('/courses') ?>">Courses</a>
      <a href="<?= url('/about') ?>">About Us</a>
    </div>
    <!-- Desktop search -->
    <div class="lp-nav-search">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" placeholder="Search courses..." onclick="window.location='<?= url('/courses') ?>'" readonly style="cursor:pointer;">
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
      <a href="<?= url('/') ?>">🏠 Home</a>
      <a href="<?= url('/courses') ?>">📚 Courses</a>
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
      <a href="<?= url('/account/login') ?>" class="btn-login">Log In</a>
      <a href="<?= url('/account/register') ?>" class="btn-get-started">Get Started Free →</a>
    </div>
  </div>
</header>

<script>
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
(function(){
  var btn = document.getElementById('signupDropBtn');
  var drop = document.getElementById('signupDrop');
  if(btn) btn.addEventListener('click', function(e){ e.stopPropagation(); drop.classList.toggle('open'); });
  document.addEventListener('click', function(e){ if(drop&&!drop.contains(e.target)) drop.classList.remove('open'); });
})();
</script>

<!-- ════════ HERO ════════ -->
<section style="background:#fff;">
  <div class="lp-hero">
    <!-- Left -->
    <div>
      <div class="lp-badge"><?= $hp('hero_badge') ?></div>
      <h1><?= $hp('hero_title_1') ?><br><em><?= $hp('hero_title_2') ?></em></h1>
      <p><?= $hp('hero_text') ?></p>
      <div class="lp-hero-btns">
        <a href="<?= url('/courses') ?>" class="btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 3l14 9-14 9V3z"/></svg>
          <?= $hp('hero_button_1') ?>
        </a>
        <a href="<?= url('/about') ?>" class="btn-secondary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
          <?= $hp('hero_button_2') ?>
        </a>
      </div>
      <div class="lp-trust">
        <div class="lp-avatars">
          <span style="background:#dc2626;">A</span>
          <span style="background:#16a34a;">B</span>
          <span style="background:#0f172a;">C</span>
          <span style="background:#7c3aed;">D</span>
          <span style="background:#ea580c;">+</span>
        </div>
        <div>
          <div class="lp-stars">★★★★★</div>
          <div class="lp-trust-text"><?= $hp('hero_trust_prefix') ?> <strong><?= $hp('hero_trust_count') ?></strong> <?= $hp('hero_trust_suffix') ?></div>
        </div>
      </div>
    </div>
    <!-- Right Visual -->
    <div class="lp-hero-visual">
      <?php
        $heroImg = null;
        try { $heroImg = \App\Models\StoreSetting::get('hero_image'); } catch (\Throwable $e) {}
      ?>
      <div class="lp-hero-card" <?php if ($heroImg): ?>style="background:none;padding:0;overflow:hidden;"<?php endif; ?>>
        <?php if ($heroImg): ?>
          <img src="<?= e(imageUrl($heroImg)) ?>"
               alt="Hero image"
               style="width:100%;height:100%;object-fit:cover;border-radius:28px;display:block;" />
        <?php else: ?>
          <div class="lp-hero-card-inner">
            <span class="big-icon">🎓</span>
            <h3><?= e(appName()) ?></h3>
            <p><?= $hp('hero_card_tagline') ?></p>
            <div style="display:flex;gap:8px;justify-content:center;margin-top:20px;flex-wrap:wrap;">
              <span style="background:rgba(255,255,255,.15);color:#fff;padding:5px 12px;border-radius:99px;font-size:.75rem;font-weight:600;backdrop-filter:blur(6px);">📚 Courses</span>
              <span style="background:rgba(255,255,255,.15);color:#fff;padding:5px 12px;border-radius:99px;font-size:.75rem;font-weight:600;backdrop-filter:blur(6px);">🏆 Certificates</span>
              <span style="background:rgba(255,255,255,.15);color:#fff;padding:5px 12px;border-radius:99px;font-size:.75rem;font-weight:600;backdrop-filter:blur(6px);">🤝 Attachments</span>
            </div>
            <p style="color:rgba(255,255,255,.5);font-size:.72rem;margin-top:20px;">
              ⬆ Upload a hero photo in Admin → Settings
            </p>
          </div>
        <?php endif; ?>
      </div>
      <!-- Floating badges -->
      <div class="lp-float lp-float-1">
        <div class="lp-float-icon" style="background:#fef2f2;">🎓</div>
        <div><div class="lp-float-val"><?= $hp('float_1_value') ?></div><div class="lp-float-label"><?= $hp('float_1_label') ?></div></div>
      </div>
      <div class="lp-float lp-float-2">
        <div class="lp-float-icon" style="background:#f0fdf4;">⭐</div>
        <div><div class="lp-float-val"><?= $hp('float_2_value') ?></div><div class="lp-float-label"><?= $hp('float_2_label') ?></div></div>
      </div>
      <div class="lp-float lp-float-3">
        <div class="lp-float-icon" style="background:#fff7ed;">🔓</div>
        <div><div class="lp-float-val" style="font-size:.85rem;"><?= $hp('float_3_value') ?></div><div class="lp-float-label"><?= $hp('float_3_label') ?></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ════════ POPULAR COURSES (chosen by Super Admin → Homepage) ════════ -->
<?php if ($homeCourses): ?>
<section class="lp-courses" id="popular-courses">
  <div class="lp-courses-inner">
    <div class="lp-courses-head">
      <div class="lp-section-label"><?= $hp('courses_label') ?></div>
      <div class="lp-section-title"><?= $hp('courses_title') ?></div>
      <p><?= $hp('courses_text') ?></p>
    </div>
    <div class="lp-course-grid">
      <?php foreach ($homeCourses as $course):
        $cover = (string) ($course['cover_image'] ?? '');
        $hasCover = $cover !== '' && preg_match('/\.(jpe?g|png|webp|gif)$/i', $cover);
        $fee = (float) ($course['enrollment_fee_ksh'] ?? 0);
      ?>
        <a class="lp-course" href="<?= url('/account/courses/' . (int) $course['id']) ?>" style="position:relative;">
          <?php if ((strtotime((string) ($course['created_at'] ?? '')) ?: 0) > strtotime('-14 days')): ?>
            <span class="lp-course-new">New</span>
          <?php endif; ?>
          <?php if ($hasCover): ?>
            <img class="lp-course-cover" src="<?= e(imageUrl($cover)) ?>" alt="<?= e($course['title']) ?>" loading="lazy">
          <?php else: ?>
            <div class="lp-course-cover-fallback" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim((string) $course['title']), 0, 1))) ?></div>
          <?php endif; ?>
          <div class="lp-course-body">
            <?php if (!empty($course['category_name'])): ?><div class="lp-course-cat"><?= e($course['category_name']) ?></div><?php endif; ?>
            <div class="lp-course-title"><?= e($course['title']) ?></div>
            <div class="lp-course-org"><?= e($course['organisation_name']) ?></div>
            <div class="lp-course-foot"><span><?= $fee > 0 ? 'Ksh ' . number_format($fee) : 'Free' ?></span><span>View →</span></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="lp-courses-more">
      <a href="<?= url('/courses') ?>" class="btn-secondary"><?= $hp('courses_button') ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════ HOW IT WORKS ════════ -->
<section class="lp-features">
  <div class="lp-features-inner">
    <div class="lp-feature-device">
      <div class="lp-device-inner">
        <span class="icon">💻</span>
        <div class="lp-device-badge"><?= $hp('device_badge') ?></div>
        <h3 style="color:#fff;font-family:'Manrope',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:8px;"><?= $hp('device_title') ?></h3>
        <p style="color:rgba(255,255,255,.65);font-size:.85rem;max-width:260px;line-height:1.6;"><?= $hp('device_text') ?></p>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:24px;">
          <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:10px 16px;color:#fff;font-size:.75rem;font-weight:700;backdrop-filter:blur(8px);">📱 Mobile</div>
          <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:10px 16px;color:#fff;font-size:.75rem;font-weight:700;backdrop-filter:blur(8px);">💻 Desktop</div>
          <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:10px 16px;color:#fff;font-size:.75rem;font-weight:700;backdrop-filter:blur(8px);">📲 Tablet</div>
        </div>
      </div>
    </div>
    <div>
      <div class="lp-section-label" style="margin-bottom:10px;"><?= $hp('features_label') ?></div>
      <div class="lp-section-title" style="margin-bottom:16px;"><?= $hp('features_title') ?></div>
      <p style="color:#6b7280;font-size:.95rem;line-height:1.7;margin-bottom:36px;"><?= $hp('features_text') ?></p>
      <ul class="lp-feature-list">
        <li class="lp-feature-item">
          <div class="lp-feature-item-icon" style="background:#fef2f2;color:#dc2626;">📲</div>
          <div class="lp-feature-item-text">
            <h4><?= $hp('feature_1_title') ?></h4>
            <p><?= $hp('feature_1_text') ?></p>
          </div>
        </li>
        <li class="lp-feature-item">
          <div class="lp-feature-item-icon" style="background:#f0fdf4;color:#16a34a;">⬇️</div>
          <div class="lp-feature-item-text">
            <h4><?= $hp('feature_2_title') ?></h4>
            <p><?= $hp('feature_2_text') ?></p>
          </div>
        </li>
        <li class="lp-feature-item">
          <div class="lp-feature-item-icon" style="background:#0f172a;color:#fff;">🏆</div>
          <div class="lp-feature-item-text">
            <h4><?= $hp('feature_3_title') ?></h4>
            <p><?= $hp('feature_3_text') ?></p>
          </div>
        </li>
      </ul>
      <a href="<?= url('/account/register') ?>" class="btn-primary" style="margin-top:32px;display:inline-flex;"><?= $hp('features_button') ?></a>
    </div>
  </div>
</section>


<!-- ════════ TRUSTED BY ════════ -->
<section class="lp-trust-bar">
  <div class="lp-trust-bar-inner">
    <p><?= $hp('trusted_title') ?></p>
    <div class="lp-trust-logos">
      <?php if ($homeLogos): ?>
        <?php foreach ($homeLogos as $logo): ?>
          <img class="lp-trust-logo-img" src="<?= e(imageUrl($logo['image'])) ?>" alt="<?= e($logo['name'] ?: 'Partner logo') ?>" title="<?= e($logo['name'] ?? '') ?>" loading="lazy">
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach (HomepageContent::DEFAULT_LOGO_NAMES as $logoName): ?>
          <span class="lp-trust-logo"><?= e($logoName) ?></span>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ════════ FOOTER ════════ -->
<footer class="lp-footer">
  <div class="lp-footer-inner">
    <div class="lp-footer-grid">
      <div class="lp-footer-brand">
        <div class="lp-nav-logo" style="color:#fff;">
          <span>🎓</span><?= e(appName()) ?>
        </div>
        <p><?= $hp('footer_text') ?></p>
        <div class="lp-footer-social">
          <a href="#">f</a>
          <a href="#">in</a>
          <a href="#">t</a>
          <a href="#">yt</a>
        </div>
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
      <p><?= $hp('footer_bottom') ?></p>
    </div>
  </div>
</footer>

</body>
</html>
