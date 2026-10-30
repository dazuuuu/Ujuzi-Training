<?php

namespace App\Services;

use App\Models\AttachmentApplication;

/**
 * The attachment-requests spreadsheet — the same columns for Super Admin, an
 * organisation providing attachment, and its branch admins.
 */
class AttachmentReport
{
    public const HEADERS = [
        'Student', 'Registration no.', 'Email', 'Phone', 'Course category', 'Organisation providing courses',
        'Organisation providing attachment', 'Branch', 'Status', 'Requested', 'Accepted', 'Completed',
        'Fees total (Ksh)', 'Paid (Ksh)', 'Balance (Ksh)', 'Paid %',
    ];

    /** @param array $applications rows from AttachmentApplication::report() */
    public static function rows(array $applications): array
    {
        $fees = WalletService::feeSummaries(array_column($applications, 'student_user_id'));
        $date = static fn(?string $at): string => $at ? date('Y-m-d', strtotime($at)) : '';
        $rows = [];
        foreach ($applications as $a) {
            $f = $fees[(int) $a['student_user_id']] ?? null;
            $rows[] = [
                trim($a['first_name'] . ' ' . $a['last_name']),
                (string) ($a['registration_number'] ?? ''),
                (string) $a['email'],
                (string) ($a['phone'] ?? ''),
                (string) ($a['category_name'] ?? ''),
                (string) ($a['category_organisation_name'] ?? ''),
                (string) ($a['provider_organisation_name'] ?: trim($a['provider_first_name'] . ' ' . $a['provider_last_name'])),
                (string) ($a['branch_title'] ?? ''),
                AttachmentApplication::statusLabel((string) $a['status']),
                $date($a['selected_at'] ?? null),
                $date($a['accepted_at'] ?? null),
                $date($a['recommended_at'] ?? ($a['completed_at'] ?? null)),
                $f ? round($f['fee'], 2) : 0,
                $f ? round($f['paid'], 2) : 0,
                $f ? round($f['balance'], 2) : 0,
                $f && $f['fee'] > 0 ? (int) floor($f['paid_ratio'] * 100) : 100,
            ];
        }
        return $rows;
    }

    /**
     * Sends the spreadsheet. The file name says what it holds, e.g.
     * attachments-completed-2026-09-01-to-2026-09-30.xlsx.
     */
    public static function download(array $applications, string $label, string $from = '', string $to = ''): void
    {
        $name = 'attachments-' . $label;
        if ($from !== '' || $to !== '') {
            $name .= $from === $to ? '-' . $from : '-' . ($from ?: 'start') . '-to-' . ($to ?: date('Y-m-d'));
        } else {
            $name .= '-' . date('Y-m-d');
        }
        SpreadsheetExport::download($name, 'Attachments', self::HEADERS, self::rows($applications));
    }

    /** Columns of the completed-attachments list used to process payments. */
    public const PAYMENT_HEADERS = [
        '#', 'Student', 'Reg. No.', 'Phone', 'Course / category', 'Organisation providing courses',
        'Organisation providing attachment', 'Branch', 'Status', 'Accepted', 'Completed', 'Days',
    ];

    /** Payment-list states: key => label. */
    public const PAYMENT_STATES = ['completed' => 'Completed', 'ongoing' => 'Ongoing', 'all' => 'Completed and ongoing'];

    /** Reads ?state= for the payment list (completed by default). */
    public static function paymentState(): string
    {
        $state = (string) ($_GET['state'] ?? 'completed');
        return isset(self::PAYMENT_STATES[$state]) ? $state : 'completed';
    }

    /**
     * Attachments for processing payments, by branch then student. Filters:
     * provider_id, branch_ids, state (completed — the default —, ongoing or
     * all), from / to (the date the attachment was completed or, while
     * ongoing, accepted).
     */
    public static function completed(array $filters): array
    {
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';
        $query = match ($filters['state'] ?? 'completed') {
            'ongoing' => ['status' => 'ongoing', 'accepted_from' => $from, 'accepted_to' => $to],
            'all' => ['status' => 'ongoing_or_completed', 'active_from' => $from, 'active_to' => $to],
            default => ['status' => AttachmentApplication::STATUS_COMPLETED, 'completed_from' => $from, 'completed_to' => $to],
        };
        if (!empty($filters['provider_id'])) {
            $query['provider_id'] = (int) $filters['provider_id'];
        }
        if (array_key_exists('branch_ids', $filters)) {
            $query['branch_ids'] = $filters['branch_ids'];
        }
        $rows = AttachmentApplication::report($query);
        usort($rows, static fn(array $a, array $b): int => [
            (string) ($a['provider_organisation_name'] ?? ''), (string) ($a['branch_title'] ?? ''), trim($a['first_name'] . ' ' . $a['last_name']),
        ] <=> [
            (string) ($b['provider_organisation_name'] ?? ''), (string) ($b['branch_title'] ?? ''), trim($b['first_name'] . ' ' . $b['last_name']),
        ]);
        return $rows;
    }

