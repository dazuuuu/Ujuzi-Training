<?php if (($c['title'] ?? '') !== ''): ?><h1 class="pb-title" style="font-size:clamp(1.7em,4vw,2.5em)"><?= e($c['title']) ?></h1><?php endif; ?>
<?php if (($c['text'] ?? '') !== ''): ?><p class="pb-text" style="margin-left:auto;margin-right:auto"><?= e($c['text']) ?></p><?php endif; ?>
<form class="pb-search" role="search" onsubmit="event.preventDefault(); if (window.filterCourses) filterCourses();">
  <input type="text" id="courseSearch" placeholder="<?= e($c['placeholder'] ?? '') ?>" aria-label="Search courses" oninput="if (window.filterCourses) filterCourses();">
  <button type="submit">Search</button>
</form>
