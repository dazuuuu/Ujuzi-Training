<?php
/**
 * Public landing page. Its sections, words, pictures and layout come from
 * Super Admin → Pages (see App\Services\PageBuilder). Requires $needsSetup.
 */
use App\Services\HomepageContent;

$pbPage = 'home';
$pbTitle = appName() . ' | ' . HomepageContent::get('hero_title_1') . ' ' . HomepageContent::get('hero_title_2');
$pbDescription = HomepageContent::get('hero_text');
require __DIR__ . '/partials/pb-page.php';
