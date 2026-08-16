<?php

namespace App\Services;

class UploadException extends \Exception {}

/**
 * Image upload handling for the admin panel (product photos, per-color
 * photos, category cover images). Validates, renames, and stores under
 * public/assets/uploads/{subdir}/ — returns the relative URL to save in the DB.
 */
class UploadService
{
    private static function uploadsRoot(): string
    {
        return dirname(__DIR__, 4) . '/public/assets/uploads';
    }

    /** @param array $file One entry from $_FILES (e.g. $_FILES['image']) */
    public static function store(array $file, string $subdir): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new UploadException('Invalid upload.');
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new UploadException('No file was selected.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new UploadException('Upload failed (error code ' . $file['error'] . ').');
        }
        if ($file['size'] > 8 * 1024 * 1024) {
            throw new UploadException('Image is larger than 8MB.');
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new UploadException('File is not a valid image.');
        }

        $allowed = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];
        if (!isset($allowed[$info[2]])) {
            throw new UploadException('Only JPG, PNG, WEBP or GIF images are allowed.');
        }
        $ext = $allowed[$info[2]];

        $dir = self::uploadsRoot() . '/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = bin2hex(random_bytes(10)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            throw new UploadException('Could not save uploaded file.');
        }

        return 'assets/uploads/' . $subdir . '/' . $filename;
    }

    /** Deletes a previously uploaded file, never touches external/seed image URLs. */
    public static function delete(?string $relativePath): void
    {
        if (!$relativePath || strpos($relativePath, 'assets/uploads/') !== 0) {
            return;
        }
        $full = dirname(__DIR__, 4) . '/public/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
