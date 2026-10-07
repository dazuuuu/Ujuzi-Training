<?php
/**
 * Top bar of the public pages: the logo, the links Super Admin set under
 * Pages → Navbar & footer, and Sign in / Sign up. The link matching the
 * current path is highlighted.
 */
use App\Services\PageBuilder;

$pbSite = PageBuilder::site();
$navPath = rtrim(\App\Core\Url::currentPath(), '/') ?: '/';
$navAccent = $pbSite['accent'] ?: '#006b3f';
$navTheme = \App\Services\SiteTheme::get();
if ($navTheme['accent'] !== \App\Services\SiteTheme::DEFAULTS['accent']) {
    $navAccent = $navTheme['accent']; // an applied theme sets the main colour everywhere
}
?>
<style>
  .pub-nav{--pub-accent:<?= e($navAccent) ?>;position:<?= !empty($pbSite['nav_sticky']) ? 'sticky' : 'relative' ?>;top:0;z-index:200;display:flex;align-items:center;justify-content:space-between;gap:12px;height:64px;padding:0 max(16px,5%);padding-top:env(safe-area-inset-top);background:<?= e($pbSite['nav_bg'] ?: '#ffffff') ?>;backdrop-filter:saturate(1.4) blur(10px);-webkit-backdrop-filter:saturate(1.4) blur(10px);border-bottom:1px solid #eef0f3;font-family:'Inter',system-ui,sans-serif;}
  .pub-logo{display:flex;align-items:center;gap:9px;min-width:0;font-weight:900;font-size:1.08rem;color:#111827;text-decoration:none;white-space:nowrap;}
  .pub-logo-mark{display:flex;align-items:center;justify-content:center;width:34px;height:34px;flex-shrink:0;border-radius:10px;background:var(--pub-accent);color:#fff;}
  .pub-logo-mark.has-logo{background:#fff;width:auto;min-width:34px;max-width:120px;}
  .pub-logo-img{height:34px;width:auto;max-width:120px;object-fit:contain;display:block;}
  .pub-links{display:flex;align-items:center;gap:4px;}
  .pub-links a{padding:.45rem .8rem;border-radius:99px;font-size:.86rem;font-weight:700;color:<?= e($pbSite['nav_text'] ?: '#374151') ?>;text-decoration:none;white-space:nowrap;}
  .pub-links a:hover,.pub-links a.is-active{background:color-mix(in srgb,var(--pub-accent) 10%,#fff);color:var(--pub-accent);}
  .pub-signin{display:inline-flex;align-items:center;padding:.5rem 1.05rem;border-radius:99px;border:1.5px solid #d1d5db;font-size:.84rem;font-weight:800;color:#111827;text-decoration:none;white-space:nowrap;}
  .pub-signin:hover{border-color:var(--pub-accent);color:var(--pub-accent);}
  .pub-signup{display:inline-flex;align-items:center;padding:.55rem 1.1rem;border-radius:99px;background:var(--pub-accent);font-size:.84rem;font-weight:800;color:#fff;text-decoration:none;white-space:nowrap;}
  .pub-signup:hover{filter:brightness(.9);}
  .pub-actions{display:flex;align-items:center;gap:8px;}
  .pub-links{min-width:0;overflow-x:auto;scrollbar-width:none;}
  .pub-links::-webkit-scrollbar{display:none;}
  .pub-menu{display:none;border:0;background:none;padding:.4rem;border-radius:8px;cursor:pointer;color:inherit;}
  @media(max-width:720px){
    .pub-menu{display:inline-flex;}
    .pub-links{display:none;position:absolute;top:100%;left:0;right:0;flex-direction:column;align-items:stretch;padding:8px max(16px,5%) 14px;background:<?= e($pbSite['nav_bg'] ?: '#ffffff') ?>;border-bottom:1px solid #eef0f3;box-shadow:0 12px 24px rgba(0,0,0,.08);}
    .pub-nav.is-open .pub-links{display:flex;}
    .pub-links a{padding:.7rem .8rem;border-radius:10px;}
    .pub-actions .pub-signup{display:none;}
  }
</style>
<header class="pub-nav">
  <a href="<?= url('/') ?>" class="pub-logo" aria-label="<?= e(appName()) ?> home">
    <span class="pub-logo-mark<?= storeLogoPath() ? ' has-logo' : '' ?>"><?= storeLogoPath() ? storeLogoHtml('pub-logo-img') : icon('graduation', 'h-5 w-5') ?></span><span class="truncate"><?= e(appName()) ?></span>
  </a>
  <nav class="pub-links" aria-label="Main" id="pub-links">
    <?php foreach ($pbSite['nav_links'] as $navLink):
      if (($navLink['label'] ?? '') === '' || ($navLink['url'] ?? '') === '') { continue; }
      $navActive = str_starts_with($navLink['url'], '/') && (rtrim($navLink['url'], '/') ?: '/') === $navPath;
    ?>
      <a href="<?= e(PageBuilder::href($navLink['url'])) ?>" class="<?= $navActive ? 'is-active' : '' ?>"><?= e($navLink['label']) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="pub-actions">
    <a href="<?= url('/account/login') ?>" class="pub-signin"><?= e($pbSite['nav_signin_label'] ?: 'Sign in') ?></a>
    <?php if (!empty($pbSite['nav_show_signup'])): ?>
      <a href="<?= url('/account/register/choose') ?>" class="pub-signup"><?= e($pbSite['nav_signup_label'] ?: 'Sign up') ?></a>
    <?php endif; ?>
    <button type="button" class="pub-menu" aria-label="Menu" aria-controls="pub-links" aria-expanded="false" onclick="var n=this.closest('.pub-nav');n.classList.toggle('is-open');this.setAttribute('aria-expanded',n.classList.contains('is-open'));">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
  </div>
</header>
