<?php

namespace App\Services;

/**
 * Streams a table as a download: a real Excel workbook (.xlsx) with a bold,
 * frozen header row when PHP's zip extension is available, otherwise a CSV
 * that Excel also opens.
 */
class SpreadsheetExport
{
    /**
     * @param string[] $headers
     * @param array<int, array<int, string|int|float|null>> $rows
     */
    public static function download(string $baseName, string $sheetTitle, array $headers, array $rows): void
    {
        $baseName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $baseName) ?: 'export';
        if (class_exists(\ZipArchive::class)) {
            $path = self::buildXlsx($sheetTitle, $headers, $rows);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $baseName . '.xlsx"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-store');
            readfile($path);
            @unlink($path);
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $baseName . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 names correctly
        fputcsv($out, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, array_map(static fn($v) => $v === null ? '' : $v, $row), ',', '"', '');
        }
        fclose($out);
        exit;
    }

    /** Writes the workbook to a temp file and returns its path. */
    public static function buildXlsx(string $sheetTitle, array $headers, array $rows): string
    {
        $sheetTitle = mb_substr(preg_replace('/[\[\]\*\?\/\\\\:]/', ' ', $sheetTitle) ?: 'Sheet1', 0, 31);
        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::xml($sheetTitle) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        // Style 0 = normal, 1 = bold header with a light fill.
        $zip->addFromString('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F3EC"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            . '</styleSheet>');

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>';
        foreach ($headers as $i => $header) {
            $width = max(12, min(45, mb_strlen((string) $header) + 4));
            foreach ($rows as $row) {
                $width = max($width, min(45, mb_strlen((string) ($row[$i] ?? '')) + 2));
            }
            $sheet .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
        }
        $sheet .= '</cols><sheetData>';
        $sheet .= self::row(1, $headers, 1);
        foreach (array_values($rows) as $r => $row) {
            $sheet .= self::row($r + 2, $row, 0);
        }
        $sheet .= '</sheetData>';
        if ($headers) {
            $sheet .= '<autoFilter ref="A1:' . self::column(count($headers)) . (count($rows) + 1) . '"/>';
        }
        $sheet .= '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        return $path;
    }

    private static function row(int $number, array $cells, int $style): string
    {
        $xml = '<row r="' . $number . '">';
        foreach (array_values($cells) as $i => $value) {
            $ref = self::column($i + 1) . $number;
            $styleAttr = $style ? ' s="' . $style . '"' : '';
            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $ref . '"' . $styleAttr . '><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '"' . $styleAttr . ' t="inlineStr"><is><t xml:space="preserve">' . self::xml((string) $value) . '</t></is></c>';
            }
        }
        return $xml . '</row>';
    }

    /** 1 => A, 27 => AA */
    private static function column(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + $n % 26) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    private static function xml(string $text): string
    {
        // Strip characters XML 1.0 forbids, then escape.
        $text = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text) ?? '';
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
