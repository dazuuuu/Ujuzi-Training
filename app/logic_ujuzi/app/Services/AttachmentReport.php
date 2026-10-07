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
