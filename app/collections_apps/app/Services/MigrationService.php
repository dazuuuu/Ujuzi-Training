<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class MigrationService
{
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

    public static function runPending(): int
    {
        $pdo = Database::connection();
        $applied = self::applied();

        $ran = 0;
        foreach (self::migrationFiles() as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $applied, true)) {
                continue;
            }
            $migration = require $file;
            $pdo->exec($migration['up']);
            $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)')->execute([$name]);
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

    private static function migrationFiles(): array
    {
        $files = glob(dirname(__DIR__, 2) . '/database/migrations/*.php') ?: [];
        sort($files);
        return $files;
    }
}
