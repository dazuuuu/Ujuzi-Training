<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class MigrationService
{
    public static function status(): array
    {
        $applied = self::applied();
        $rows = [];

        foreach (self::migrationFiles() as $file) {
            $name = basename($file, '.php');
            $rows[] = [
                'name' => $name,
                'applied' => in_array($name, $applied, true),
            ];
        }

        return $rows;
    }

    public static function pending(): array
    {
        $applied = self::applied();
        $pending = [];

        foreach (self::migrationFiles() as $file) {
            $name = basename($file, '.php');
            if (!in_array($name, $applied, true)) {
                $pending[] = $name;
            }
        }

        return $pending;
    }

    /** Names of the updates the last runPending() call applied. */
    public static array $ranNames = [];

    public static function runPending(): int
    {
        // Steps an earlier, interrupted run already did are skipped, not failed on.
        $pdo = \App\Core\RerunnablePdo::open();
        $applied = self::applied();

        $ran = 0;
        foreach (self::migrationFiles() as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $applied, true)) {
                continue;
            }
            $migration = require $file;
            if (is_callable($migration['up'] ?? null)) {
                $migration['up']($pdo);
            } else {
                $pdo->exec($migration['up']);
            }
            $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)')->execute([$name]);
            self::$ranNames[] = $name;
            $ran++;
        }

        return $ran;
    }

    private static function applied(): array
    {
        $pdo = Database::connection();
        self::ensureTable();
        return $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    }

    private static function ensureTable(): void
    {
        Database::connection()->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * What an update does, for Super Admin: ['title' => string, 'notes' =>
     * string[]] from the migration file's optional 'title' and 'notes' keys
     * (older updates have none, so their name is tidied into a title).
     */
    public static function describe(string $name): array
    {
        $file = dirname(__DIR__, 2) . '/database/migrations/' . basename($name) . '.php';
        $migration = is_file($file) ? require $file : [];
        $title = trim((string) ($migration['title'] ?? ''));
        if ($title === '') {
            $title = ucfirst(str_replace('_', ' ', preg_replace('/^\d+_/', '', $name)));
        }
        $notes = array_values(array_filter(array_map('strval', (array) ($migration['notes'] ?? []))));
        return ['title' => $title, 'notes' => $notes];
    }

    private static function migrationFiles(): array
    {
        $files = glob(dirname(__DIR__, 2) . '/database/migrations/*.php') ?: [];
        sort($files);
        return $files;
    }
}
