<?php

namespace App\Services;

/**
 * Keeps product image replacement rules in one place so controllers can treat
 * missing hidden image fields as "no existing image" instead of PHP warnings.
 */
class ProductImageService
{
    public static function existingPath(array $input): ?string
    {
        $path = trim((string) ($input['existing_image'] ?? ''));
        return $path !== '' ? $path : null;
    }

    public static function replaceColorImage(array $files, int $index, ?string $existingPath, string $subdir = 'products'): ?string
    {
        $name = $files['name'][$index]['image_file'] ?? '';
        if ($name === '') {
            return $existingPath;
        }

        $uploaded = UploadService::store([
            'name' => $name,
            'type' => $files['type'][$index]['image_file'] ?? '',
            'tmp_name' => $files['tmp_name'][$index]['image_file'] ?? '',
            'error' => $files['error'][$index]['image_file'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index]['image_file'] ?? 0,
        ], $subdir);

        if ($existingPath && $existingPath !== $uploaded) {
            UploadService::delete($existingPath);
        }

        return $uploaded;
    }
}
