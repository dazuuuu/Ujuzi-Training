<?php

namespace App\Services;

use App\Core\Request;
use App\Models\FormFieldTypes;
use App\Models\Organisation;
use App\Models\OrganisationCategory;
use App\Services\UploadException;
use App\Services\UploadService;

class FormAnswerService
{
    public const OTHER_VALUE = '__other__';

    /**
     * @return array{answers: array, errors: array}
     */
    public static function collect(array $fields, array $posted, array $existing = [], ?array $user = null): array
    {
        $answers = $existing;
        $errors = [];

        foreach ($fields as $field) {
            $type = $field['field_type'];
            $key = $field['field_key'];
            if (FormFieldTypes::isLayout($type)) {
                continue;
            }

            if ($type === 'files') {
                $uploads = Request::nestedFileList('answers', $key);
                $kept = is_array($existing[$key] ?? null) ? $existing[$key] : [];
                if ($kept && !is_array($kept)) {
                    $kept = [$kept];
                }
                foreach ($uploads as $file) {
                    try {
                        $kept[] = UploadService::storeDocument($file, 'forms');
                    } catch (UploadException $e) {
                        $errors[] = $field['label'] . ': ' . $e->getMessage();
                    }
                }
                if ($field['is_required'] && !$kept) {
                    $errors[] = $field['label'] . ' is required.';
                }
                $answers[$key] = array_values($kept);
                continue;
            }

            if (FormFieldTypes::isFile($type)) {
                $file = Request::nestedFile('answers', $key);
                if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $answers[$key] = $type === 'image'
                            ? UploadService::store($file, 'forms')
                            : UploadService::storeDocument($file, 'forms');
                    } catch (UploadException $e) {
                        $errors[] = $field['label'] . ': ' . $e->getMessage();
                    }
                } elseif ($field['is_required'] && empty($existing[$key])) {
                    $errors[] = $field['label'] . ' is required.';
                }
                continue;
            }

            if ($type === 'name') {
                $raw = is_array($posted[$key] ?? null) ? $posted[$key] : [];
                $value = [
                    'first' => trim((string) ($raw['first'] ?? '')),
                    'last' => trim((string) ($raw['last'] ?? '')),
                ];
                if ($field['is_required'] && $value['first'] === '') {
                    $errors[] = $field['label'] . ' needs a first name.';
                }
                $answers[$key] = $value;
                continue;
            }

            if ($type === 'address' || $type === 'county') {
                $raw = $posted[$key] ?? '';
                $value = is_array($raw)
                    ? trim((string) ($raw['county'] ?? ''))
                    : trim((string) $raw);
                if ($field['is_required'] && $value === '') {
                    $errors[] = $field['label'] . ' is required.';
                }
                if ($value !== '') {
                    $typeError = self::typeError($field, $value);
                    if ($typeError) {
                        $errors[] = $typeError;
                    }
                }
                $answers[$key] = $value;
                continue;
            }

            if ($type === 'organisation') {
                $raw = $posted[$key] ?? [];
                if (!is_array($raw)) {
                    $raw = $raw !== '' && $raw !== null ? [$raw] : [];
                }
                $allowed = Organisation::activeIds();
                $ids = [];
                foreach ($raw as $item) {
                    $id = (int) $item;
                    if ($id > 0 && in_array($id, $allowed, true) && !in_array($id, $ids, true)) {
                        $ids[] = $id;
                    }
                }
                $multiple = ($field['org_mode'] ?? 'single') === 'multiple';
                if (!$multiple) {
                    $ids = array_slice($ids, 0, 1);
                }
                if ($field['is_required'] && !$ids) {
                    $errors[] = $field['label'] . ' is required.';
                }
                $answers[$key] = $multiple ? $ids : ($ids[0] ?? '');
                continue;
            }

            if ($type === 'category') {
                $raw = $posted[$key] ?? [];
                if (!is_array($raw)) {
                    $raw = $raw !== '' && $raw !== null ? [$raw] : [];
                }
                $allowed = [];
                foreach (OrganisationCategory::groupedForUser($user) as $group) {
                    foreach ($group as $category) {
                        $allowed[] = (int) $category['id'];
                    }
                }
                $ids = [];
                foreach ($raw as $item) {
                    $id = (int) $item;
                    if ($id > 0 && in_array($id, $allowed, true) && !in_array($id, $ids, true)) {
                        $ids[] = $id;
                    }
                }
                $ids = array_slice($ids, 0, 1);
                if ($field['is_required'] && !$ids) {
                    $errors[] = $field['label'] . ' is required.';
                }
                $answers[$key] = $ids[0] ?? '';
                continue;
            }

            if ($type === 'list') {
                $raw = $posted[$key] ?? [];
                if (!is_array($raw)) {
                    $raw = $raw !== '' && $raw !== null ? [$raw] : [];
                }
                $value = array_values(array_filter(array_map('trim', $raw), fn($item) => $item !== ''));
                if ($field['is_required'] && !$value) {
                    $errors[] = $field['label'] . ' is required.';
                }
                $answers[$key] = $value;
                continue;
            }

