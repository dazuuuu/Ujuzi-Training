<?php
/**
 * Footer of the public pages: about + social links, two link columns, the
 * contact details and a newsletter sign-up, then the copyright line and
 * legal links. Everything comes from Super Admin → Pages → Navbar & footer.
 * Optional $needsSetup.
 */
use App\Services\PageBuilder;

$pbSite = PageBuilder::site();
$ftHref = static fn(string $u): string => e(PageBuilder::href($u));
$ftLinks = static fn(array $links): array => array_values(array_filter($links, static fn(array $l): bool => ($l['label'] ?? '') !== '' && ($l['url'] ?? '') !== ''));
$ftDigits = static fn(string $n): string => preg_replace('/[^0-9+]/', '', $n);
$ftSocial = [
    'social_facebook' => ['Facebook', '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H8v4h2v8h4v-8h3l1-4h-4V8z"/>'],
    'social_instagram' => ['Instagram', '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8"/>'],
    'social_youtube' => ['YouTube', '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>'],
    'social_linkedin' => ['LinkedIn', '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>'],
    'social_x' => ['X', '<path d="M4 4l16 16M20 4 4 20"/>'],
    'social_tiktok' => ['TikTok', '<path d="M14 3v11a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3a5 5 0 0 0 5 5"/>'],
];
$contact = array_filter([
    'map' => trim((string) ($pbSite['contact_address'] ?? '')),
    'phone' => trim((string) ($pbSite['contact_phone'] ?? '')),
    'mail' => trim((string) ($pbSite['contact_email'] ?? '')),
    'clock' => trim((string) ($pbSite['contact_hours'] ?? '')),
]);
$ftIcon = static fn(string $d): string => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
$contactIcons = [
    'map' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
    'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
    'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
    'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
];
$newsState = (string) ($_GET['subscribed'] ?? '');
?>
<style>
  .pub-footer{--ft-gold:#e3a72f;padding:44px max(16px,5%) calc(0px + env(safe-area-inset-bottom));background:<?= e(in_array($pbSite['footer_bg'], ['', '#0f172a'], true) ? '#0b3d24' : $pbSite['footer_bg']) ?>;color:rgba(255,255,255,.72);font-family:'Inter',system-ui,sans-serif;font-size:.82rem;}
  .pub-footer a{color:inherit;text-decoration:none;}
  .pub-footer-grid{max-width:1180px;margin:0 auto;display:grid;grid-template-columns:1.3fr .8fr .9fr 1.2fr 1.2fr;gap:28px;}
  .pub-footer-grid > div + div{padding-left:22px;border-left:1px solid rgba(255,255,255,.1);}
  .pub-footer h3{margin:0 0 14px;font-size:.78rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#fff;}
  .pub-footer-brand strong{display:flex;align-items:center;gap:8px;margin-bottom:10px;font-family:'Playfair Display',Georgia,serif;font-size:1.25rem;font-weight:700;color:#fff;}
  .pub-footer-brand strong img{height:34px;width:auto;}
  .pub-footer-brand p{margin:0;line-height:1.65;white-space:pre-line;}
  .pub-footer-social{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;}
  .pub-footer-social a{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid rgba(255,255,255,.45);border-radius:50%;color:#fff;}
  .pub-footer-social a:hover{background:var(--ft-gold);border-color:var(--ft-gold);color:#1f1300;}
  .pub-footer ul{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:7px;}
  .pub-footer ul a:hover{color:#fff;text-decoration:underline;}
  .pub-footer-contact li{display:flex;gap:9px;align-items:flex-start;line-height:1.5;}
  .pub-footer-contact svg{flex-shrink:0;margin-top:2px;color:var(--ft-gold);}
  .pub-footer-news p{margin:0 0 12px;line-height:1.55;}
  .pub-footer-news form{display:flex;max-width:280px;}
  .pub-footer-news input{flex:1;min-width:0;padding:.65rem .8rem;border:1px solid rgba(255,255,255,.3);border-right:0;border-radius:4px 0 0 4px;background:rgba(255,255,255,.06);color:#fff;font:inherit;}
  .pub-footer-news input::placeholder{color:rgba(255,255,255,.5);}
  .pub-footer-news button{display:flex;align-items:center;justify-content:center;width:44px;border:0;border-radius:0 4px 4px 0;background:#fff;color:#0b3d24;cursor:pointer;}
  .pub-footer-news button:hover{background:var(--ft-gold);}
  .pub-footer-note{margin-top:8px !important;font-size:.76rem;font-weight:700;color:var(--ft-gold);}
  .pub-footer-bottom{max-width:1180px;margin:34px auto 0;padding:14px 0;border-top:1px solid rgba(255,255,255,.12);display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;font-size:.74rem;}
  .pub-footer-bottom nav{display:flex;flex-wrap:wrap;gap:6px 14px;}
  .pub-footer-bottom nav a + a::before{content:"|";margin-right:14px;opacity:.4;}
  @media(max-width:1023px){.pub-footer-grid{grid-template-columns:1fr 1fr 1fr;}.pub-footer-brand{grid-column:1 / -1;}.pub-footer-grid > div + div{padding-left:0;border-left:0;}}
  @media(max-width:640px){.pub-footer-grid{grid-template-columns:1fr 1fr;gap:24px 18px;}.pub-footer-contact,.pub-footer-news{grid-column:1 / -1;}.pub-footer-bottom{flex-direction:column;}}
</style>
<footer class="pub-footer">
  <div class="pub-footer-grid">
    <div class="pub-footer-brand">
      <strong><?= storeLogoPath() ? storeLogoHtml('') : '' ?><?= e(appName()) ?></strong>
      <p><?= e($pbSite['footer_text']) ?></p>
      <div class="pub-footer-social">
        <?php foreach ($ftSocial as $key => [$label, $path]): if (($pbSite[$key] ?? '') === '') { continue; } ?>
          <a href="<?= e($pbSite[$key]) ?>" target="_blank" rel="noopener" aria-label="<?= e($label) ?>"><?= $ftIcon($path) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <h3>Quick links</h3>
      <ul>
        <?php foreach ($ftLinks($pbSite['footer_links']) as $l): ?><li><a href="<?= $ftHref($l['url']) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?>
        <?php if (!empty($needsSetup)): ?><li><a href="<?= url('/setup') ?>">Run setup</a></li><?php else: ?><li><a href="<?= url('/admin/login') ?>">Super Admin</a></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <h3><?= e(($pbSite['footer_info_title'] ?? '') ?: 'Information') ?></h3>
      <ul><?php foreach ($ftLinks($pbSite['footer_info_links'] ?? []) as $l): ?><li><a href="<?= $ftHref($l['url']) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?></ul>
    </div>
    <div class="pub-footer-contact">
      <h3>Contact us</h3>
      <ul>
        <?php foreach ($contact as $kind => $value): ?>
          <li><?= $ftIcon($contactIcons[$kind]) ?>
            <?php if ($kind === 'phone'): ?><a href="tel:<?= e($ftDigits($value)) ?>"><?= e($value) ?></a>
            <?php elseif ($kind === 'mail'): ?><a href="mailto:<?= e($value) ?>"><?= e($value) ?></a>
            <?php else: ?><span><?= e($value) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
        <?php if (!$contact): ?><li><a href="<?= url('/contact') ?>">Contact page</a></li><?php endif; ?>
      </ul>
    </div>
    <?php if (!empty($pbSite['newsletter_show'])): ?>
      <div class="pub-footer-news" id="newsletter">
        <h3>Newsletter</h3>
        <p><?= e(($pbSite['newsletter_text'] ?? '') ?: 'Subscribe for updates.') ?></p>
        <form method="post" action="<?= url('/newsletter') ?>">
          <input type="email" name="email" required placeholder="Enter your email" aria-label="Your email">
          <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px">
          <button type="submit" aria-label="Subscribe"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg></button>
        </form>
        <?php if ($newsState === '1'): ?><p class="pub-footer-note">Thank you — you're subscribed.</p><?php elseif ($newsState === '0'): ?><p class="pub-footer-note">Please enter a valid email.</p><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="pub-footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= e(appName()) ?>. <?= e($pbSite['footer_bottom']) ?></span>
    <?php if ($legal = $ftLinks($pbSite['footer_legal_links'] ?? [])): ?>
      <nav aria-label="Legal"><?php foreach ($legal as $l): ?><a href="<?= $ftHref($l['url']) ?>"><?= e($l['label']) ?></a><?php endforeach; ?></nav>
    <?php endif; ?>
  </div>
</footer>
