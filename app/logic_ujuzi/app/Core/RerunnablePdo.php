<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * The connection updates run on. If an update stopped half way (a server
 * timeout, say), running it again must not fail on the steps already done:
 * "column / index / table already exists" and "already removed" are skipped.
 * Every other error still stops the update.
 */
class RerunnablePdo extends PDO
{
    /** MySQL error numbers meaning "this step was already done". */
    private const ALREADY_DONE = [
        1050, // table already exists
        1060, // duplicate column name
        1061, // duplicate key (index) name
        1091, // can't DROP: column / key doesn't exist
        1826, // duplicate foreign key constraint name
    ];

    public static function open(): self
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', Env::get('DB_HOST'), Env::get('DB_PORT'), Env::get('DB_NAME'));
        return new self($dsn, (string) Env::get('DB_USER'), (string) Env::get('DB_PASS', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function exec(string $statement): int|false
    {
        try {
            return parent::exec($statement);
        } catch (PDOException $e) {
            if (in_array((int) ($e->errorInfo[1] ?? 0), self::ALREADY_DONE, true)) {
                return 0;
            }
            throw $e;
        }
    }
}
