<?php

namespace App\Services;

/**
 * Writes a table as a landscape A4 PDF using the PDF standard fonts, so no
 * library is needed: a title, the table with its header repeated on every
 * page, a bold totals row, and page numbers. Every cell is ruled with
 * column and row lines. Long cells are shortened with
 * an ellipsis to fit their column.
 */
class PdfTable
{
    private const W = 842.0;
    private const H = 595.0;
    private const MARGIN = 28.0;
    private const FONT = 7.2;
    private const ROW = 13.0;

    /** Helvetica widths (1/1000 em) for printable ASCII. */
    private const WIDTHS = [
        ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, "'" => 191, '(' => 333, ')' => 333,
        '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278, ':' => 278, ';' => 278, '<' => 584, '=' => 584,
        '>' => 584, '?' => 556, '@' => 1015, 'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778,
        'H' => 722, 'I' => 278, 'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778, 'P' => 667, 'Q' => 778,
        'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944, 'X' => 667, 'Y' => 667, 'Z' => 611, '[' => 278,
        '\\' => 278, ']' => 278, '^' => 469, '_' => 556, '`' => 333, 'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556,
        'f' => 278, 'g' => 556, 'h' => 556, 'i' => 222, 'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556,
        'p' => 556, 'q' => 556, 'r' => 333, 's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722, 'x' => 500, 'y' => 500,
        'z' => 500, '{' => 334, '|' => 260, '}' => 334, '~' => 584,
    ];

    /** Sends the PDF; $inline opens it in the browser (to view or print) instead of saving it. */
    public static function download(string $baseName, string $title, string $subtitle, array $headers, array $rows, ?array $totals = null, bool $inline = false): void
    {
        $pdf = self::build($title, $subtitle, $headers, $rows, $totals);
        $baseName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $baseName) ?: 'report';
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $baseName . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: no-store');
        echo $pdf;
        exit;
    }

    public static function build(string $title, string $subtitle, array $headers, array $rows, ?array $totals = null): string
    {
        $headers = array_map([self::class, 'text'], $headers);
        $rows = array_map(static fn(array $r): array => array_map([self::class, 'text'], array_values($r)), $rows);
        $totals = $totals ? array_map([self::class, 'text'], array_values($totals)) : null;
        $widths = self::columnWidths($headers, $rows);

        $perPage = (int) floor((self::H - self::MARGIN * 2 - 58) / self::ROW);
        $chunks = $rows ? array_chunk($rows, max(1, $perPage)) : [[]];
        $pages = [];
        foreach ($chunks as $i => $chunk) {
            $last = $i === count($chunks) - 1;
            $pages[] = self::page($title, $subtitle, $headers, $chunk, $last ? $totals : null, $widths, $i + 1, count($chunks), $last && !$rows);
        }
        return self::document($pages);
    }

    private static function text($value): string
    {
        if (is_float($value)) {
            $value = number_format($value, 2);
        }
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '?', $value) : $converted;
    }

    private static function width(string $text, float $size, bool $bold = false): float
    {
        $units = 0;
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $units += self::WIDTHS[$text[$i]] ?? 556;
        }
        return $units * $size / 1000 * ($bold ? 1.07 : 1.0);
    }

    /** Columns share the page width by how much they hold (capped so one long column can't crowd out the rest). */
    private static function columnWidths(array $headers, array $rows): array
    {
        $want = [];
        foreach ($headers as $i => $header) {
            $longest = self::width($header, self::FONT, true);
            foreach (array_slice($rows, 0, 300) as $row) {
                $longest = max($longest, self::width((string) ($row[$i] ?? ''), self::FONT));
            }
            $want[$i] = min(max($longest + 8, 34), 190);
        }
        $available = self::W - self::MARGIN * 2;
        $scale = $available / array_sum($want);
        return array_map(static fn(float $w): float => $w * $scale, $want);
    }

    private static function fit(string $text, float $width, bool $bold = false): string
    {
        if (self::width($text, self::FONT, $bold) <= $width) {
            return $text;
        }
        while ($text !== '' && self::width($text . '...', self::FONT, $bold) > $width) {
            $text = substr($text, 0, -1);
        }
        return $text . '...';
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $text);
    }

    private static function page(string $title, string $subtitle, array $headers, array $rows, ?array $totals, array $widths, int $number, int $count, bool $empty): string
    {
        $out = [];
        $top = self::H - self::MARGIN;
        $out[] = 'BT /F2 15 Tf ' . self::MARGIN . ' ' . ($top - 12) . ' Td (' . self::escape(self::text($title)) . ') Tj ET';
        $out[] = '0.42 0.45 0.5 rg BT /F1 8 Tf ' . self::MARGIN . ' ' . ($top - 26) . ' Td (' . self::escape(self::text($subtitle)) . ') Tj ET 0 0 0 rg';

        $y = $top - 46;
        $drawRow = static function (array $cells, float $y, bool $bold, ?string $fill) use (&$out, $widths): void {
            $x = self::MARGIN;
            if ($fill) {
                $out[] = $fill . ' rg ' . self::MARGIN . ' ' . ($y - 4) . ' ' . (self::W - self::MARGIN * 2) . ' ' . self::ROW . ' re f 0 0 0 rg';
            }
            // Column and row lines around every cell.
            $grid = '0.55 0.58 0.6 RG 0.5 w';
            $gx = self::MARGIN;
            foreach ($widths as $w) {
                $grid .= ' ' . round($gx, 2) . ' ' . round($y - 4, 2) . ' ' . round($w, 2) . ' ' . self::ROW . ' re';
                $gx += $w;
            }
            $out[] = $grid . ' S 0 0 0 RG';
            foreach ($widths as $i => $w) {
                $cell = self::fit((string) ($cells[$i] ?? ''), $w - 6, $bold);
                $out[] = 'BT /' . ($bold ? 'F2' : 'F1') . ' ' . self::FONT . ' Tf ' . round($x + 3, 2) . ' ' . round($y, 2) . ' Td (' . self::escape($cell) . ') Tj ET';
                $x += $w;
            }
        };

        $drawRow($headers, $y, true, '0.86 0.93 0.89');
        $y -= self::ROW;
        foreach ($rows as $i => $row) {
            $drawRow($row, $y, false, $i % 2 ? '0.97 0.97 0.97' : null);
            $y -= self::ROW;
        }
        if ($empty) {
            $out[] = 'BT /F1 9 Tf ' . self::MARGIN . ' ' . $y . ' Td (No records for this report.) Tj ET';
            $y -= self::ROW;
        }
        if ($totals) {
            $drawRow($totals, $y, true, '0.93 0.93 0.93');
        }
        $footer = 'Page ' . $number . ' of ' . $count;
        $out[] = '0.42 0.45 0.5 rg BT /F1 7 Tf ' . round(self::W - self::MARGIN - self::width($footer, 7), 2) . ' ' . (self::MARGIN - 12) . ' Td (' . $footer . ') Tj ET';
        return implode("\n", $out);
    }

    private static function document(array $pages): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $kids = [];
        $next = 5;
        foreach ($pages as $content) {
            $pageId = $next++;
            $contentId = $next++;
            $kids[] = $pageId . ' 0 R';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }
}
