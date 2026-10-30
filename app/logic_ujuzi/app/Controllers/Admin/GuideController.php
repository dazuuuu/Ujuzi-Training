<?php

namespace App\Controllers\Admin;

use App\Core\View;
use App\Services\SystemGuide;

/** Super Admin → System guide & help: how everything works, with a question box that finds the answer. */
class GuideController extends BaseAdminController
{
    public function index(): void
    {
        View::render('admin.guide.index', [
            'pageTitle' => 'System guide & help',
            'activeNav' => 'guide',
            'sections' => SystemGuide::sections(),
        ]);
    }
}