            if (FormFieldTypes::isMulti($type)) {
                $raw = $posted[$key] ?? [];
                if (!is_array($raw)) {
                    $raw = $raw !== '' && $raw !== null ? [$raw] : [];
                }
                $value = self::resolveOtherList($raw, $posted, $key, !empty($field['allow_other']));
                $allowed = $field['options'] ?: [];
                foreach ($value as $item) {
                    if ($allowed && !in_array($item, $allowed, true) && empty($field['allow_other'])) {
                        $errors[] = $field['label'] . ' has an invalid option.';
                        break;
                    }
                }
                $count = count($value);
                $minSelect = max(0, (int) ($field['min_select'] ?? 0));
                $maxSelect = max(0, (int) ($field['max_select'] ?? 0));
                if ($field['is_required'] && $minSelect < 1) {
                    $minSelect = 1;
                }
                if ($minSelect > 0 && $count < $minSelect) {
                    $errors[] = $field['label'] . ' needs at least ' . $minSelect . ' selection' . ($minSelect === 1 ? '' : 's') . '.';
                }
                if ($maxSelect > 0 && $count > $maxSelect) {
                    $errors[] = $field['label'] . ' allows at most ' . $maxSelect . ' selection' . ($maxSelect === 1 ? '' : 's') . '.';
                }
                if (in_array(self::OTHER_VALUE, $raw, true) && empty(trim((string) ($posted[$key . '__other'] ?? '')))) {
                    $errors[] = $field['label'] . ' needs the “Other” value filled in.';
                }
                $answers[$key] = $value;
                continue;
            }

            if (in_array($type, ['checkbox', 'consent', 'toggle'], true)) {
                $value = isset($posted[$key]) ? trim((string) $posted[$key]) : '';
                if ($type === 'toggle' && $value === '') {
                    $value = 'No';
                }
                if ($field['is_required'] && ($value === '' || $value === 'No')) {
                    $errors[] = $field['label'] . ' must be confirmed.';
                }
                $answers[$key] = $value;
                continue;
            }

            $value = isset($posted[$key]) ? trim((string) $posted[$key]) : '';
            if ($value === self::OTHER_VALUE) {
                $value = trim((string) ($posted[$key . '__other'] ?? ''));
                if ($value === '') {
                    $errors[] = $field['label'] . ' needs the “Other” value filled in.';
                }
            }

            if ($field['is_required'] && $value === '') {
                $errors[] = $field['label'] . ' is required.';
            }

            if ($value !== '') {
                $typeError = self::typeError($field, $value);
                if ($typeError) {
                    $errors[] = $typeError;
                }
            }

            $answers[$key] = $value;
        }

        return ['answers' => $answers, 'errors' => $errors];
    }

    private static function resolveOtherList(array $raw, array $posted, string $key, bool $allowOther): array
    {
        $value = [];
        foreach ($raw as $item) {
            $item = trim((string) $item);
            if ($item === '' || $item === self::OTHER_VALUE) {
                continue;
            }
            $value[] = $item;
        }
        if ($allowOther && in_array(self::OTHER_VALUE, array_map('strval', $raw), true)) {
            $other = trim((string) ($posted[$key . '__other'] ?? ''));
            if ($other !== '') {
                $value[] = $other;
            }
        }
        return array_values(array_unique($value));
    }

    private static function typeError(array $field, string $value): ?string
    {
        $type = $field['field_type'];
        $label = $field['label'];
        $options = $field['options'] ?: [];
        $min = $field['range_min'] ?? '';
        $max = $field['range_max'] ?? '';

        return match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : $label . ' must be a valid email address.',
            'url' => filter_var($value, FILTER_VALIDATE_URL) ? null : $label . ' must be a valid URL.',
            'number', 'range', 'rating' => self::numberBoundsError($label, $value, $min, $max),
            'dropdown', 'radio', 'yesno' => self::choiceError($label, $value, $options, !empty($field['allow_other'])),
            'country' => in_array($value, $options ?: FormFieldTypes::countries(), true) ? null : $label . ' has an invalid country.',
            'county', 'address' => in_array($value, FormFieldTypes::counties(), true) ? null : $label . ' has an invalid county.',
            default => null,
        };
    }

    private static function numberBoundsError(string $label, string $value, $min, $max): ?string
    {
        if (!is_numeric($value)) {
            return $label . ' must be a number.';
        }
        $number = (float) $value;
        if ($min !== '' && is_numeric($min) && $number < (float) $min) {
            return $label . ' must be at least ' . $min . '.';
        }
        if ($max !== '' && is_numeric($max) && $number > (float) $max) {
            return $label . ' must be at most ' . $max . '.';
        }
        return null;
    }

    private static function choiceError(string $label, string $value, array $options, bool $allowOther): ?string
    {
        if (!$options || in_array($value, $options, true) || $allowOther) {
            return null;
        }
        return $label . ' has an invalid option.';
    }
}