    /** One row per completed attachment, numbered. */
    public static function paymentRows(array $applications): array
    {
        $date = static fn(?string $at): string => $at ? date('Y-m-d', strtotime($at)) : '';
        $rows = [];
        foreach (array_values($applications) as $n => $a) {
            $done = $a['recommended_at'] ?? ($a['completed_at'] ?? null);
            $start = $a['accepted_at'] ?? null;
            $rows[] = [
                $n + 1,
                trim($a['first_name'] . ' ' . $a['last_name']),
                (string) ($a['registration_number'] ?? ''),
                (string) ($a['phone'] ?? ''),
                (string) (($a['course_title'] ?? '') ?: ($a['category_name'] ?? '')),
                (string) (($a['course_organisation_name'] ?? '') ?: ($a['category_organisation_name'] ?? '')),
                (string) ($a['provider_organisation_name'] ?: trim($a['provider_first_name'] . ' ' . $a['provider_last_name'])),
                (string) (($a['branch_title'] ?? '') ?: 'No branch'),
                in_array($a['status'], [AttachmentApplication::STATUS_COMPLETED, AttachmentApplication::STATUS_RECOMMENDED], true) ? 'Completed' : 'Ongoing',
                $date($start),
                $date($done),
                $start && $done ? max(0, (int) round((strtotime($done) - strtotime($start)) / 86400)) : '',
            ];
        }
        return $rows;
    }

    /** Completed students per branch: [branch title => count]. */
    public static function perBranch(array $applications): array
    {
        $out = [];
        foreach ($applications as $a) {
            $key = (string) (($a['branch_title'] ?? '') ?: 'No branch');
            $out[$key] = ($out[$key] ?? 0) + 1;
        }
        ksort($out);
        return $out;
    }

    /** The payment list as Excel, or as a PDF opened in the browser to view and print. */
    public static function downloadPayments(array $applications, string $title, string $format, string $from = '', string $to = '', string $state = 'completed'): void
    {
        $stateLabel = self::PAYMENT_STATES[$state] ?? 'Completed';
        $range = $from !== '' || $to !== '' ? ($from ?: 'start') . ' to ' . ($to ?: date('Y-m-d')) : 'all dates';
        $name = 'attachment-payments-' . $state . '-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($title)) . '-' . ($from !== '' || $to !== '' ? ($from ?: 'start') . '-to-' . ($to ?: date('Y-m-d')) : date('Y-m-d'));
        $rows = self::paymentRows($applications);
        if ($format === 'pdf') {
            $summary = [];
            foreach (self::perBranch($applications) as $branch => $count) {
                $summary[] = $branch . ': ' . $count;
            }
            PdfTable::download(
                $name,
                $stateLabel . ' attachments — ' . $title,
                appName() . ' · ' . strtolower($stateLabel) . ' ' . $range . ' · ' . count($rows) . ' student' . (count($rows) === 1 ? '' : 's') . ($summary ? ' (' . implode(', ', $summary) . ')' : '') . ' · generated ' . date('j M Y H:i'),
                self::PAYMENT_HEADERS,
                $rows,
                ['', 'Total', '', '', '', '', '', '', '', '', '', count($rows) . ' students'],
                true
            );
        }
        SpreadsheetExport::download($name, $stateLabel . ' attachments', self::PAYMENT_HEADERS, $rows);
    }

    /** Reads ?from=&to= (Y-m-d) from the query string; anything else is ignored. */
    public static function dateRange(): array
    {
        $valid = static function ($v): string {
            $v = trim((string) $v);
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : '';
        };
        $from = $valid($_GET['from'] ?? '');
        $to = $valid($_GET['to'] ?? '');
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }
        return [$from, $to];
    }
}
