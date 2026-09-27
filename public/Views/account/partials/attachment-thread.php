<?php
/**
 * The notes on one attachment request, oldest first.
 * Requires $messages (AttachmentMessage::forApplication) and $viewerSide
 * ('student' or 'reviewer'), so the viewer's own notes line up on the right.
 */
use App\Models\AttachmentApplication;

$statusWords = [
    'accepted' => 'accepted the request',
    'paused' => 'put the request on hold',
    'rejected' => 'declined the request',
    'recommended' => 'marked the attachment completed',
    'letter_resent' => 'resent the recommendation letter',
];
?>
<?php if (!$messages): ?>
  <p class="rounded-xl border border-dashed p-4 text-sm font-bold" style="border-color:var(--ke-line);color:var(--ke-muted)">No notes yet.</p>
<?php else: ?>
  <ol class="space-y-3" aria-label="Notes">
    <?php foreach ($messages as $message):
      $mine = $message['sender_side'] === $viewerSide;
      $who = $message['sender_side'] === 'student'
          ? ($mine ? 'You' : (trim(($message['first_name'] ?? '') . ' ' . ($message['last_name'] ?? '')) ?: 'Student'))
          : ($mine ? 'You' : 'Attachment provider');
      $change = $message['status_change'] ?? null;
    ?>
      <li class="flex <?= $mine ? 'justify-end' : 'justify-start' ?>">
        <div class="max-w-[85%] rounded-xl border px-4 py-3 shadow-sm" style="border-color:var(--ke-line);background:<?= $mine ? '#f0fdf4' : '#ffffff' ?>">
          <p class="text-[11px] font-black uppercase tracking-wider text-neutral-500">
            <?= e($who) ?> · <?= e(date('j M Y, H:i', strtotime((string) $message['created_at']))) ?>
          </p>
          <?php if ($change): ?>
            <p class="mt-1 text-xs font-black" style="color:<?= $change === 'rejected' ? 'var(--ke-red)' : 'var(--ke-green)' ?>">
              <?= e(($mine ? 'You ' : '') . ($statusWords[$change] ?? 'changed the status to ' . AttachmentApplication::statusLabel($change))) ?>
            </p>
          <?php endif; ?>
          <?php if (trim((string) $message['body']) !== ''): ?>
            <?php // Escape first, then link bare http(s) URLs — nothing typed can add markup. ?>
            <p class="mt-1 whitespace-pre-line break-words text-sm text-neutral-800"><?= preg_replace('#https?://[^\s<]+#', '<a href="$0" class="font-bold underline" style="color:var(--ke-green)">$0</a>', e($message['body'])) ?></p>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
