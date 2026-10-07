<?php
/** One student's full record. Requires $record (StudentLookup::find). */
use App\Models\AttachmentApplication;

$s = $record['student'];
$name = userDisplayName($s);
$photo = (string) ($s['photo_path'] ?? '');
$fees = $record['fees'];
$ksh = static fn(float $n): string => 'Ksh ' . number_format($n, 2);
$date = static fn(?string $at): string => $at ? date('j M Y', strtotime($at)) : '—';
?>
<div class="space-y-5">
  <section class="learn-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
    <?php if ($photo !== ''): ?>
      <img src="<?= e(imageUrl($photo)) ?>" alt="<?= e($name) ?>" class="h-24 w-24 shrink-0 rounded-full object-cover">
    <?php else: ?>
      <span class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full text-3xl font-black text-white" style="background:var(--ke-green)" aria-label="No profile picture"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
    <?php endif; ?>
    <div class="min-w-0 flex-1">
      <p class="text-[11px] font-black uppercase tracking-widest text-neutral-500">Student</p>
      <h2 class="text-2xl font-black text-gray-900"><?= e($name) ?></h2>
      <p class="text-sm font-black tracking-wide" style="color:var(--ke-green)"><?= e($s['registration_number'] ?? '') ?></p>
      <p class="mt-1 text-xs font-semibold text-neutral-500">Registered <?= e($date($s['created_at'] ?? null)) ?> · Account <?= e($s['account_status'] ?? 'active') ?></p>
    </div>
    <?php if ($record['certificates']): ?>
      <span class="inline-flex items-center gap-1.5 self-start rounded-full px-3 py-1 text-xs font-black uppercase" style="background:#ecf7f0;color:var(--ke-green)"><?= icon('shield', 'h-4 w-4') ?> <?= count($record['certificates']) ?> genuine certificate<?= count($record['certificates']) === 1 ? '' : 's' ?></span>
    <?php else: ?>
      <span class="inline-flex items-center gap-1.5 self-start rounded-full px-3 py-1 text-xs font-black uppercase" style="background:#fffbeb;color:#92400e"><?= icon('alert', 'h-4 w-4') ?> No certificate yet</span>
    <?php endif; ?>
  </section>

  <div class="grid gap-5 lg:grid-cols-3">
    <section class="learn-card p-5">
      <h3 class="text-sm font-black uppercase tracking-wider text-gray-800">Details</h3>
      <dl class="mt-3 space-y-2 text-sm">
        <div><dt class="text-[11px] font-black uppercase text-neutral-500">Email</dt><dd class="font-semibold break-words"><?= e($s['email'] ?: '—') ?></dd></div>
        <div><dt class="text-[11px] font-black uppercase text-neutral-500">Phone</dt><dd class="font-semibold"><?= e($s['phone'] ?: '—') ?></dd></div>
        <?php foreach ($record['details'] as $detail): ?>
          <div><dt class="text-[11px] font-black uppercase text-neutral-500"><?= e($detail['label']) ?></dt><dd class="font-semibold break-words"><?= e($detail['value']) ?></dd></div>
        <?php endforeach; ?>
      </dl>
    </section>

    <div class="space-y-5 lg:col-span-2">
      <section class="learn-card p-5">
        <h3 class="text-sm font-black uppercase tracking-wider text-gray-800">Certificates</h3>
        <?php if (!$record['certificates']): ?>
          <p class="mt-2 text-sm font-semibold text-neutral-500">None yet. A certificate showing this registration number is not genuine.</p>
        <?php else: ?>
          <ul class="mt-2 divide-y" style="border-color:var(--ke-line)">
            <?php foreach ($record['certificates'] as $course): ?>
              <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                <span class="min-w-0"><span class="block font-bold text-gray-800"><?= e($course['title']) ?></span><span class="block text-xs font-semibold text-neutral-500"><?= e($course['organisation_name'] ?? '') ?></span></span>
                <span class="text-xs font-bold text-neutral-600">Completed <?= e($date($course['completed_on'] ?? null)) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <section class="learn-card p-5">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
          <h3 class="text-sm font-black uppercase tracking-wider text-gray-800">Courses</h3>
          <?php if ($fees && $fees['courses']): ?>
            <p class="text-xs font-bold text-neutral-600">Paid <?= e($ksh($fees['paid'])) ?> of <?= e($ksh($fees['fee'])) ?> · <span style="color:<?= $fees['balance'] > 0 ? 'var(--ke-red)' : 'var(--ke-green)' ?>">balance <?= e($ksh($fees['balance'])) ?></span></p>
          <?php endif; ?>
        </div>
        <?php if (!$fees || !$fees['items']): ?>
          <p class="mt-2 text-sm font-semibold text-neutral-500">Not enrolled in any course.</p>
        <?php else: ?>
          <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="text-[11px] font-black uppercase text-neutral-500"><tr><th class="py-1.5 pr-3">Course</th><th class="py-1.5 pr-3">Progress</th><th class="py-1.5 pr-3">Paid</th><th class="py-1.5">Balance</th></tr></thead>
              <tbody class="divide-y">
                <?php foreach ($fees['items'] as $item): ?>
                  <tr>
                    <td class="py-1.5 pr-3 font-bold text-gray-800"><?= e($item['title']) ?><?= $item['final_passed'] ? ' <span class="text-xs" style="color:var(--ke-green)">· completed</span>' : '' ?></td>
                    <td class="py-1.5 pr-3 font-semibold whitespace-nowrap"><?= (int) $item['progress'] ?>% · <?= (int) $item['modules_passed'] ?>/<?= (int) $item['modules_total'] ?> modules</td>
                    <td class="py-1.5 pr-3 font-semibold whitespace-nowrap"><?= e($ksh($item['paid'])) ?> / <?= e($ksh($item['fee'])) ?></td>
                    <td class="py-1.5 font-black whitespace-nowrap" style="color:<?= $item['balance'] > 0 ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= e($ksh($item['balance'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="learn-card p-5">
        <h3 class="text-sm font-black uppercase tracking-wider text-gray-800">Organisations</h3>
        <?php if (!$record['memberships']): ?>
          <p class="mt-2 text-sm font-semibold text-neutral-500">Hasn't joined an organisation providing courses.</p>
        <?php endif; ?>
        <ul class="mt-2 space-y-1.5">
          <?php foreach ($record['memberships'] as $m): ?>
            <li class="text-sm"><span class="font-bold text-gray-800"><?= e($m['organisation_name']) ?></span><?= !empty($m['branch_title']) ? ' · ' . e($m['branch_title']) : '' ?> <span class="text-xs font-black uppercase" style="color:<?= $m['status'] === 'approved' ? 'var(--ke-green)' : 'var(--ke-red)' ?>"><?= e($m['status']) ?></span><?= $m['category_names'] ? '<span class="block text-xs font-semibold text-neutral-500">' . e(implode(', ', $m['category_names'])) . '</span>' : '' ?></li>
          <?php endforeach; ?>
        </ul>
      </section>

      <section class="learn-card p-5">
        <h3 class="text-sm font-black uppercase tracking-wider text-gray-800">Attachments</h3>
        <?php if (!$record['attachments']): ?>
          <p class="mt-2 text-sm font-semibold text-neutral-500">No attachment requests.</p>
        <?php endif; ?>
        <ul class="mt-2 divide-y">
          <?php foreach ($record['attachments'] as $a): ?>
            <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
              <span class="min-w-0"><span class="block font-bold text-gray-800"><?= e($a['organisation_name'] ?: trim($a['first_name'] . ' ' . $a['last_name'])) ?><?= !empty($a['branch_title']) ? ' · ' . e($a['branch_title']) : '' ?></span><span class="block text-xs font-semibold text-neutral-500"><?= e($a['category_name'] ?? '') ?> · requested <?= e($date($a['selected_at'] ?? null)) ?></span></span>
              <span class="text-xs font-black uppercase" style="color:<?= in_array($a['status'], ['rejected', 'paused'], true) ? 'var(--ke-red)' : 'var(--ke-green)' ?>"><?= e(AttachmentApplication::statusLabel((string) $a['status'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    </div>
  </div>
</div>
