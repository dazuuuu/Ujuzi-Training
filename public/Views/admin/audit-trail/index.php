<?php
/** Requires $rows, $total, $page, $pages, $q, $status, $type. */
require __DIR__ . '/../layout-header.php';
$qs = fn(int $p): string => url('/admin/audit-trail?' . http_build_query(['q' => $q, 'page' => $p] + array_filter(['status' => $status, 'type' => $type])));
?>
<div class="space-y-4">
  <section class="rounded-lg border border-neutral-200 bg-white p-4">
    <h2 class="text-sm font-black uppercase tracking-wide text-neutral-900">Audit trail</h2>
    <p class="text-xs text-neutral-500">Every sign-in to the system: who, when, from which address and device, and failed attempts. <?= (int) $total ?> entries.</p>
    <form method="get" action="<?= url('/admin/audit-trail') ?>" class="mt-3 flex flex-wrap gap-2">
      <input name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone or IP" class="input" style="min-width:240px">
      <select name="type" class="input"><option value="">Everyone</option><option value="user" <?= $type === 'user' ? 'selected' : '' ?>>Users</option><option value="admin" <?= $type === 'admin' ? 'selected' : '' ?>>Admins</option></select>
      <select name="status" class="input"><option value="">All results</option><option value="ok" <?= $status === 'ok' ? 'selected' : '' ?>>Successful</option><option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option></select>
      <button class="btn-primary">Filter</button>
    </form>
  </section>
  <section class="overflow-x-auto rounded-lg border border-neutral-200 bg-white">
    <table class="w-full text-left text-xs">
      <thead class="bg-neutral-50 text-[10px] uppercase tracking-wide text-neutral-500"><tr>
        <th class="p-3">When</th><th class="p-3">Who</th><th class="p-3">Role</th><th class="p-3">Result</th><th class="p-3">IP address</th><th class="p-3">Device</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr class="border-t border-neutral-100">
          <td class="p-3 whitespace-nowrap"><?= e(date('d M Y H:i:s', strtotime((string) $r['created_at']))) ?></td>
          <td class="p-3"><strong><?= e($r['display_name'] ?: '—') ?></strong><br><span class="text-neutral-500"><?= e($r['identifier'] ?? '') ?></span></td>
          <td class="p-3"><?= $r['actor_type'] === 'admin' ? 'Super Admin' : e(str_replace('_', ' ', (string) ($r['role_slug'] ?: 'user'))) ?></td>
          <td class="p-3"><?php if ($r['success']): ?><span style="color:#166534;font-weight:700">Signed in</span><?php else: ?><span style="color:#b91c1c;font-weight:700">Failed</span><?php endif; ?><?= $r['detail'] ? '<br><span class="text-neutral-500">' . e($r['detail']) . '</span>' : '' ?></td>
          <td class="p-3"><?= e($r['ip'] ?? '') ?></td>
          <td class="p-3 text-neutral-500" title="<?= e($r['user_agent'] ?? '') ?>"><?= e(mb_substr((string) ($r['user_agent'] ?? ''), 0, 60)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="p-6 text-center text-neutral-500">No sign-ins recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </section>
  <?php if ($pages > 1): ?>
    <div class="flex items-center justify-between text-xs">
      <?php if ($page > 1): ?><a class="btn-secondary" href="<?= $qs($page - 1) ?>">← Newer</a><?php else: ?><span></span><?php endif; ?>
      <span>Page <?= (int) $page ?> of <?= (int) $pages ?></span>
      <?php if ($page < $pages): ?><a class="btn-secondary" href="<?= $qs($page + 1) ?>">Older →</a><?php else: ?><span></span><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
