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
// The contact strip above the navbar (Super Admin → Pages → Navbar & footer); not inside the portals.
$topDigits = static fn(string $n): string => preg_replace('/[^0-9+]/', '', $n);
$topItems = [];
if (!empty($pbSite['topbar_show']) && !str_starts_with($navPath, '/account')) {
    if (($v = trim((string) $pbSite['contact_phone'])) !== '') { $topItems[] = ['phone', $v, 'tel:' . $topDigits($v)]; }
    if (($v = trim((string) $pbSite['contact_email'])) !== '') { $topItems[] = ['mail', $v, 'mailto:' . $v]; }
    if (($v = trim((string) $pbSite['contact_whatsapp'])) !== '') { $topItems[] = ['chat', 'WhatsApp', 'https://wa.me/' . ltrim($topDigits($v), '+')]; }
    if (($v = trim((string) $pbSite['contact_address'])) !== '') { $topItems[] = ['pin', $v, '']; }
    if (($v = trim((string) $pbSite['contact_hours'])) !== '') { $topItems[] = ['clock', $v, '']; }
}
$topLinks = !empty($pbSite['topbar_show']) && !str_starts_with($navPath, '/account')
    ? array_values(array_filter($pbSite['topbar_links'] ?? [], static fn(array $l): bool => ($l['label'] ?? '') !== '' && ($l['url'] ?? '') !== ''))
    : [];
$topCta = !empty($pbSite['topbar_show']) && !str_starts_with($navPath, '/account') && ($pbSite['topbar_cta_label'] ?? '') !== '' && ($pbSite['topbar_cta_url'] ?? '') !== '';
$topIcons = [
    'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
    'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
    'chat' => '<path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.6A8.5 8.5 0 1 1 21 11.5z"/>',
    'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
];
?>
<?php if ($topItems || $topLinks || $topCta): ?>
<div class="pub-topbar" style="background:<?= e(($pbSite['topbar_bg'] ?? '') ?: 'color-mix(in srgb,' . $navAccent . ' 78%,#000)') ?>">
  <div class="pub-topbar-left">
  <?php foreach ($topItems as [$ic, $label, $link]): ?>
    <<?= $link !== '' ? 'a href="' . e($link) . '"' . (str_starts_with($link, 'https') ? ' target="_blank" rel="noopener"' : '') : 'span' ?> class="pub-topbar-item<?= in_array($ic, ['pin', 'clock'], true) ? ' is-optional' : '' ?>">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $topIcons[$ic] ?></svg><span><?= e($label) ?></span>
    </<?= $link !== '' ? 'a' : 'span' ?>>
  <?php endforeach; ?>
  </div>
  <div class="pub-topbar-right">
    <?php foreach ($topLinks as $tl): ?><a href="<?= e(\App\Services\PageBuilder::href($tl['url'])) ?>" class="pub-topbar-link"><?= e($tl['label']) ?></a><?php endforeach; ?>
    <?php if ($topCta): ?><a href="<?= e(\App\Services\PageBuilder::href($pbSite['topbar_cta_url'])) ?>" class="pub-topbar-cta"><?= e($pbSite['topbar_cta_label']) ?></a><?php endif; ?>
  </div>
</div>
<?php endif; ?>
<style>
  .pub-topbar{display:flex;flex-wrap:wrap;align-items:stretch;justify-content:space-between;gap:4px 18px;padding:0 max(16px,5%);padding-top:env(safe-area-inset-top);color:#fff;font-family:'Inter',system-ui,sans-serif;font-size:.76rem;font-weight:600;line-height:1.3;}
  .pub-topbar-left,.pub-topbar-right{display:flex;flex-wrap:wrap;align-items:center;gap:4px 18px;min-height:34px;}
  .pub-topbar-link{color:#fff;text-decoration:none;opacity:.9;white-space:nowrap;}
  .pub-topbar-link:hover{opacity:1;text-decoration:underline;}
  .pub-topbar-cta{display:flex;align-items:center;align-self:stretch;padding:0 16px;background:#e3a72f;color:#1f1300;font-weight:800;text-transform:uppercase;letter-spacing:.04em;font-size:.72rem;text-decoration:none;white-space:nowrap;}
  .pub-topbar-cta:hover{filter:brightness(.93);}
  .pub-topbar-item{display:inline-flex;align-items:center;gap:6px;min-width:0;color:#fff;text-decoration:none;white-space:nowrap;opacity:.95;}
  .pub-topbar-item span{overflow:hidden;text-overflow:ellipsis;max-width:260px;}
  a.pub-topbar-item:hover{opacity:1;text-decoration:underline;}
  @media(max-width:720px){.pub-topbar-right{display:none;}.pub-topbar-left{justify-content:center;width:100%;min-height:30px;}.pub-topbar{justify-content:center;gap:4px 14px;font-size:.72rem;}.pub-topbar-item.is-optional{display:none;}.pub-topbar-item span{max-width:180px;}}
  .pub-nav{--pub-accent:<?= e($navAccent) ?>;position:<?= !empty($pbSite['nav_sticky']) ? 'sticky' : 'relative' ?>;top:0;z-index:200;display:flex;align-items:center;justify-content:space-between;gap:12px;height:72px;padding:0 max(16px,5%);padding-top:env(safe-area-inset-top);background:<?= e($pbSite['nav_bg'] ?: '#ffffff') ?>;backdrop-filter:saturate(1.4) blur(10px);-webkit-backdrop-filter:saturate(1.4) blur(10px);border-bottom:1px solid #eef0f3;font-family:'Inter',system-ui,sans-serif;}
  .pub-logo{display:flex;align-items:center;gap:9px;min-width:0;font-weight:900;font-size:1.08rem;color:#111827;text-decoration:none;white-space:nowrap;}
  .pub-logo-mark{display:flex;align-items:center;justify-content:center;width:34px;height:34px;flex-shrink:0;border-radius:10px;background:var(--pub-accent);color:#fff;}
  .pub-logo-mark.has-logo{background:#fff;width:auto;min-width:34px;max-width:120px;}
  .pub-logo-img{height:34px;width:auto;max-width:120px;object-fit:contain;display:block;}
  .pub-links{display:flex;align-items:center;gap:4px;}
  .pub-links a{padding:.45rem .8rem;border-radius:99px;font-size:.86rem;font-weight:700;color:<?= e($pbSite['nav_text'] ?: '#374151') ?>;text-decoration:none;white-space:nowrap;}
  .pub-links a{position:relative;text-transform:uppercase;letter-spacing:.03em;font-size:.78rem;}
  .pub-links a:hover,.pub-links a.is-active{color:var(--pub-accent);}
  .pub-links a.is-active::after{content:"";position:absolute;left:.8rem;right:.8rem;bottom:.1rem;height:2px;border-radius:2px;background:var(--pub-accent);}
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
