<?php /** Requires $branches, $categories, $smsReady, $form. */ require __DIR__ . '/../layout-header.php'; ?>
<div class="max-w-2xl space-y-4">
  <h1 class="font-serif-heading text-2xl font-bold">New announcement</h1>
  <form method="post" enctype="multipart/form-data" action="<?= url('/account/announcements') ?>" class="space-y-4 rounded-xl border bg-white p-5" style="border-color:var(--ke-line)">
    <?= csrfField() ?>
    <label class="block text-[11px] font-black uppercase tracking-wide text-neutral-600">Title
      <input name="title" required maxlength="200" value="<?= e($form['title']) ?>" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm normal-case font-semibold">
    </label>
    <label class="block text-[11px] font-black uppercase tracking-wide text-neutral-600">Message
      <textarea name="body" rows="6" class="mt-1 w-full rounded-lg border border-neutral-300 p-2.5 text-sm normal-case font-medium"><?= e($form['body']) ?></textarea>
    </label>
    <label class="block text-[11px] font-black uppercase tracking-wide text-neutral-600">Attach a document or picture (PDF, JPG or PNG)
      <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm">
    </label>
    <fieldset>
      <legend class="text-[11px] font-black uppercase tracking-wide text-neutral-600">Send to — branches <span class="font-medium normal-case">(none ticked = all)</span></legend>
      <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
        <?php foreach ($branches as $b): ?><label class="flex items-center gap-1.5 text-sm font-semibold"><input type="checkbox" name="branch_ids[]" value="<?= (int) $b['id'] ?>"> <?= e($b['title']) ?></label><?php endforeach; ?>
        <?php if (!$branches): ?><span class="text-sm text-neutral-500">Whole organisation</span><?php endif; ?>
      </div>
    </fieldset>
    <fieldset>
      <legend class="text-[11px] font-black uppercase tracking-wide text-neutral-600">Categories <span class="font-medium normal-case">(none ticked = everyone in the branches above)</span></legend>
      <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
        <?php foreach ($categories as $c): ?><label class="flex items-center gap-1.5 text-sm font-semibold"><input type="checkbox" name="category_ids[]" value="<?= (int) $c['id'] ?>"> <?= e($c['name']) ?></label><?php endforeach; ?>
        <?php if (!$categories): ?><span class="text-sm text-neutral-500">No categories yet.</span><?php endif; ?>
      </div>
    </fieldset>
    <fieldset class="rounded-lg bg-neutral-50 p-3">
      <legend class="px-1 text-[11px] font-black uppercase tracking-wide text-neutral-600">Also alert them by</legend>
      <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="notify_email" value="1" checked> Email — “Kindly check your inbox, you have a new confidential message”</label>
      <label class="mt-1 flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="notify_sms" value="1" <?= $smsReady ? 'checked' : '' ?>> SMS <?= $smsReady ? '' : '<span class="font-medium text-neutral-500">(not set up yet — Super Admin adds the SMS keys in Settings)</span>' ?></label>
      <p class="mt-1 text-xs text-neutral-500">They always get the bell notification and see it in their Announcements inbox.</p>
    </fieldset>
    <div class="flex gap-2"><button class="btn-primary">Send announcement</button><a href="<?= url('/account/announcements') ?>" class="btn-secondary">Cancel</a></div>
  </form>
</div>
<?php require __DIR__ . '/../layout-footer.php'; ?>
