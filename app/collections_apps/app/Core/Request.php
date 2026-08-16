<?php

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Reads a nested file input such as name="answers[national_id]".
     */
    public static function nestedFile(string $group, string $key): ?array
    {
        if (!isset($_FILES[$group]['name'][$key])) {
            return null;
        }
        if (($_FILES[$group]['error'][$key] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return [
            'name' => $_FILES[$group]['name'][$key] ?? '',
            'type' => $_FILES[$group]['type'][$key] ?? '',
            'tmp_name' => $_FILES[$group]['tmp_name'][$key] ?? '',
            'error' => $_FILES[$group]['error'][$key] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES[$group]['size'][$key] ?? 0,
        ];
    }

    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
