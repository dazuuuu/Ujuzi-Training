<?php
/**
 * Styles for page-builder sections. Everything is scoped under .pb so the
 * sections also sit safely inside the portal layout (sign up / sign in).
 */
$pbSite = $pbSite ?? \App\Services\PageBuilder::site();
$pbAccent = $pbSite['accent'] ?: '#006b3f';
?>
<style>
  .pb{--pb-accent:<?= e($pbAccent) ?>;--pb-accent-soft:color-mix(in srgb,var(--pb-accent) 10%,#fff);font-family:'Inter',system-ui,sans-serif;color:#111827;}
  .pb *,.pb *::before,.pb *::after{box-sizing:border-box;}
  .pb a{text-decoration:none;color:inherit;}
  .pb img{max-width:100%;}

  .pb-sec{position:relative;scroll-margin-top:72px;}
  .pb-wrap{margin:0 auto;padding:0 max(16px,4%);}
  .pb-w-narrow{max-width:760px;}.pb-w-normal{max-width:1100px;}.pb-w-wide{max-width:1320px;}.pb-w-full{max-width:none;}
  .pb-pad-none{padding:0;}.pb-pad-sm{padding:24px 0;}.pb-pad-md{padding:48px 0;}.pb-pad-lg{padding:72px 0;}.pb-pad-xl{padding:104px 0;}
  .pb-bg-white{background:#fff;}.pb-bg-light{background:#f7f8f7;}
  .pb-bg-mint{background:radial-gradient(1200px 400px at 80% -10%,var(--pb-accent-soft) 0%,#fff 60%);}
  .pb-bg-green{background:linear-gradient(145deg,color-mix(in srgb,var(--pb-accent) 70%,#000),var(--pb-accent));color:#fff;}
  .pb-bg-dark{background:linear-gradient(135deg,#0f172a 0%,color-mix(in srgb,var(--pb-accent) 60%,#0f172a) 100%);color:#fff;}
  .pb-align-center{text-align:center;}
  .pb-align-center .pb-head,.pb-align-center .pb-text{margin-left:auto;margin-right:auto;}
  .pb-align-center .pb-actions{justify-content:center;}
  .pb-ts-sm{font-size:.92rem;}.pb-ts-md{font-size:1rem;}.pb-ts-lg{font-size:1.1rem;}

  /* Type: sizes use em so the section's text size scales everything. */
  .pb-label{font-size:.72em;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--pb-accent);}
  .pb-dark .pb-label{color:rgba(255,255,255,.8);}
  .pb-title{margin-top:.25em;font-size:clamp(1.35em,3vw,1.9em);font-weight:900;line-height:1.2;overflow-wrap:anywhere;}
  .pb-text{margin-top:.5em;max-width:42rem;font-size:.95em;line-height:1.65;color:#4b5563;white-space:pre-line;overflow-wrap:anywhere;}
  .pb-dark .pb-text{color:rgba(255,255,255,.82);}
  .pb-head{max-width:46rem;margin-bottom:1.4em;}

  .pb-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:1.6em;}
  .pb-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;padding:0 1.35rem;border-radius:14px;font-size:.93em;font-weight:800;transition:transform .08s ease,filter .15s ease;border:1.5px solid transparent;}
  .pb-btn:active{transform:scale(.97);}
  .pb .pb-btn-main{background:var(--pb-accent);color:#fff;}
  .pb-btn-main:hover{filter:brightness(.9);}
  .pb .pb-btn-alt{background:#fff;color:#111827;border-color:#e5e7eb;}
  .pb .pb-btn-alt:hover{border-color:var(--pb-accent);color:var(--pb-accent);}
  .pb .pb-dark .pb-btn-main{background:#fff;color:var(--pb-accent);}
  .pb .pb-dark .pb-btn-alt{background:transparent;color:#fff;border-color:rgba(255,255,255,.55);}

  /* Hero */
  .pb-hero-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:40px;align-items:center;}
  .pb-hero-grid.is-left > .pb-hero-visual{order:-1;}
  .pb-hero-grid.is-solo{grid-template-columns:1fr;}
  .pb-badge{display:inline-block;margin-bottom:14px;padding:.3rem .8rem;border-radius:99px;background:var(--pb-accent-soft);color:var(--pb-accent);font-size:.74em;font-weight:800;}
  .pb-dark .pb-badge{background:rgba(255,255,255,.15);color:#fff;}
  .pb-t-hero h1{font-size:clamp(2em,5.2vw,3.2em);font-weight:900;line-height:1.08;letter-spacing:-.02em;overflow-wrap:anywhere;}
  .pb-t-hero h1 em{display:block;font-style:normal;color:var(--pb-accent);}
  .pb-dark.pb-t-hero h1 em{color:#bbf7d0;}
  .pb-hero-visual{position:relative;aspect-ratio:4/3.4;border-radius:28px;overflow:hidden;background:linear-gradient(145deg,#0f5132,var(--pb-accent) 55%,#16a34a);box-shadow:0 30px 60px rgba(0,60,30,.18);}
  .pb-hero-visual img{width:100%;height:100%;object-fit:cover;display:block;}
  .pb-hero-empty{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:#fff;text-align:center;padding:24px;font-size:1.4em;font-weight:900;}

  /* Picture & text */
  .pb-split{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;}
  .pb-split.is-right > .pb-split-img{order:2;}
  .pb-split-img{width:100%;border-radius:22px;object-fit:cover;background:#e5e7eb;}

  /* Cards */
  .pb-grid{display:grid;gap:16px;grid-template-columns:repeat(var(--pb-cols,3),minmax(0,1fr));}
  .pb-card{display:flex;flex-direction:column;gap:8px;padding:22px;border:1px solid #eceff1;border-radius:18px;background:#fff;color:#111827;text-align:left;min-width:0;}
  .pb-card-icon{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;background:var(--pb-accent-soft);font-size:1.3em;}
  .pb-card h3{margin:0;font-size:1.02em;font-weight:800;line-height:1.3;overflow-wrap:anywhere;}
  .pb-card p{margin:0;font-size:.88em;line-height:1.6;color:#4b5563;white-space:pre-line;overflow-wrap:anywhere;}

  /* Numbers */
  .pb-stats{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));}
  .pb-stat{padding:20px;border-radius:18px;background:var(--pb-accent-soft);text-align:center;}
  .pb-dark .pb-stat{background:rgba(255,255,255,.1);}
  .pb-stat strong{display:block;font-size:1.9em;font-weight:900;color:var(--pb-accent);line-height:1.1;}
  .pb-dark .pb-stat strong{color:#fff;}
  .pb-stat span{display:block;margin-top:4px;font-size:.82em;font-weight:700;color:#4b5563;}
  .pb-dark .pb-stat span{color:rgba(255,255,255,.75);}

  /* Team */
  .pb-team{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));}
  .pb-person{padding:22px 16px;border:1px solid #eceff1;border-radius:18px;background:#fff;text-align:center;color:#111827;}
  .pb-person img,.pb-person .pb-avatar{width:72px;height:72px;margin:0 auto 10px;border-radius:50%;object-fit:cover;}
  .pb-person .pb-avatar{display:flex;align-items:center;justify-content:center;background:var(--pb-accent);color:#fff;font-size:1.6em;font-weight:900;}
  .pb-person strong{display:block;font-weight:800;}
  .pb-person span{display:block;font-size:.84em;color:#6b7280;}

  /* Courses */
  .pb-courses-head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:10px;margin-bottom:20px;}
  .pb-courses-head .pb-head{margin-bottom:0;flex:1 1 28rem;}
  .pb .pb-link{font-size:.88em;font-weight:800;color:var(--pb-accent);white-space:nowrap;}
  .pb-dark .pb .pb-link{color:#fff;}
  .pb-courses{display:grid;gap:16px;grid-template-columns:repeat(var(--pb-cols,4),minmax(0,1fr));text-align:left;}
  .pb-course{position:relative;display:flex;flex-direction:column;overflow:hidden;border:1px solid #eceff1;border-radius:18px;background:#fff;color:#111827;transition:transform .15s ease,box-shadow .15s ease;min-width:0;}
  .pb-course:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(0,0,0,.07);}
  .pb-course-cover{aspect-ratio:16/10;width:100%;object-fit:cover;background:#e5e7eb;display:block;}
  .pb-course-fallback{aspect-ratio:16/10;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f5132,var(--pb-accent));font-size:2.2em;font-weight:900;color:#fff;}
  .pb-course-new{position:absolute;top:10px;left:10px;padding:3px 8px;border-radius:6px;background:#dc2626;color:#fff;font-size:.62em;font-weight:900;letter-spacing:.08em;text-transform:uppercase;}
  .pb-course-body{display:flex;flex-direction:column;gap:4px;flex:1;padding:12px 14px 14px;min-width:0;}
  .pb-course-cat{font-size:.66em;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--pb-accent);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .pb-course-title{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.6em;font-size:.95em;font-weight:800;line-height:1.3;}
  .pb-course-org{font-size:.76em;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .pb-course-foot{display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:8px;font-size:.84em;font-weight:800;color:var(--pb-accent);}
  .pb-size-sm .pb-course-body{padding:9px 10px 10px;}.pb-size-sm .pb-course{font-size:.88em;}
  .pb-size-lg .pb-course{font-size:1.1em;}.pb-size-lg .pb-course-body{padding:16px 18px 18px;}

  /* Logos */
  .pb-logos{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:28px 44px;}
  .pb-logos img{height:var(--pb-logo-h,42px);width:auto;max-width:170px;object-fit:contain;}
  .pb-logos span{font-size:1em;font-weight:900;letter-spacing:.06em;color:#9ca3af;}

  /* Call to action */
  .pb-cta{border-radius:24px;padding:36px 24px;background:linear-gradient(145deg,color-mix(in srgb,var(--pb-accent) 70%,#000),var(--pb-accent));color:#fff;}
  .pb-cta .pb-text{color:rgba(255,255,255,.82);}
  .pb .pb-cta .pb-btn-main{background:#fff;color:var(--pb-accent);}
  .pb .pb-cta .pb-btn-alt{background:transparent;color:#fff;border-color:rgba(255,255,255,.55);}
  .pb-dark .pb-cta,.pb-bg-green .pb-cta{background:rgba(255,255,255,.08);}
  .pb-note{display:block;margin-top:14px;font-size:.88em;opacity:.88;}
  .pb-note a{font-weight:800;text-decoration:underline;}

  /* Courses banner */
  .pb-search{display:flex;max-width:560px;margin:1.4em auto 0;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.18);}
  .pb-align-left .pb-search{margin-left:0;}
  .pb-search input{flex:1;min-width:0;border:0;padding:.95rem 1rem;font-size:.95rem;color:#111827;outline:none;}
  .pb-search button{border:0;padding:0 1.3rem;background:var(--pb-accent);color:#fff;font-weight:800;cursor:pointer;}

  /* Sign up / sign in choices */
  .pb-roles{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));text-align:left;}
  .pb-role-columns{display:grid;gap:16px;grid-template-columns:repeat(2,minmax(0,1fr));text-align:left;align-items:start;}
  .pb-role-column{display:flex;flex-direction:column;gap:16px;min-width:0;}
  .pb-role-column-title{font-size:.75em;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:var(--pb-accent);text-align:center;}
  @media (max-width:640px){.pb-role-columns{grid-template-columns:1fr;}}
  .pb-role{display:flex;flex-direction:column;padding:20px;border:2px solid var(--pb-accent);border-radius:16px;background:#fff;color:#111827;transition:box-shadow .15s ease;min-width:0;}
  .pb-role:hover{box-shadow:0 10px 24px rgba(0,0,0,.08);}
  .pb-role h2{margin-top:10px;font-size:1.1em;font-weight:900;overflow-wrap:anywhere;}
  .pb-role p{margin-top:6px;flex:1;font-size:.88em;line-height:1.55;color:#6b7280;}
  .pb-role-go{margin-top:14px;font-size:.72em;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--pb-accent);}
  .pb-role-tag{font-size:.68em;font-weight:900;letter-spacing:.1em;text-transform:uppercase;color:var(--pb-accent);}
  .pb-box{margin-top:24px;padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#f9fafb;text-align:center;font-size:.9em;font-weight:600;color:#374151;}

  @media(max-width:900px){
    .pb-hero-grid,.pb-split{grid-template-columns:1fr;}
    .pb-hero-visual{display:none;}
    .pb-split.is-right > .pb-split-img{order:0;}
    .pb-grid{grid-template-columns:repeat(min(var(--pb-cols,3),2),minmax(0,1fr));}
    .pb-courses{grid-template-columns:repeat(min(var(--pb-cols,4),3),minmax(0,1fr));}
  }
  @media(max-width:640px){
    .pb-pad-lg{padding:44px 0;}.pb-pad-xl{padding:60px 0;}.pb-pad-md{padding:32px 0;}
    .pb-actions .pb-btn{flex:1 1 100%;}
    .pb-grid{grid-template-columns:1fr;}
    .pb-courses{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
    .pb-courses.is-swipe{grid-auto-flow:column;grid-template-columns:none;grid-auto-columns:72%;overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:6px;scrollbar-width:none;}
    .pb-courses.is-swipe::-webkit-scrollbar{display:none;}
    .pb-courses.is-swipe .pb-course{scroll-snap-align:start;}
  }

  /* Inside the portal the page background shows through plain sections. */
  .srms-main .pb-bg-white{background:transparent;}

  /* Questions & answers, video, custom HTML */
  .pb-faq{display:flex;flex-direction:column;gap:10px;max-width:820px;}
  .pb-align-center .pb-faq{margin:0 auto;text-align:left;}
  .pb-faq-item{border:1px solid #eceff1;border-radius:14px;background:#fff;padding:14px 18px;color:#111827;}
  .pb-faq-item summary{cursor:pointer;font-weight:800;}
  .pb-faq-item .pb-text{margin-top:8px;}
  .pb-video{position:relative;aspect-ratio:16/9;border-radius:18px;overflow:hidden;background:#000;}
  .pb-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0;}
  .pb-html img{max-width:100%;height:auto;}
  .pb-html h1{font-size:2em;font-weight:900;line-height:1.15;margin:.4em 0;}
  .pb-html h2{font-size:1.6em;font-weight:900;line-height:1.2;margin:.5em 0 .3em;}
  .pb-html h3{font-size:1.25em;font-weight:800;margin:.5em 0 .3em;}
  .pb-html p{margin:.5em 0;line-height:1.65;}
  .pb-html ul{list-style:disc;padding-left:1.4em;margin:.5em 0;}
  .pb-html ol{list-style:decimal;padding-left:1.4em;margin:.5em 0;}
  .pb-html a{color:var(--pb-accent);text-decoration:underline;}

  /* Student sign-up form */
  .pb-contact-form{max-width:520px;margin:0 auto;padding:24px;border:1px solid #eceff1;border-radius:18px;background:#fff;text-align:left;position:relative;}
  .pb-form-notice{margin:0 0 14px;padding:10px 14px;border-radius:10px;font-size:.85em;font-weight:700;}
  .pb-form-notice.is-ok{background:#ecf7f0;color:#006b3f;border:1px solid #b7e1c7;}
  .pb-form-notice.is-bad{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;}
  .pb-lookup{max-width:640px;margin:0 auto;}
  .pb-lookup-form{display:flex;gap:8px;margin:1.2em auto 0;max-width:520px;}
  .pb-lookup-form input{flex:1;min-width:0;padding:.8em 1em;border:1.5px solid #d1d5db;border-radius:12px;font:inherit;font-size:.95em;background:#fff;color:#111827;}
  .pb-lookup-form input:focus{outline:none;border-color:var(--pb-accent);}
  .pb-lookup-result{margin-top:1.4em;padding:20px;border-radius:16px;background:#fff;text-align:left;color:#111827;font-size:.92em;}
  .pb-lookup-result.is-bad{border:1.5px solid #fecaca;}
  .pb-lookup-result.is-bad strong{color:#b91c1c;}
  .pb-lookup-result.is-good{border:1.5px solid color-mix(in srgb,var(--pb-accent) 40%,#fff);}
  .pb-lookup-result h4{margin:1.1em 0 .4em;font-size:.72em;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;}
  .pb-lookup-result ul{margin:0;padding-left:1.1em;}
  .pb-lookup-result li{margin:.25em 0;}
  .pb-lookup-result li span{color:#6b7280;}
  .pb-lookup-who{display:flex;align-items:center;gap:14px;}
  .pb-lookup-who h3{margin:.2em 0 .1em;font-size:1.25em;font-weight:900;}
  .pb-lookup-who p{margin:0;color:#4b5563;font-size:.9em;}
  .pb-lookup-photo{width:64px;height:64px;flex-shrink:0;border-radius:50%;object-fit:cover;}
  .pb-lookup-photo.is-initial{display:flex;align-items:center;justify-content:center;background:var(--pb-accent);color:#fff;font-weight:900;font-size:1.5em;}
  .pb-lookup-badge{display:inline-block;padding:.2em .7em;border-radius:99px;background:#ecf7f0;color:#006b3f;font-size:.72em;font-weight:900;text-transform:uppercase;}
  .pb-lookup-none{margin-top:1em;color:#6b7280;}
  @media(max-width:520px){.pb-lookup-form{flex-direction:column;}.pb-lookup-who{align-items:flex-start;}}
  .pb-signup{max-width:440px;margin:0 auto;padding:24px;border:1px solid #eceff1;border-radius:18px;background:#fff;text-align:left;}
  .pb-signup-form{display:flex;flex-direction:column;gap:12px;}
  .pb-signup-form label{display:flex;flex-direction:column;gap:4px;font-size:.72em;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#4b5563;}
  .pb-signup-form textarea{text-transform:none;letter-spacing:0;font-size:.95rem;color:#111827;}
  .pb-signup-form input{padding:.7rem .8rem;border:1px solid #d1d5db;border-radius:10px;font-size:.95rem;text-transform:none;letter-spacing:0;color:#111827;}

  /* ---- Landing sections (hero slider, feature strip, welcome, explore, picture cards, gallery, band) ---- */
  .pb{--pbx-gold:#e3a72f;--pbx-deep:color-mix(in srgb,var(--pb-accent) 78%,#000);--pbx-serif:'Playfair Display',Georgia,'Times New Roman',serif;}
  .pbx-label{margin:0 0 .35em;font-size:.72em;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--pbx-gold);}
  .pbx-title{margin:0 0 .9em;font-family:var(--pbx-serif);font-size:clamp(1.45em,3vw,2.05em);font-weight:700;line-height:1.2;color:var(--pbx-deep);}
  .pb-dark .pbx-title{color:#fff;}
  .pbx-text{margin:0 0 1.2em;font-size:.93em;line-height:1.75;color:#4b5563;white-space:pre-line;}
  .pbx-center{display:flex;justify-content:center;margin-top:1.8em;}
  .pbx-btn{display:inline-flex;align-items:center;gap:10px;min-height:44px;padding:0 1.3rem;border-radius:6px;font-size:.78em;font-weight:800;letter-spacing:.06em;text-transform:uppercase;border:1.5px solid transparent;transition:filter .15s,background .15s,color .15s;}
  .pb .pbx-btn.is-gold{background:var(--pbx-gold);color:#1f1300;}
  .pb .pbx-btn.is-gold:hover{filter:brightness(.93);}
  .pb .pbx-btn.is-ghost{border-color:rgba(255,255,255,.85);color:#fff;}
  .pb .pbx-btn.is-ghost:hover{background:#fff;color:#111827;}
  .pb .pbx-btn.is-dark{background:var(--pbx-deep);color:#fff;}
  .pb .pbx-btn.is-dark:hover{filter:brightness(1.15);}
  .pb .pbx-btn.is-outline{border-color:var(--pbx-deep);color:var(--pbx-deep);background:#fff;}
  .pb .pbx-btn.is-outline:hover{background:var(--pbx-deep);color:#fff;}
  .pb-t-hero_slider > .pb-wrap{padding:0;}
  .pbx-pic-empty{display:flex;align-items:center;justify-content:center;width:100%;height:100%;min-height:140px;background:linear-gradient(145deg,var(--pbx-deep),var(--pb-accent));color:rgba(255,255,255,.85);}

  /* Hero slider */
  .pbx-slider{position:relative;height:var(--pbx-h,560px);min-height:420px;overflow:hidden;background:linear-gradient(135deg,var(--pbx-deep),var(--pb-accent));color:#fff;}
  .pbx-slide{position:absolute;inset:0;opacity:0;visibility:hidden;transition:opacity .8s ease,visibility .8s;}
  .pbx-slide.is-active{opacity:1;visibility:visible;}
  .pbx-slide img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform:scale(1.04);transition:transform 7s ease;}
  .pbx-slide.is-active img{transform:scale(1);}
  .pbx-slide::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(5,20,12,.82) 0%,rgba(5,20,12,.55) 45%,rgba(5,20,12,.08) 100%);}
  .pbx-slide-inner{position:relative;z-index:1;display:flex;align-items:center;height:100%;max-width:1200px;margin:0 auto;padding:0 max(70px,6%) 40px;}
  .pbx-slide-copy{max-width:560px;}
  .pbx-slide h1{margin:0;font-family:var(--pbx-serif);font-size:clamp(2em,4.6vw,3.3em);font-weight:700;line-height:1.15;color:#fff;}
  .pbx-slide h1 em{display:block;font-style:normal;color:var(--pbx-gold);}
  .pbx-slide p{margin:1em 0 0;max-width:470px;font-size:1em;line-height:1.65;color:rgba(255,255,255,.9);}
  .pbx-slide-actions{display:flex;flex-wrap:wrap;gap:14px;margin-top:1.8em;}
  .pbx-arrow{position:absolute;top:50%;z-index:2;display:flex;align-items:center;justify-content:center;width:46px;height:46px;margin-top:-23px;border:1.5px solid rgba(255,255,255,.8);border-radius:50%;background:rgba(0,0,0,.15);color:#fff;cursor:pointer;}
  .pbx-arrow:hover{background:rgba(255,255,255,.2);}
  .pbx-arrow.is-prev{left:20px;}.pbx-arrow.is-next{right:20px;}
  .pbx-dots{position:absolute;left:0;right:0;bottom:70px;z-index:2;display:flex;justify-content:center;gap:8px;}
  .pbx-dots button{width:9px;height:9px;padding:0;border:0;border-radius:99px;background:rgba(255,255,255,.5);cursor:pointer;transition:width .3s,background .3s;}
  .pbx-dots button.is-active{width:24px;background:#fff;}

  /* Feature strip: a white card lifted over the bottom of the hero. */
  .pb-t-feature_strip{z-index:3;}
  .pbx-strip{position:relative;display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin:-56px auto 0;background:#fff;border-radius:10px;box-shadow:0 18px 40px rgba(15,40,25,.12);}
  .pbx-strip-item{display:flex;align-items:center;gap:14px;padding:24px 20px;}
  .pbx-strip-item + .pbx-strip-item{border-left:1px solid #edf0ee;}
  .pbx-strip-icon{flex-shrink:0;color:var(--pbx-deep);}
  .pbx-strip-item strong{display:block;font-size:.86em;font-weight:800;color:var(--pbx-deep);}
  .pbx-strip-item span{display:block;margin-top:2px;font-size:.76em;line-height:1.45;color:#6b7280;}

  /* Welcome */
  .pbx-welcome{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center;}
  .pbx-welcome-pic{aspect-ratio:4/3;overflow:hidden;border-radius:10px;box-shadow:0 14px 34px rgba(0,0,0,.1);}
  .pbx-welcome-pic img{width:100%;height:100%;object-fit:cover;display:block;}
  .pbx-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:.4em 0 1.6em;}
  .pbx-stats div{text-align:center;}
  .pbx-stats div + div{border-left:1px solid #e5e7eb;}
  .pbx-stats strong{display:block;font-family:var(--pbx-serif);font-size:1.5em;font-weight:700;color:var(--pbx-deep);}
  .pbx-stats span{display:block;margin-top:2px;font-size:.72em;color:#6b7280;}

  /* Explore: icon cards + round picture with a badge */
  .pbx-explore{display:grid;grid-template-columns:1fr 300px;gap:32px;align-items:center;}
  .pbx-icon-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
  .pbx-icon-card{padding:24px 14px;border:1px solid #eceeed;border-radius:8px;background:#fff;text-align:center;box-shadow:0 6px 18px rgba(0,0,0,.05);transition:transform .2s,box-shadow .2s;}
  .pbx-icon-card:hover{transform:translateY(-3px);box-shadow:0 14px 28px rgba(0,0,0,.08);}
  .pbx-icon-card-icon{display:inline-flex;color:var(--pbx-deep);}
  .pbx-icon-card h3{margin:.7em 0 .45em;font-family:var(--pbx-serif);font-size:.98em;font-weight:700;color:var(--pbx-deep);}
  .pbx-icon-card p{margin:0;font-size:.74em;line-height:1.55;color:#6b7280;}
  .pbx-round{position:relative;padding-bottom:34px;}
  .pbx-round-pic{aspect-ratio:1;overflow:hidden;border-radius:50%;border:6px solid #fff;box-shadow:0 14px 34px rgba(0,0,0,.14);}
  .pbx-round-pic img{width:100%;height:100%;object-fit:cover;display:block;}
  .pbx-round-badge{position:absolute;left:4%;right:4%;bottom:0;padding:14px 12px;border-radius:8px;background:var(--pbx-deep);color:#fff;text-align:center;}
  .pbx-round-badge strong{display:block;font-family:var(--pbx-serif);font-size:1.25em;font-style:italic;font-weight:600;}
  .pbx-round-badge span{display:block;margin-top:2px;font-size:.8em;opacity:.85;}

  /* Picture cards */
  .pbx-pic-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;}
  .pbx-pic-card{position:relative;display:flex;flex-direction:column;overflow:hidden;border-radius:8px;background:#fff;box-shadow:0 8px 22px rgba(0,0,0,.07);transition:transform .2s,box-shadow .2s;}
  a.pbx-pic-card:hover{transform:translateY(-3px);box-shadow:0 16px 30px rgba(0,0,0,.1);}
  .pbx-pic-card-img{aspect-ratio:16/10;overflow:hidden;}
  .pbx-pic-card-img img{width:100%;height:100%;object-fit:cover;display:block;}
  .pbx-pic-card-icon{position:relative;z-index:1;display:flex;align-items:center;justify-content:center;width:44px;height:44px;margin:-22px 0 0 16px;border:3px solid #fff;border-radius:50%;background:var(--pbx-deep);color:#fff;}
  .pbx-pic-card-body{padding:8px 16px 18px;}
  .pbx-pic-card h3{margin:0 0 .35em;font-size:.92em;font-weight:800;color:var(--pbx-deep);}
  .pbx-pic-card p{margin:0;font-size:.78em;line-height:1.55;color:#6b7280;}

  /* Gallery strip */
  .pbx-gallery-head{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:12px;}
  .pbx-gallery-head .pbx-title{margin-bottom:.8em;}
  .pbx-gallery-head .pbx-btn{margin-bottom:1.1em;}
  .pbx-gallery{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;}
  .pbx-gallery figure{position:relative;margin:0;aspect-ratio:4/3;overflow:hidden;border-radius:6px;}
  .pbx-gallery img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s;}
  .pbx-gallery figure:hover img{transform:scale(1.05);}
  .pbx-gallery figcaption{position:absolute;left:0;right:0;bottom:0;padding:18px 10px 8px;background:linear-gradient(transparent,rgba(0,0,0,.65));color:#fff;font-size:.74em;font-weight:700;}

  /* Announcement band */
  .pbx-band{display:grid;grid-template-columns:auto 1.2fr 1fr auto;align-items:center;gap:26px;padding:26px 34px;border-radius:10px;background:var(--pbx-deep);color:#fff;}
  .pbx-band-icon{color:var(--pbx-gold);}
  .pbx-band h2{margin:0;padding-right:26px;border-right:1px solid rgba(255,255,255,.3);font-family:var(--pbx-serif);font-size:clamp(1.25em,2.4vw,1.65em);font-weight:700;line-height:1.25;color:var(--pbx-gold);}
  .pbx-band p{margin:0;font-size:.95em;line-height:1.55;color:rgba(255,255,255,.88);}

  @media(max-width:1023px){
    .pbx-explore{grid-template-columns:1fr;}
    .pbx-round{width:100%;max-width:300px;margin:0 auto;}
    .pbx-pic-cards{grid-template-columns:repeat(2,1fr);}
    .pbx-gallery{grid-template-columns:repeat(3,1fr);}
    .pbx-band{grid-template-columns:auto 1fr;}
    .pbx-band h2{border-right:0;padding-right:0;}
    .pbx-band p,.pbx-band .pbx-btn{grid-column:1 / -1;justify-self:start;}
  }
  @media(max-width:720px){
    .pbx-slider{min-height:480px;}
    .pbx-slide-inner{align-items:flex-end;padding:0 20px 92px;}
    .pbx-slide::after{background:linear-gradient(0deg,rgba(5,20,12,.88) 0%,rgba(5,20,12,.5) 60%,rgba(5,20,12,.2) 100%);}
    .pbx-slide p{font-size:.9em;}
    .pbx-btn{min-height:40px;padding:0 1rem;font-size:.72em;}
    .pbx-arrow{display:none;}
    .pbx-dots{bottom:64px;}
    .pbx-strip{grid-template-columns:repeat(2,1fr);margin-top:-40px;}
    .pbx-strip-item{flex-direction:column;align-items:flex-start;gap:8px;padding:16px 14px;}
    .pbx-strip-item + .pbx-strip-item{border-left:0;}
    .pbx-strip-item:nth-child(even){border-left:1px solid #edf0ee;}
    .pbx-strip-item:nth-child(n+3){border-top:1px solid #edf0ee;}
    .pbx-strip-item:last-child:nth-child(odd){grid-column:1 / -1;}
    .pbx-welcome{grid-template-columns:1fr;gap:26px;}
    .pbx-stats{grid-template-columns:repeat(2,1fr);row-gap:16px;}
    .pbx-stats div:nth-child(odd){border-left:0;}
    .pbx-icon-cards{grid-template-columns:repeat(2,1fr);gap:10px;}
    .pbx-icon-card{padding:18px 10px;}
    .pbx-pic-cards{gap:10px;}
    .pbx-pic-card-body{padding:6px 10px 14px;}
    .pbx-pic-card-icon{width:38px;height:38px;margin:-19px 0 0 10px;}
    .pbx-gallery{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;gap:8px;margin:0 calc(-1 * max(16px,4%));padding:0 max(16px,4%);scrollbar-width:none;}
    .pbx-gallery::-webkit-scrollbar{display:none;}
    .pbx-gallery figure{flex:0 0 62%;scroll-snap-align:start;}
    .pbx-band{grid-template-columns:1fr;gap:12px;padding:24px 20px;text-align:left;}
    .pbx-band-icon{display:none;}
  }

  /* Super Admin's live preview: outline the section under the mouse. */
  .pb-previewing .pb-sec{cursor:pointer;}
  .pb-previewing .pb-sec:hover{outline:2px dashed #2563eb;outline-offset:-2px;}
  .pb-previewing .pb-sec.pb-selected{outline:2px solid #2563eb;outline-offset:-2px;}
  .pb-previewing .pb-sec[data-pb-hidden]{opacity:.35;}
</style>
<?= \App\Services\SiteTheme::head('public') ?>
