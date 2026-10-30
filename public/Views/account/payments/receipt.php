<?php
/** A standalone printable receipt. Requires $receipt. "Download" = the browser's Save as PDF. */
$r = $receipt;
$platform = (string) (\App\Models\StoreSetting::get('platform_name', '') ?: 'Ujuzi');
// Ujuzi's logo and contact details (from Super Admin's site settings), so the receipt is clearly genuine.
$logo = storeLogoPath();
try {
    $site = \App\Services\PageBuilder::site();
} catch (\Throwable $e) {
    $site = [];
}
$contacts = array_filter([
    trim((string) ($site['contact_address'] ?? '')),
    trim((string) ($site['contact_phone'] ?? '')) !== '' ? 'Tel: ' . trim((string) $site['contact_phone']) : '',
    trim((string) ($site['contact_email'] ?? '')),
]);
$website = preg_replace('#^https?://#', '', rtrim(\App\Core\Url::absolute('/'), '/'));
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt <?= e($r['receipt_no']) ?></title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4f4f5;color:#111;margin:0;padding:16px}
  .receipt{max-width:560px;margin:0 auto;background:#fff;border:1px solid #ddd;border-radius:12px;padding:28px}
  .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #006b3f;padding-bottom:12px;margin-bottom:16px}
  .head h1{margin:0;font-size:20px}.muted{color:#666;font-size:13px}
  .brand{display:flex;gap:12px;align-items:center;min-width:0}
  .brand img{height:56px;width:auto;max-width:120px;object-fit:contain}
  .brand .mark{width:52px;height:52px;border-radius:10px;background:#006b3f;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:24px}
  .contacts{font-size:12px;color:#555;margin-top:2px;line-height:1.4}
  .title{display:flex;justify-content:space-between;align-items:center;margin:0 0 12px}
  .title h2{margin:0;font-size:15px;letter-spacing:.12em;text-transform:uppercase;color:#006b3f}
  .stamp{border:2px solid #006b3f;color:#006b3f;border-radius:6px;padding:2px 10px;font-weight:800;letter-spacing:.15em;transform:rotate(-6deg);font-size:13px}
  .foot{margin-top:24px;padding-top:12px;border-top:1px dashed #ccc;font-size:12px;color:#666;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
  @media (max-width:480px){.head{flex-direction:column;gap:10px}.head>div:last-child{text-align:left!important}.receipt{padding:18px}}
  table{width:100%;border-collapse:collapse;font-size:14px}td{padding:8px 0;border-bottom:1px solid #eee;vertical-align:top}td:first-child{color:#666;width:38%}
  .total{font-size:22px;font-weight:800;text-align:right;margin-top:16px}
  .actions{max-width:560px;margin:12px auto;display:flex;gap:8px;justify-content:flex-end}
  .actions a,.actions button{font:inherit;font-weight:700;padding:8px 14px;border-radius:8px;border:1px solid #006b3f;background:#006b3f;color:#fff;cursor:pointer;text-decoration:none}
  .actions a{background:#fff;color:#006b3f}
  @media print{body{background:#fff;padding:0}.actions{display:none}.receipt{border:0}}
</style></head>
<body>
<div class="actions"><a href="<?= url('/account/payments') ?>">Payment history</a><button onclick="window.print()">Print / Save PDF</button></div>
<div class="receipt">
  <div class="head">
    <div class="brand">
      <?php if ($logo): ?><img src="<?= e(imageUrl($logo)) ?>" alt="<?= e($platform) ?> logo"><?php else: ?><span class="mark"><?= e(mb_strtoupper(mb_substr($platform, 0, 1))) ?></span><?php endif; ?>
      <div>
        <h1><?= e($platform) ?></h1>
        <div class="contacts">
          <?php foreach ($contacts as $line): ?><?= e($line) ?><br><?php endforeach; ?>
          <?= e($website) ?>
        </div>
      </div>
    </div>
    <div style="text-align:right"><strong><?= e($r['receipt_no']) ?></strong><div class="muted"><?= e(date('j M Y, H:i', strtotime((string) $r['created_at']))) ?></div></div>
  </div>
  <div class="title"><h2>Official payment receipt</h2><span class="stamp">PAID</span></div>
  <table>
    <tr><td>Received from</td><td><?= e(userFullName($r)) ?></td></tr>
    <?php if (!empty($r['registration_number'])): ?><tr><td>Reg. number</td><td><?= e($r['registration_number']) ?></td></tr><?php endif; ?>
    <tr><td>Contact</td><td><?= e(trim(($r['email'] ?? '') . ' · ' . ($r['phone'] ?? ''), ' ·')) ?></td></tr>
    <tr><td>Type</td><td><?= $r['kind'] === 'deposit' ? 'Wallet deposit' : 'Course payment' ?></td></tr>
    <tr><td>For</td><td><?= e($r['description']) ?></td></tr>
    <?php if (!empty($r['course_title'])): ?><tr><td>Course</td><td><?= e($r['course_title']) ?></td></tr><?php endif; ?>
    <?php if (!empty($r['reference'])): ?><tr><td>Reference</td><td><?= e($r['reference']) ?></td></tr><?php endif; ?>
  </table>
  <div class="total">Ksh <?= number_format((float) $r['amount_ksh'], 2) ?></div>
  <p class="muted" style="margin-top:20px">Thank you for your payment.</p>
  <div class="foot">
    <span>Issued by <?= e($platform) ?> · <?= e($website) ?></span>
    <span>Receipt no. <?= e($r['receipt_no']) ?> — computer generated, valid without a signature.</span>
  </div>
</div>
</body></html>
