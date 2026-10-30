<?php
require __DIR__ . '/_head.php';
// No details typed into this section: use the contact details from Navbar & footer.
$contactItems = array_values(array_filter($c['items'] ?? [], static fn(array $i): bool => ($i['value'] ?? '') !== ''));
if (!$contactItems) {
    $site = \App\Services\PageBuilder::site();
    $digits = static fn(string $n): string => preg_replace('/[^0-9+]/', '', $n);
    foreach ([
        ['Phone', $site['contact_phone'] ?? '', 'tel:' . $digits((string) ($site['contact_phone'] ?? ''))],
        ['Email', $site['contact_email'] ?? '', 'mailto:' . ($site['contact_email'] ?? '')],
        ['WhatsApp', $site['contact_whatsapp'] ?? '', 'https://wa.me/' . ltrim($digits((string) ($site['contact_whatsapp'] ?? '')), '+')],
        ['Visit us', $site['contact_address'] ?? '', ''],
        ['Opening hours', $site['contact_hours'] ?? '', ''],
    ] as [$label, $value, $link]) {
        if (trim((string) $value) !== '') {
            $contactItems[] = ['label' => $label, 'value' => $value, 'link' => $link];
        }
    }
}
?>
<div class="pb-grid" style="--pb-cols:3">
  <?php foreach ($contactItems as $item): if (($item['value'] ?? '') === '') { continue; } ?>
    <div class="pb-card">
      <p style="font-size:.7em;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:var(--pb-accent)"><?= e($item['label'] ?? '') ?></p>
      <h3><?php if (($item['link'] ?? '') !== ''): ?><a href="<?= $pbHref($item['link']) ?>" style="text-decoration:underline"><?= e($item['value']) ?></a><?php else: ?><?= e($item['value']) ?><?php endif; ?></h3>
    </div>
  <?php endforeach; ?>
</div>
