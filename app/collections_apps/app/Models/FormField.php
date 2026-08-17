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
            'INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, help_text, is_required, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $usedKeys = [];
        foreach (array_values($fields) as $index => $field) {
            $label = trim((string) ($field['label'] ?? ''));
            $type = (string) ($field['field_type'] ?? 'text');
            if (!FormFieldTypes::isValid($type)) {
                $type = 'text';
            }
            if ($label === '' && !FormFieldTypes::isLayout($type)) {
                continue;
            }
            if ($label === '') {
                $label = FormFieldTypes::label($type);
            }

            $existingKey = trim((string) ($field['field_key'] ?? ''));
            $key = $existingKey !== '' && !in_array($existingKey, $usedKeys, true)
                ? $existingKey
                : self::uniqueKey($label, $usedKeys);
            $usedKeys[] = $key;

            $options = self::normalizeOptions($type, $field);
            $stmt->execute([
                $formId,
                $label,
                $key,
                $type,
                $options ? json_encode($options) : null,
                trim((string) ($field['placeholder'] ?? '')) ?: null,
                trim((string) ($field['help_text'] ?? '')) ?: null,
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
        $meta = self::unpackOptions(is_array($options) ? $options : [], (string) ($row['field_type'] ?? 'text'));
        $row['options'] = $meta['choices'];
        $row['choices'] = $meta['choices'];
        $row['allow_other'] = $meta['allow_other'];
        $row['columns'] = $meta['columns'];
        $row['min_select'] = $meta['min_select'];
        $row['max_select'] = $meta['max_select'];
        $row['select_all'] = $meta['select_all'];
        $row['range_min'] = $meta['range_min'];
        $row['range_max'] = $meta['range_max'];
        $row['org_mode'] = $meta['org_mode'];
        $row['is_required'] = (int) $row['is_required'] === 1;
        $row['placeholder'] = $row['placeholder'] ?? '';
        $row['help_text'] = $row['help_text'] ?? '';
        return $row;
    }

    public static function formatAnswer(array $field, $value): string
    {
        if (FormFieldTypes::isLayout($field['field_type'])) {
            return '';
        }
        if ($value === null || $value === '' || $value === []) {
            return '—';
        }
        if (($field['field_type'] ?? '') === 'organisation') {
            $ids = is_array($value) ? $value : [$value];
            $names = Organisation::namesByIds($ids);
            return $names ? implode(', ', array_values($names)) : '—';
        }
        if (($field['field_type'] ?? '') === 'category') {
            $ids = is_array($value) ? $value : [$value];
            $names = OrganisationCategory::namesByIds($ids);
            return $names ? implode(', ', array_values($names)) : '—';
        }
        if (is_array($value)) {
            if (isset($value['first']) || isset($value['last'])) {
                return trim(($value['first'] ?? '') . ' ' . ($value['last'] ?? '')) ?: '—';
            }
            if (isset($value['county']) || isset($value['street']) || isset($value['city'])) {
                return trim((string) ($value['county'] ?? '')) ?: '—';
            }
            return implode(', ', array_map('strval', $value));
        }
        if (FormFieldTypes::isFile($field['field_type'])) {
            return (string) $value;
        }
        if (in_array($field['field_type'], ['checkbox', 'consent', 'toggle'], true)) {
            if ($value === '' || $value === null || $value === 'No' || $value === '0' || $value === false) {
                return 'No';
            }
            return 'Yes';
        }
        if ($field['field_type'] === 'rating') {
            $max = $field['range_max'] !== '' ? $field['range_max'] : '5';
            return $value . ' / ' . $max;
        }
        return (string) $value;
    }

    private static function unpackOptions(array $raw, string $type): array
    {
        $meta = [
            'choices' => [],
            'allow_other' => false,
            'columns' => 1,
            'min_select' => 0,
            'max_select' => 0,
            'select_all' => false,
            'range_min' => '',
            'range_max' => '',
            'org_mode' => 'single',
        ];

        if ($raw === []) {
            return $meta;
        }

        $structured = array_key_exists('choices', $raw)
            || array_key_exists('allow_other', $raw)
            || array_key_exists('min', $raw)
            || array_key_exists('max', $raw)
            || array_key_exists('columns', $raw)
            || array_key_exists('org_mode', $raw);

        if ($structured) {
            $meta['choices'] = self::stringList($raw['choices'] ?? []);
            $meta['allow_other'] = !empty($raw['allow_other']);
            $meta['columns'] = max(1, min(3, (int) ($raw['columns'] ?? 1)));
            $meta['min_select'] = max(0, (int) ($raw['min_select'] ?? 0));
            $meta['max_select'] = max(0, (int) ($raw['max_select'] ?? 0));
            $meta['select_all'] = !empty($raw['select_all']);
            $meta['range_min'] = trim((string) ($raw['min'] ?? ''));
            $meta['range_max'] = trim((string) ($raw['max'] ?? ''));
            $meta['org_mode'] = (($raw['org_mode'] ?? '') === 'multiple') ? 'multiple' : 'single';
            return $meta;
        }

        $list = self::stringList($raw);
        if (FormFieldTypes::needsRange($type)) {
            $meta['range_min'] = $list[0] ?? '';
            $meta['range_max'] = $list[1] ?? '';
            return $meta;
        }
        $meta['choices'] = $list;
        return $meta;
    }

    private static function normalizeOptions(string $type, array $field): array
    {
        if (FormFieldTypes::needsRange($type)) {
            $min = trim((string) ($field['range_min'] ?? ($field['options']['min'] ?? '')));
            $max = trim((string) ($field['range_max'] ?? ($field['options']['max'] ?? '')));
            if ($type === 'range') {
                return ['min' => $min !== '' ? $min : '0', 'max' => $max !== '' ? $max : '100'];
            }
            if ($type === 'rating') {
                return ['min' => $min !== '' ? $min : '1', 'max' => $max !== '' ? $max : '5'];
            }
            $out = [];
            if ($min !== '') {
                $out['min'] = $min;
            }
            if ($max !== '') {
                $out['max'] = $max;
            }
            return $out;
        }
        if ($type === 'organisation') {
            return [
                'org_mode' => (($field['org_mode'] ?? '') === 'multiple') ? 'multiple' : 'single',
            ];
        }
        if ($type === 'yesno' || $type === 'toggle') {
            return ['choices' => ['Yes', 'No']];
        }
        if ($type === 'checkbox' || $type === 'consent') {
            $choices = self::stringList($field['choices'] ?? $field['options'] ?? ['Yes']);
            return ['choices' => $choices ?: ['Yes']];
        }
        if (!FormFieldTypes::needsChoices($type) && $type !== 'country') {
            return [];
        }

        $payload = [
            'choices' => self::stringList($field['choices'] ?? $field['options'] ?? []),
        ];
        if (!empty($field['allow_other']) && FormFieldTypes::allowsOther($type)) {
            $payload['allow_other'] = true;
        }
        $columns = max(1, min(3, (int) ($field['columns'] ?? 1)));
        if ($columns > 1 && FormFieldTypes::hasColumns($type)) {
            $payload['columns'] = $columns;
        }
        $minSelect = max(0, (int) ($field['min_select'] ?? 0));
        $maxSelect = max(0, (int) ($field['max_select'] ?? 0));
        if ($minSelect > 0 && FormFieldTypes::hasMinMaxSelect($type)) {
            $payload['min_select'] = $minSelect;
        }
        if ($maxSelect > 0 && FormFieldTypes::hasMinMaxSelect($type)) {
            $payload['max_select'] = $maxSelect;
        }
        if (!empty($field['select_all']) && $type === 'checkboxes') {
            $payload['select_all'] = true;
        }
        return $payload;
    }

    private static function stringList($raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }
        return array_values(array_filter(array_map(fn($item) => trim((string) $item), $raw), fn($item) => $item !== ''));
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
