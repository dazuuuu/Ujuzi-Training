<?php

namespace App\Models;

use App\Core\Database;

class FormField
{
    public static function forForm(int $formId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$formId]);
        return array_map([self::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function countForForm(int $formId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM form_fields WHERE form_id = ?');
        $stmt->execute([$formId]);
        return (int) $stmt->fetchColumn();
    }

    public static function replaceForForm(int $formId, array $fields): void
    {
        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM form_fields WHERE form_id = ?')->execute([$formId]);
        $stmt = $pdo->prepare(
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $usedKeys = [];
        foreach (array_values($fields) as $index => $field) {
            $label = trim((string) ($field['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $type = (string) ($field['field_type'] ?? 'text');
            if (!in_array($type, ['text', 'paragraph', 'dropdown', 'number', 'date', 'datetime'], true)) {
                $type = 'text';
            }
            $key = self::uniqueKey($label, $usedKeys);
            $usedKeys[] = $key;
            $options = null;
            if ($type === 'dropdown') {
                $raw = $field['options'] ?? [];
                if (is_string($raw)) {
                    $raw = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw))));
                }
                $options = json_encode(array_values($raw));
            }
            $stmt->execute([
                $formId,
                $label,
                $key,
                $type,
                $options,
                !empty($field['is_required']) ? 1 : 0,
                $index,
            ]);
        }
    }

    public static function hydrate(array $row): array
    {
        $options = $row['options'] ?? null;
        if (is_string($options) && $options !== '') {
            $options = json_decode($options, true) ?: [];
        }
        $row['options'] = is_array($options) ? $options : [];
        $row['is_required'] = (int) $row['is_required'] === 1;
        return $row;
    }

    private static function uniqueKey(string $label, array $used): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $label), '_')) ?: 'field';
        $key = $base;
        $i = 2;
        while (in_array($key, $used, true)) {
            $key = $base . '_' . $i;
            $i++;
        }
        return $key;
    }
}
