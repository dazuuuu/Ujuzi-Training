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

    /** Reads a multi-file input such as name="materials[]". */
    public static function fileList(string $key): array
    {
        if (!isset($_FILES[$key]['name'])) {
            return [];
        }
        $names = $_FILES[$key]['name'];
        if (!is_array($names)) {
            $one = self::file($key);
            if (!$one || ($one['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }
            return [$one];
        }
        $out = [];
        foreach ($names as $index => $name) {
            if (($_FILES[$key]['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => (string) $name,
                'type' => $_FILES[$key]['type'][$index] ?? '',
                'tmp_name' => $_FILES[$key]['tmp_name'][$index] ?? '',
                'error' => $_FILES[$key]['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES[$key]['size'][$index] ?? 0,
            ];
        }
        return $out;
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

    /**
     * Reads a nested multi-file input such as name="answers[materials][]".
     */
    public static function nestedFileList(string $group, string $key): array
    {
        if (!isset($_FILES[$group]['name'][$key])) {
            return [];
        }
        $names = $_FILES[$group]['name'][$key];
        if (!is_array($names)) {
            $one = self::nestedFile($group, $key);
            return $one ? [$one] : [];
        }
        $out = [];
        foreach ($names as $index => $name) {
            if (($_FILES[$group]['error'][$key][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => (string) $name,
                'type' => $_FILES[$group]['type'][$key][$index] ?? '',
                'tmp_name' => $_FILES[$group]['tmp_name'][$key][$index] ?? '',
                'error' => $_FILES[$group]['error'][$key][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES[$group]['size'][$key][$index] ?? 0,
            ];
        }
        return $out;
    }

    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
