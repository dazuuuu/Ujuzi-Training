<?php

namespace App\Services;

use App\Core\Database;
use App\Core\SiteLock;
use PDO;

/**
 * Full-site backup: a single .sql file with every table's structure and data —
 * users, organisations, courses, enrolments, payments, reports, settings,
 * everything — so the whole website can be moved or restored elsewhere.
 *
 * The private developer account is left out of the backup, and a restore never
 * touches the developer account on the site it is restored into: those rows are
 * saved before the import and written back after. So the client's data comes
 * across intact (they sign in with their previous credentials), while the gate
 * stays whatever it is on the destination.
 */
class BackupService
{
    private const SEP = ";\n";

    /** Streams the backup to the browser as a download and stops. */
    public static function download(): void
    {
        $sql = self::export();
        $name = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) appName())) . '-backup-' . date('Y-m-d-His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }

    /** Builds the whole dump as one string. */
    public static function export(): string
    {
        $pdo = Database::connection();
        $reserved = array_flip(SiteLock::reservedKeys());
        $out = "-- " . appName() . " full backup\n-- generated " . date('c') . "\n";
        $out .= "SET NAMES utf8mb4" . self::SEP;
        $out .= "SET FOREIGN_KEY_CHECKS=0" . self::SEP;

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $table = (string) $table;
            $out .= "DROP TABLE IF EXISTS `" . $table . "`" . self::SEP;
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_ASSOC);
            $out .= trim((string) ($create['Create Table'] ?? $create['Create View'] ?? '')) . self::SEP;

            $rows = $pdo->query('SELECT * FROM `' . $table . '`');
            foreach ($rows as $row) {
                // Keep the developer gate's own rows out of the backup entirely.
                if ($table === 'store_settings' && isset($reserved[$row['setting_key'] ?? ''])) {
                    continue;
                }
                $cols = array_map(static fn(string $c): string => '`' . $c . '`', array_keys($row));
                $vals = array_map(static function ($v) use ($pdo): string {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, array_values($row));
                $out .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')' . self::SEP;
            }
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1" . self::SEP;
        return $out;
    }

    /**
     * Restores a backup produced by export() (or a comparable .sql dump).
     * Returns the number of statements run. Throws on the first failing
     * statement. The developer account on this site is preserved regardless
     * of what the backup contains.
     */
    public static function import(string $sql): int
    {
        $pdo = Database::connection();

        // Remember this site's developer-gate rows so the restore can't disturb them.
        $keptGate = [];
        try {
            $in = implode(',', array_fill(0, count(SiteLock::reservedKeys()), '?'));
            $stmt = $pdo->prepare('SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN (' . $in . ')');
            $stmt->execute(SiteLock::reservedKeys());
            $keptGate = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (\Throwable $e) {
            $keptGate = [];
        }

        $statements = self::split($sql);
        if (!$statements) {
            throw new \RuntimeException('The file had no SQL statements to run. Make sure it is a backup produced by this system.');
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $ran = 0;
        try {
            foreach ($statements as $i => $statement) {
                try {
                    $pdo->exec($statement);
                    $ran++;
                } catch (\PDOException $e) {
                    throw new \RuntimeException('Restore stopped at statement ' . ($i + 1) . ': ' . $e->getMessage());
                }
            }
        } finally {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Write this site's developer-gate rows back on top of whatever came in.
        try {
            $restore = $pdo->prepare(
                'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
            foreach ($keptGate as $key => $value) {
                $restore->execute([$key, $value]);
            }
        } catch (\Throwable $e) {
            // The gate simply keeps whatever the import left; nothing fatal.
        }

        return $ran;
    }

    /**
     * Adds back rows from an older backup without disturbing the live
     * database: never drops or recreates a table, never touches
     * store_settings (so live mail/payment credentials and the site lock
     * are untouched), and for every other table only inserts a row if its
     * primary/unique key doesn't already exist — an existing row (old or
     * brand new) is never overwritten or removed. One bad statement (a
     * table or column the live schema no longer has, say) is skipped and
     * counted, not fatal to the rest of the file.
     *
     * Returns ['tables' => int, 'inserted' => int, 'skipped' => int, 'failed' => int, 'errors' => string[]].
     */
    public static function mergeImport(string $sql): array
    {
        $pdo = Database::connection();
        $statements = self::split($sql);
        if (!$statements) {
            throw new \RuntimeException('The file had no SQL statements to run. Make sure it is a backup produced by this system.');
        }

        $tables = [];
        $inserted = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($statements as $statement) {
                if (!preg_match('/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $statement, $m)) {
                    continue; // CREATE/DROP TABLE, SET, etc. — the live schema is left exactly as it is
                }
                $table = $m[1];
                if ($table === 'store_settings') {
                    continue; // live settings (mail, payments, the site lock) are never touched by a merge
                }
                $tables[$table] = true;

                // INSERT -> INSERT IGNORE: a row whose primary/unique key already
                // exists is silently left alone instead of erroring or overwriting.
                $merged = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $statement, 1);
                try {
                    $rows = $pdo->exec($merged);
                    $attempted = max(1, substr_count($statement, '),(') + 1); // multi-row INSERT support
                    $inserted += (int) $rows;
                    $skipped += max(0, $attempted - (int) $rows);
                } catch (\PDOException $e) {
                    $failed++;
                    if (count($errors) < 20) {
                        $errors[] = $table . ': ' . $e->getMessage();
                    }
                }
            }
        } finally {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return [
            'tables' => count($tables),
            'inserted' => $inserted,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Pulls the one .sql file out of an uploaded .zip — the largest .sql
     * entry if there's more than one, since a real dump dwarfs anything
     * else in the archive. Returns null (not false) when there isn't one,
     * so callers can tell "no sql in zip" apart from "sql file was empty".
     */
    public static function sqlFromZip(string $path): ?string
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('This PHP has no "zip" extension, so it cannot open the archive. Upload the .sql file directly instead.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('That file is not a valid zip archive.');
        }
        $best = null;
        $bestSize = -1;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false || !preg_match('/\.sql$/i', $entryName)) {
                continue;
            }
            $size = (int) ($zip->statIndex($i)['size'] ?? 0);
            if ($size > $bestSize) {
                $best = $entryName;
                $bestSize = $size;
            }
        }
        if ($best === null) {
            $zip->close();
            return null;
        }
        $sql = $zip->getFromName($best);
        $zip->close();
        return $sql === false ? '' : $sql;
    }

    /**
     * Splits a dump into individual statements, respecting single-quoted
     * strings (with backslash and doubled-quote escaping) and skipping
     * -- line comments, so semicolons inside data don't split a statement.
     */
    private static function split(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $len = strlen($sql);
        $inString = false;
        $i = 0;
        while ($i < $len) {
            $c = $sql[$i];
            if ($inString) {
                $buffer .= $c;
                if ($c === '\\' && $i + 1 < $len) {
                    $buffer .= $sql[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($c === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $buffer .= "'";
                        $i += 2;
                        continue;
                    }
                    $inString = false;
                }
                $i++;
                continue;
            }
            // Line comment: -- to end of line (only outside strings).
            if ($c === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                $nl = strpos($sql, "\n", $i);
                if ($nl === false) {
                    break;
                }
                $i = $nl + 1;
                continue;
            }
            if ($c === "'") {
                $inString = true;
                $buffer .= $c;
                $i++;
                continue;
            }
            if ($c === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                $i++;
                continue;
            }
            $buffer .= $c;
            $i++;
        }
        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }
        return $statements;
    }
}
