<?php
/** Footer of the public pages. Optional $needsSetup. */
use App\Services\PageBuilder;

$pbSite = PageBuilder::site();
?>
<style>
  .pub-footer{padding:36px max(16px,5%) calc(28px + env(safe-area-inset-bottom));background:<?= e($pbSite['footer_bg'] ?: '#0f172a') ?>;color:rgba(255,255,255,.6);font-family:'Inter',system-ui,sans-serif;}
  .pub-footer-inner{max-width:1100px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:20px;}
  .pub-footer-brand{max-width:360px;}
  .pub-footer-brand strong{display:block;margin-bottom:6px;font-size:1rem;font-weight:900;color:#fff;}
  .pub-footer-brand p{font-size:.84rem;line-height:1.6;}
  .pub-footer-links{display:flex;flex-wrap:wrap;gap:6px 18px;}
  .pub-footer-links a{font-size:.84rem;font-weight:600;color:rgba(255,255,255,.75);text-decoration:none;}
  .pub-footer-links a:hover{color:#fff;}
  .pub-footer-bottom{max-width:1100px;margin:24px auto 0;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);font-size:.78rem;}
</style>
<footer class="pub-footer">
  <div class="pub-footer-inner">
    <div class="pub-footer-brand">
      <strong><?= e(appName()) ?></strong>
      <p style="white-space:pre-line"><?= e($pbSite['footer_text']) ?></p>
    </div>
    <nav class="pub-footer-links" aria-label="Footer">
      <?php foreach ($pbSite['footer_links'] as $footLink): if (($footLink['label'] ?? '') === '' || ($footLink['url'] ?? '') === '') { continue; } ?>
        <a href="<?= e(PageBuilder::href($footLink['url'])) ?>"><?= e($footLink['label']) ?></a>
      <?php endforeach; ?>
      <?php if (!empty($needsSetup)): ?>
        <a href="<?= url('/setup') ?>">Run setup</a>
      <?php else: ?>
        <a href="<?= url('/admin/login') ?>">Super Admin</a>
      <?php endif; ?>
    </nav>
  </div>
  <p class="pub-footer-bottom">&copy; <?= date('Y') ?> <?= e(appName()) ?>. <?= e($pbSite['footer_bottom']) ?></p>
</footer>
