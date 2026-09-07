<?php
/**
 * Mirrors src/components/SizeGuideModal.tsx. Content is static, so it's
 * pre-rendered here and simply shown/hidden by assets/js/app.js.
 */
?>
<div id="size-guide-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
  <div class="fixed inset-0 bg-black/70 backdrop-blur-xs" data-close-size-guide></div>

  <div class="relative w-full max-w-2xl bg-[#faf9f6] rounded-xs shadow-2xl z-10 p-6 sm:p-8 space-y-6 border border-neutral-200">
    <div class="flex items-center justify-between border-b pb-4 border-neutral-300">
      <div class="flex items-center space-x-2">
        <svg class="w-5 h-5 text-neutral-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>
        <h3 class="font-serif-heading text-2xl font-bold text-neutral-900">PENTAGON SIZE &amp; FIT GUIDE</h3>
      </div>
      <button data-close-size-guide class="p-2 text-neutral-500 hover:text-black">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </div>

    <p class="text-xs text-neutral-600 font-light leading-relaxed">
      Our silhouettes are designed with architectural ease. For an oversized tailored drape as shown in our lookbooks, take your true size. For a more traditional fitted profile, consider sizing down one size.
    </p>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="border-b-2 border-neutral-800 text-neutral-900 uppercase font-bold text-[10px] tracking-wider">
            <th class="py-2 px-3">Size Tag</th>
            <th class="py-2 px-3">Bust (in)</th>
            <th class="py-2 px-3">Waist (in)</th>
            <th class="py-2 px-3">Hips (in)</th>
            <th class="py-2 px-3">EU</th>
            <th class="py-2 px-3">UK / AU</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-200 text-neutral-700 font-mono">
          <?php
          $sizeRows = [
              ['XS', '31 - 33"', '24 - 25"', '34 - 35"', '34', '6'],
              ['S', '33 - 35"', '26 - 27"', '36 - 37"', '36', '8'],
              ['M', '35 - 37"', '28 - 29"', '38 - 39"', '38', '10'],
              ['L', '37 - 39"', '30 - 32"', '40 - 42"', '40', '12'],
              ['XL', '39 - 42"', '33 - 35"', '43 - 45"', '42', '14'],
          ];
          foreach ($sizeRows as $row): ?>
            <tr>
              <td class="py-2.5 px-3 font-bold text-black font-sans"><?= e($row[0]) ?></td>
              <td class="py-2.5 px-3"><?= e($row[1]) ?></td>
              <td class="py-2.5 px-3"><?= e($row[2]) ?></td>
              <td class="py-2.5 px-3"><?= e($row[3]) ?></td>
              <td class="py-2.5 px-3"><?= e($row[4]) ?></td>
              <td class="py-2.5 px-3"><?= e($row[5]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="p-3 bg-neutral-200/60 rounded-xs text-[11px] text-neutral-600">
      <strong>Need specific measurements?</strong> Contact our styling concierges anytime at concierge@pentagoncollections.com.
    </div>
  </div>
</div>
