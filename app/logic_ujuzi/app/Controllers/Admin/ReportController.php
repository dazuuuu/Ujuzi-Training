<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\View;
use App\Services\AttachmentReport;
use App\Services\PdfTable;
use App\Services\ReportService;
use App\Services\SpreadsheetExport;

/** Super Admin → Reports: one section per kind of data, each downloadable as Excel or PDF. */
class ReportController extends BaseAdminController
{
    private const PREVIEW_ROWS = 50;

    public function index(): void
    {
        $section = (string) Request::query('section', 'students');
        if (!isset(ReportService::SECTIONS[$section])) {
            $section = 'students';
        }
        [$from, $to] = AttachmentReport::dateRange();
        $report = ReportService::build($section, $from, $to);

        $view = Request::query('view', '') === 'charts' || Request::query('section', '') === '' ? 'charts' : 'table';
        View::render('admin.reports.index', [
            'view' => $view,
            'reportCharts' => $view === 'charts' ? \App\Services\ReportCharts::build([], $from, $to) : [],
            'pageTitle' => 'Reports',
            'activeNav' => 'reports',
            'sections' => ReportService::SECTIONS,
            'section' => $section,
            'report' => $report,
            'preview' => array_slice($report['rows'], 0, self::PREVIEW_ROWS),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function download(string $section, string $format): void
    {
        if (!isset(ReportService::SECTIONS[$section]) || !in_array($format, ['xlsx', 'pdf'], true)) {
            flashError('That report could not be found.');
            redirect('/admin/reports');
        }
        [$from, $to] = AttachmentReport::dateRange();
        $report = ReportService::build($section, $from, $to);
        $range = $from !== '' || $to !== '' ? ($from ?: 'start') . '-to-' . ($to ?: date('Y-m-d')) : date('Y-m-d');
        $name = 'report-' . $section . '-' . $range;
        $rows = $report['rows'];

        if ($format === 'pdf') {
            $subtitle = appName() . ' · ' . count($rows) . ' record' . (count($rows) === 1 ? '' : 's')
                . ($from !== '' || $to !== '' ? ' · ' . ($from ?: 'start') . ' to ' . ($to ?: date('Y-m-d')) : '')
                . ' · generated ' . date('j M Y H:i');
            PdfTable::download($name, $report['title'], $subtitle, $report['headers'], $rows, $report['totals']);
        }
        if ($report['totals']) {
            $rows[] = $report['totals'];
        }
        SpreadsheetExport::download($name, $report['title'], $report['headers'], $rows);
    }

    /** Organisation activity: pick the kind of organisation, one (or all), a branch (or all), status, finance and dates. */
    public function activity(): void
    {
        $filters = \App\Services\ActivityReport::filters($_GET);
        $report = \App\Services\ActivityReport::build($filters);
        View::render('admin.reports.activity', [
            'pageTitle' => 'Reports',
            'activeNav' => 'reports',
            'filters' => $filters,
            'report' => $report,
            'preview' => array_slice($report['rows'], 0, 200),
            'organisations' => \App\Services\ActivityReport::organisations($filters['type']),
            'branches' => \App\Services\ActivityReport::branches($filters['type'], $filters['org']),
        ]);
    }

    public function activityDownload(string $format): void
    {
        $filters = \App\Services\ActivityReport::filters($_GET);
        $report = \App\Services\ActivityReport::build($filters);
        $name = 'activity-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($report['title'] . '-' . ($filters['status'] ?: 'all') . '-' . date('Y-m-d')));
        $rows = $report['rows'];
        if ($format === 'pdf') {
            PdfTable::download($name, $report['title'], appName() . ' · ' . $report['subtitle'] . ' · ' . count($rows) . ' record(s) · generated ' . date('j M Y H:i'), $report['headers'], $rows, $report['totals'], true);
        }
        $rows[] = $report['totals'];
        SpreadsheetExport::download($name, 'Activity', $report['headers'], $rows);
    }
}
