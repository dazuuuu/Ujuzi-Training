<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Models\LoginAudit;

/** Who logged in and when (main Super Admins only — not listed in the grantable sections). */
class AuditTrailController extends BaseAdminController
{
    public function index(): void
    {
        $per = 50;
        $page = max(1, (int) Request::query('page', 1));
        $q = trim((string) Request::query('q', ''));
        $status = (string) Request::query('status', '');
        $type = (string) Request::query('type', '');
        $found = LoginAudit::search($q, $status, $type, $per, ($page - 1) * $per);
        View::render('admin.audit-trail.index', [
            'pageTitle' => 'Audit trail',
            'activeNav' => 'audit-trail',
            'rows' => $found['rows'],
            'total' => $found['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($found['total'] / $per)),
            'q' => $q, 'status' => $status, 'type' => $type,
        ]);
    }
}
