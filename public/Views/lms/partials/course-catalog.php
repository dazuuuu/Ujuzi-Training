<?php
/**
 * The public course catalog: search-filtered cards that open a detail
 * modal. Placed by the "All public courses" section on the courses page.
 * Requires $courses and $pbSection.
 */
$catContent = $pbSection['content'] ?? [];
$catCols = (int) ($catContent['columns'] ?? 3) ?: 3;
$catSize = (string) ($catContent['card_size'] ?? 'md');
?>
<div class="courses-container cat-size-<?= e($catSize) ?>" style="--cat-cols:<?= $catCols ?>;<?= ($pbSection['style']['width'] ?? '') === 'full' ? 'max-width:none;' : '' ?>">
  <div class="courses-header">
    <h2><?= e($catContent['title'] ?? 'Public Courses') ?></h2>
    <span id="courseCount"><?= count($courses) ?> courses available</span>
  </div>

  <div class="courses-grid" id="coursesGrid">
    <?php if (empty($courses)): ?>
      <div class="empty-state">
        <span class="icon">📚</span>
        <h3>No Public Courses Yet</h3>
        <p>Public courses will appear here once organisations publish them. Sign in to access your organisation's private courses.</p>
        <a href="<?= url('/account/login') ?>" class="btn-green">Sign In to Access Courses</a>
      </div>
    <?php else: ?>
      <?php
      $gradients = [
        'linear-gradient(135deg,#0f172a,#1d4ed8)',
        'linear-gradient(135deg,#166534,#16a34a)',
        'linear-gradient(135deg,#4c1d95,#7c3aed)',
        'linear-gradient(135deg,#7c2d12,#ea580c)',
        'linear-gradient(135deg,#0f172a,#166534)',
        'linear-gradient(135deg,#7f1d1d,#dc2626)',
      ];
      $icons = ['💻','📊','🎨','📣','📈','🌱','🔬','🎵','✈️','🏗️'];
      $avColors = ['#dc2626','#16a34a','#7c3aed','#ea580c','#0891b2','#ca8a04'];

      foreach ($courses as $idx => $course):
        $grad = $gradients[$idx % count($gradients)];
        $icon = $icons[$idx % count($icons)];
        $avColor = $avColors[$idx % count($avColors)];
        $tutorName = trim(($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? ''));
        if (!$tutorName) $tutorName = $course['trainer_name'] ?? ($course['created_by_name'] ?? 'Expert Instructor');
        $catName = $course['category_name'] ?? 'General';
        $orgName = $course['organisation_name'] ?? '';
        $feeKsh = (float) ($course['enrollment_fee_ksh'] ?? 0);
        $price = $feeKsh > 0 ? 'Ksh ' . number_format($feeKsh) : 'Free';
        $isFree = $feeKsh == 0;
        $coverImage = $course['cover_image'] ?? null;
        $description = $course['description'] ?? '';
        $videoUrl = $course['introduction_embed_url'] ?? ($course['introduction_video_url'] ?? null);
        $lessonCount = $course['lesson_count'] ?? null;
        $durationHours = $course['duration_hours'] ?? null;
        $courseId = $course['id'] ?? $idx;
      ?>
        <div class="c-card"
             data-cat="<?= e($catName) ?>"
             data-title="<?= e(strtolower($course['title'] ?? '')) ?>"
             style="cursor:default;">

          <!-- Image / thumbnail -->
          <div class="c-card-img" style="background:<?= $grad ?>;">
            <?php if ($coverImage): ?>
              <img src="<?= e(imageUrl($coverImage)) ?>" alt="<?= e($course['title'] ?? '') ?>" loading="lazy">
            <?php else: ?>
              <div class="c-card-img-placeholder"><?= $icon ?></div>
            <?php endif; ?>
            <!-- Play overlay hint (if has video) -->
            <?php if ($videoUrl): ?>
              <div class="c-card-play-overlay">
                <div class="c-card-play-btn">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="#dc2626"><path d="M5 3l14 9-14 9V3z"/></svg>
                </div>
              </div>
            <?php endif; ?>
            <!-- Badges -->
            <span class="c-card-badge <?= $isFree ? '' : 'paid' ?>"><?= $isFree ? 'Free' : 'Paid' ?></span>
            <?php if ($orgName): ?>
              <span class="c-card-org-badge"><?= e($orgName) ?></span>
            <?php endif; ?>
          </div>

          <!-- Card body -->
          <div class="c-card-body">
            <div class="c-card-cat"><?= e($catName) ?></div>
            <div class="c-card-title"><?= e($course['title'] ?? 'Untitled Course') ?></div>
            <?php if ($description): ?>
              <div class="c-card-desc"><?= e($description) ?></div>
            <?php endif; ?>
            <div class="c-card-tutor">
              <div class="c-card-av" style="background:<?= $avColor ?>;"><?= strtoupper(substr($tutorName,0,1)) ?></div>
              <?= e($tutorName) ?>
            </div>
            <?php if ($lessonCount || $durationHours): ?>
            <div class="c-card-meta">
              <?php if ($lessonCount): ?><span>📖 <?= $lessonCount ?> lessons</span><?php endif; ?>
              <?php if ($durationHours): ?><span>⏱ <?= $durationHours ?>h</span><?php endif; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Card footer -->
          <div class="c-card-footer">
            <div class="c-card-price <?= $isFree ? 'free' : '' ?>"><?= $price ?></div>
            <div class="c-card-actions">
              <button class="btn-view-more"
                      onclick="openCourseModal(<?= $courseId ?>)"
                      data-id="<?= $courseId ?>">
                View More
              </button>
              <a href="<?= url('/account/login') ?>" class="btn-enroll">Enroll</a>
            </div>
          </div>
        </div>

        <!-- Hidden data for modal -->
        <script type="application/json" id="course-data-<?= $courseId ?>">
        <?= json_encode([
          'id'          => $courseId,
          'title'       => $course['title'] ?? 'Untitled Course',
          'description' => $description,
          'category'    => $catName,
          'organisation'=> $orgName,
          'tutor'       => $tutorName,
          'tutorColor'  => $avColor,
          'price'       => $price,
          'isFree'      => $isFree,
          'cover'       => $coverImage ? imageUrl($coverImage) : null,
          'gradient'    => $grad,
          'icon'        => $icon,
          'videoUrl'    => $videoUrl,
          'lessons'     => $lessonCount,
          'hours'       => $durationHours,
          'loginUrl'    => url('/account/login'),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
        </script>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
