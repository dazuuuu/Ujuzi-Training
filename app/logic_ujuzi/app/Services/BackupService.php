<?php

namespace App\Services;

use App\Core\Database;
use App\Core\DevGate;
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
        $reserved = array_flip(DevGate::reservedKeys());
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
            $in = implode(',', array_fill(0, count(DevGate::reservedKeys()), '?'));
            $stmt = $pdo->prepare('SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN (' . $in . ')');
            $stmt->execute(DevGate::reservedKeys());
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
