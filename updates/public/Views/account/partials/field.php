<?php
/** Renders one assigned profile field. Requires $field and $value. */
use App\Models\FormFieldTypes;
use App\Services\FormAnswerService;

$type = $field['field_type'] ?? 'text';
$key = $field['field_key'];
$name = 'answers[' . $key . ']';
$required = !empty($field['is_required']);
$placeholder = $field['placeholder'] ?? '';
$help = $field['help_text'] ?? '';
$options = $field['options'] ?: [];
$allowOther = !empty($field['allow_other']);
$columns = max(1, min(3, (int) ($field['columns'] ?? 1)));
$minSelect = max(0, (int) ($field['min_select'] ?? 0));
$maxSelect = max(0, (int) ($field['max_select'] ?? 0));
$selectAll = !empty($field['select_all']);
$class = 'w-full mt-1 bg-white border border-neutral-300 rounded-lg p-2.5 text-sm';
$otherToken = FormAnswerService::OTHER_VALUE;

if ($type === 'heading') {
    echo '<h3 class="font-serif-heading text-lg font-bold pt-2">' . e($field['label']) . '</h3>';
    if ($help) {
        echo '<p class="text-sm font-medium" style="color:var(--ke-muted)">' . e($help) . '</p>';
    }
    return;
}

if ($type === 'instructions') {
    echo '<div class="rounded-lg p-3 text-sm font-medium" style="background:#e8f5ee;color:var(--ke-green-dark)">' . e($help ?: $field['label']) . '</div>';
    return;
}

if ($type === 'hidden') {
    echo '<input type="hidden" name="' . e($name) . '" value="' . e((string) ($value !== '' && $value !== null ? $value : $placeholder)) . '" />';
    return;
}

echo '<label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">' . e($field['label']) . ($required ? ' *' : '') . '</label>';
if ($help && !in_array($type, ['checkbox', 'consent'], true)) {
    echo '<p class="field-hint">' . e($help) . '</p>';
}

$isOtherSelected = static function ($current, array $options) use ($otherToken): bool {
    if (is_array($current)) {
        foreach ($current as $item) {
            if ((string) $item !== '' && !in_array((string) $item, array_map('strval', $options), true)) {
                return true;
            }
        }
        return in_array($otherToken, array_map('strval', $current), true);
    }
    $current = (string) $current;
    return $current !== '' && !in_array($current, array_map('strval', $options), true);
};

$otherText = static function ($current, array $options): string {
    if (is_array($current)) {
        foreach ($current as $item) {
            if ((string) $item !== '' && !in_array((string) $item, array_map('strval', $options), true)) {
                return (string) $item;
            }
        }
        return '';
    }
    $current = (string) $current;
    return ($current !== '' && !in_array($current, array_map('strval', $options), true)) ? $current : '';
};

switch ($type) {
    case 'paragraph':
        echo '<textarea name="' . e($name) . '" rows="4" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder) . '" class="' . $class . '">' . e((string) $value) . '</textarea>';
        break;

    case 'name':
        $first = is_array($value) ? (string) ($value['first'] ?? '') : '';
        $last = is_array($value) ? (string) ($value['last'] ?? '') : '';
        echo '<div class="name-grid">';
        echo '<div><label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">First name</label><input type="text" name="' . e($name) . '[first]" value="' . e($first) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '" /></div>';
        echo '<div><label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Last name</label><input type="text" name="' . e($name) . '[last]" value="' . e($last) . '" class="' . $class . '" /></div>';
        echo '</div>';
        break;

    case 'address':
    case 'dropdown':
    case 'country':
    case 'county':
        $opts = $options;
        if ($type === 'country') {
            $opts = $options ?: FormFieldTypes::countries();
        } elseif ($type === 'county' || $type === 'address') {
            $opts = FormFieldTypes::counties();
        }
        $current = is_array($value) ? (string) ($value['county'] ?? '') : (string) $value;
        $usingOther = $allowOther && $type === 'dropdown' && $isOtherSelected($current, $opts);
        echo '<div class="js-choice-field">';
        echo '<select name="' . e($name) . '" ' . ($required ? 'required' : '') . ' class="' . $class . ' js-has-other"><option value="">' . ($type === 'county' || $type === 'address' ? 'Choose county' : 'Choose') . '</option>';
        foreach ($opts as $option) {
            $selected = !$usingOther && $current === (string) $option ? 'selected' : '';
            echo '<option value="' . e($option) . '" ' . $selected . '>' . e($option) . '</option>';
        }
        if ($allowOther && $type === 'dropdown') {
            echo '<option value="' . e($otherToken) . '" ' . ($usingOther ? 'selected' : '') . '>Other</option>';
        }
        echo '</select>';
        if ($allowOther && $type === 'dropdown') {
            echo '<input type="text" name="' . e('answers[' . $key . '__other]') . '" value="' . e($usingOther ? $otherText($current, $opts) : '') . '" class="' . $class . ' js-other-input' . ($usingOther ? '' : ' hidden') . '" placeholder="Type the other value" />';
        }
        echo '</div>';
        break;

    case 'multiselect':
        $selected = is_array($value) ? $value : (array) array_filter([(string) $value]);
        echo '<select name="' . e($name) . '[]" multiple size="' . max(3, min(8, count($options))) . '" class="' . $class . '">';
        foreach ($options as $option) {
            $isOn = in_array((string) $option, array_map('strval', $selected), true) ? 'selected' : '';
            echo '<option value="' . e($option) . '" ' . $isOn . '>' . e($option) . '</option>';
        }
        echo '</select>';
        echo '<p class="field-hint">Hold Ctrl or Cmd to select more than one.' . ($minSelect ? ' Pick at least ' . (int) $minSelect . '.' : '') . ($maxSelect ? ' At most ' . (int) $maxSelect . '.' : '') . '</p>';
        break;

    case 'radio':
    case 'yesno':
        $opts = $type === 'yesno' ? ['Yes', 'No'] : $options;
        $current = (string) $value;
        $usingOther = $allowOther && $type === 'radio' && $isOtherSelected($current, $opts);
        echo '<div class="js-choice-field">';
        echo '<div class="choice-grid cols-' . (int) $columns . '">';
        foreach ($opts as $option) {
            $id = $key . '-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) $option));
            $checked = !$usingOther && $current === (string) $option ? 'checked' : '';
            echo '<label class="flex items-center gap-2 text-sm font-semibold"><input type="radio" id="' . e($id) . '" name="' . e($name) . '" value="' . e($option) . '" ' . $checked . ' ' . ($required ? 'required' : '') . ' class="h-4 w-4 js-has-other" />' . e($option) . '</label>';
        }
        if ($allowOther && $type === 'radio') {
            echo '<label class="flex items-center gap-2 text-sm font-semibold"><input type="radio" name="' . e($name) . '" value="' . e($otherToken) . '" ' . ($usingOther ? 'checked' : '') . ' class="h-4 w-4 js-has-other" />Other</label>';
        }
        echo '</div>';
        if ($allowOther && $type === 'radio') {
            echo '<input type="text" name="' . e('answers[' . $key . '__other]') . '" value="' . e($usingOther ? $otherText($current, $opts) : '') . '" class="' . $class . ' js-other-input' . ($usingOther ? '' : ' hidden') . '" placeholder="Type the other value" />';
        }
        echo '</div>';
        break;

    case 'checkboxes':
        $selected = is_array($value) ? array_map('strval', $value) : [];
        $usingOther = $allowOther && $isOtherSelected($selected, $options);
        echo '<div class="js-choice-field">';
        if ($selectAll) {
            echo '<label class="mt-2 flex items-center gap-2 text-sm font-bold"><input type="checkbox" class="h-4 w-4 js-select-all" /> Select all</label>';
        }
        echo '<div class="choice-grid cols-' . (int) $columns . '" data-checkbox-group="' . e($key) . '">';
        foreach ($options as $option) {
            $checked = in_array((string) $option, $selected, true) ? 'checked' : '';
            echo '<label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="' . e($name) . '[]" value="' . e($option) . '" ' . $checked . ' class="h-4 w-4 js-choice-box" />' . e($option) . '</label>';
        }
        if ($allowOther) {
            echo '<label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="' . e($name) . '[]" value="' . e($otherToken) . '" ' . ($usingOther ? 'checked' : '') . ' class="h-4 w-4 js-choice-box js-has-other" />Other</label>';
        }
        echo '</div>';
        if ($allowOther) {
            echo '<input type="text" name="' . e('answers[' . $key . '__other]') . '" value="' . e($usingOther ? $otherText($selected, $options) : '') . '" class="' . $class . ' js-other-input' . ($usingOther ? '' : ' hidden') . '" placeholder="Type the other value" />';
        }
        if ($minSelect || $maxSelect) {
            echo '<p class="field-hint">' . ($minSelect ? 'Pick at least ' . (int) $minSelect . '.' : '') . ($maxSelect ? ' At most ' . (int) $maxSelect . '.' : '') . '</p>';
        }
        echo '</div>';
        break;

    case 'checkbox':
    case 'consent':
        $boxValue = $options[0] ?? 'Yes';
        $checked = (string) $value !== '' && (string) $value !== 'No' ? 'checked' : '';
        $text = $help ?: ($type === 'consent' ? $field['label'] : $boxValue);
        echo '<label class="mt-2 flex items-start gap-2 text-sm font-semibold"><input type="checkbox" name="' . e($name) . '" value="' . e($boxValue) . '" ' . $checked . ' ' . ($required ? 'required' : '') . ' class="mt-0.5 h-4 w-4" /><span>' . e($text) . '</span></label>';
        break;

    case 'toggle':
        $on = in_array((string) $value, ['Yes', '1', 'on'], true);
        $off = (string) $value === 'No' || (string) $value === '0' || (!$required && !$on && (string) $value === '');
        echo '<div class="mt-2 flex gap-4">';
        echo '<label class="flex items-center gap-2 text-sm font-semibold"><input type="radio" name="' . e($name) . '" value="Yes" ' . ($on ? 'checked' : '') . ' ' . ($required ? 'required' : '') . ' class="h-4 w-4" />Yes</label>';
        echo '<label class="flex items-center gap-2 text-sm font-semibold"><input type="radio" name="' . e($name) . '" value="No" ' . ($off ? 'checked' : '') . ' class="h-4 w-4" />No</label>';
        echo '</div>';
        break;

    case 'number':
        $minAttr = ($field['range_min'] ?? '') !== '' ? ' min="' . e((string) $field['range_min']) . '"' : '';
        $maxAttr = ($field['range_max'] ?? '') !== '' ? ' max="' . e((string) $field['range_max']) . '"' : '';
        echo '<input type="number" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . $minAttr . $maxAttr . ' placeholder="' . e($placeholder) . '" class="' . $class . '" />';
        break;

    case 'email':
        echo '<input type="email" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder) . '" class="' . $class . '" />';
        break;

    case 'phone':
        echo '<input type="tel" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder ?: '2547...') . '" class="' . $class . '" />';
        break;

    case 'url':
        echo '<input type="url" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder ?: 'https://') . '" class="' . $class . '" />';
        break;

    case 'password':
        echo '<input type="password" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder) . '" class="' . $class . '" autocomplete="new-password" />';
        break;

    case 'date':
        echo '<input type="date" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '" />';
        break;

    case 'time':
        echo '<input type="time" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '" />';
        break;

    case 'datetime':
        $dt = (string) $value;
        if ($dt !== '' && strpos($dt, 'T') === false) {
            $dt = str_replace(' ', 'T', substr($dt, 0, 16));
        }
        echo '<input type="datetime-local" name="' . e($name) . '" value="' . e($dt) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '" />';
        break;

    case 'duration':
        $start = is_array($value) ? (string) ($value['start'] ?? '') : '';
        $end = is_array($value) ? (string) ($value['end'] ?? '') : '';
        echo '<div class="name-grid">';
        echo '<div><label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Start</label><input type="date" name="' . e($name) . '[start]" value="' . e($start) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '" /></div>';
        echo '<div><label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">End</label><input type="date" name="' . e($name) . '[end]" value="' . e($end) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '" /></div>';
        echo '</div>';
        echo '<p class="field-hint">Attachment placements cover a specific period. Students see this after they finish their course.</p>';
        break;

    case 'branches':
        $rows = is_array($value) && $value ? array_values($value) : [['name' => '', 'location' => '', 'extra' => []]];
        $extraLabels = FormFieldTypes::extraLabels($field);
        echo '<p class="field-hint">Add every branch. Name and location are required on each row.</p>';
        echo '<div class="branch-rows" data-branches="' . e($key) . '">';
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowName = (string) ($row['name'] ?? $row['title'] ?? '');
            $rowLocation = (string) ($row['location'] ?? '');
            $rowId = (int) ($row['id'] ?? 0);
            $rowExtra = is_array($row['extra'] ?? null) ? $row['extra'] : (is_array($row['details'] ?? null) ? $row['details'] : []);
            $extraByKey = [];
            foreach ($rowExtra as $extraLabel => $extraValue) {
                $extraByKey[FormFieldTypes::extraKey((string) $extraLabel)] = (string) $extraValue;
                $extraByKey[(string) $extraLabel] = (string) $extraValue;
            }
            echo '<div class="branch-row rounded-lg border border-neutral-200 p-3 space-y-3">';
            if ($rowId > 0) {
                echo '<input type="hidden" name="' . e($name) . '[' . (int) $index . '][id]" value="' . $rowId . '" />';
            }
            echo '<div class="name-grid">';
            echo '<div><label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Branch name</label><input type="text" name="' . e($name) . '[' . (int) $index . '][name]" value="' . e($rowName) . '" placeholder="e.g. Nairobi campus" class="' . $class . '" /></div>';
            echo '<div><label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">Location</label><input type="text" name="' . e($name) . '[' . (int) $index . '][location]" value="' . e($rowLocation) . '" placeholder="e.g. Westlands, Nairobi" class="' . $class . '" /></div>';
            echo '</div>';
            foreach ($extraLabels as $extraLabel) {
                $slug = FormFieldTypes::extraKey($extraLabel);
                $extraValue = (string) ($extraByKey[$slug] ?? $extraByKey[$extraLabel] ?? '');
                echo '<div><label class="text-[11px] font-bold uppercase" style="color:var(--ke-muted)">' . e($extraLabel) . '</label><input type="text" name="' . e($name) . '[' . (int) $index . '][extra][' . e($slug) . ']" value="' . e($extraValue) . '" class="' . $class . '" /></div>';
            }
            echo '<button type="button" class="remove-branch-row btn-danger" style="padding:0.4rem 0.7rem;">Remove branch</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="add-branch-row btn-secondary mt-2" style="padding:0.4rem 0.75rem;" data-branches-add="' . e($key) . '">Add another branch</button>';
        break;

    case 'color':
        echo '<input type="color" name="' . e($name) . '" value="' . e((string) ($value ?: '#006b3f')) . '" class="mt-2 h-10 w-20 rounded border border-neutral-300" />';
        break;

    case 'range':
        $min = is_numeric($field['range_min'] ?? null) ? $field['range_min'] : 0;
        $max = is_numeric($field['range_max'] ?? null) ? $field['range_max'] : 100;
        $current = $value !== '' && $value !== null ? $value : $min;
        echo '<input type="range" name="' . e($name) . '" min="' . e((string) $min) . '" max="' . e((string) $max) . '" value="' . e((string) $current) . '" class="w-full mt-3 js-range" />';
        echo '<p class="field-hint js-range-label">Value: ' . e((string) $current) . ' (range ' . e((string) $min) . ' – ' . e((string) $max) . ')</p>';
        break;

    case 'rating':
        $min = is_numeric($field['range_min'] ?? null) ? (int) $field['range_min'] : 1;
        $max = is_numeric($field['range_max'] ?? null) ? (int) $field['range_max'] : 5;
        if ($max < $min) {
            $max = $min;
        }
        echo '<div class="rating-options">';
        for ($i = $min; $i <= $max; $i++) {
            $checked = (string) $value === (string) $i ? 'checked' : '';
            echo '<label class="rating-option"><input type="radio" name="' . e($name) . '" value="' . $i . '" ' . $checked . ' ' . ($required ? 'required' : '') . ' /><span>' . $i . '</span></label>';
        }
        echo '</div>';
        break;

    case 'signature':
        echo '<input type="text" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder ?: 'Type your full name') . '" class="' . $class . ' signature-input" />';
        break;

    case 'list':
        $items = is_array($value) && $value ? $value : [''];
        echo '<div class="list-rows" data-list="' . e($key) . '">';
        foreach ($items as $item) {
            echo '<div class="list-row"><input type="text" name="' . e($name) . '[]" value="' . e((string) $item) . '" placeholder="' . e($placeholder) . '" class="' . $class . '" /><button type="button" class="remove-list-row btn-danger" style="padding:0.4rem 0.7rem;">Remove</button></div>';
        }
        echo '</div>';
        echo '<button type="button" class="add-list-row btn-secondary mt-2" style="padding:0.4rem 0.75rem;" data-list-add="' . e($key) . '">Add item</button>';
        break;

    case 'file':
    case 'image':
        $accept = $type === 'image' ? 'image/*' : '.pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.gif,.txt';
        if ($value) {
            $href = e(imageUrl((string) $value));
            echo '<p class="mt-2 text-sm font-semibold"><a href="' . $href . '" target="_blank" style="color:var(--ke-green)">Current file</a></p>';
            if ($type === 'image') {
                echo '<img src="' . $href . '" alt="" class="mt-2 max-h-28 rounded border border-neutral-200" />';
            }
        }
        echo '<input type="file" name="' . e($name) . '" accept="' . e($accept) . '" class="mt-2 block w-full text-sm" />';
        break;

    case 'files':
        $items = is_array($value) ? $value : ($value ? [$value] : []);
        foreach ($items as $item) {
            echo '<p class="mt-2 text-sm font-semibold"><a href="' . e(imageUrl((string) $item)) . '" target="_blank" style="color:var(--ke-green)">Current file</a></p>';
        }
        echo '<input type="file" name="' . e($name) . '[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.jpg,.jpeg,.png,.webp,.gif,application/pdf,image/*" class="mt-2 block w-full text-sm" />';
        echo '<p class="field-hint">You can attach several PDFs, images, or Word documents.</p>';
        break;

    case 'organisation':
        $orgs = \App\Models\Organisation::active();
        $multiple = ($field['org_mode'] ?? 'single') === 'multiple';
        $selected = is_array($value) ? array_map('strval', $value) : array_values(array_filter([(string) $value], fn($item) => $item !== ''));
        $roleSlug = is_array($viewer = ($currentUser ?? \App\Core\UserSession::current())) ? ($viewer['role_slug'] ?? '') : '';
        if (!$orgs) {
            echo '<p class="mt-2 text-sm font-bold" style="color:var(--ke-muted)">No organisations are available yet. Ask Super Admin to add them.</p>';
            break;
        }
        if ($multiple) {
            echo '<div class="choice-grid cols-1">';
            foreach ($orgs as $org) {
                $checked = in_array((string) $org['id'], $selected, true) ? 'checked' : '';
                echo '<label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="' . e($name) . '[]" value="' . (int) $org['id'] . '" ' . $checked . ' class="h-4 w-4" />' . e($org['name']) . '</label>';
            }
            echo '</div>';
            echo $roleSlug === 'student'
                ? '<p class="field-hint">Your dashboard only lists courses for the organisation(s) you pick, plus any global courses.</p>'
                : '<p class="field-hint">You can select more than one organisation. Each organisation admin must approve you before you are assigned.</p>';
        } else {
            echo '<select name="' . e($name) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '"><option value="">Choose organisation</option>';
            foreach ($orgs as $org) {
                $isOn = in_array((string) $org['id'], $selected, true) ? 'selected' : '';
                echo '<option value="' . (int) $org['id'] . '" ' . $isOn . '>' . e($org['name']) . '</option>';
            }
            echo '</select>';
            echo $roleSlug === 'student'
                ? '<p class="field-hint">Your dashboard then shows courses for this organisation, plus global courses such as basic skills.</p>'
                : '<p class="field-hint">The organisation you pick must approve you before you appear on their dashboard.</p>';
        }
        break;

    case 'category':
        $user = $currentUser ?? \App\Core\UserSession::current();
        $groups = \App\Models\OrganisationCategory::groupedForUser($user ?: null);
        $current = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        if (!$groups) {
            echo '<p class="mt-2 text-sm font-bold" style="color:var(--ke-muted)">No categories are available yet. An organisation admin must list categories, and you must be an approved tutor for that organisation.</p>';
            break;
        }
        echo '<select name="' . e($name) . '" ' . ($required ? 'required' : '') . ' class="' . $class . '"><option value="">Choose category</option>';
        foreach ($groups as $orgName => $cats) {
            echo '<optgroup label="' . e($orgName) . '">';
            foreach ($cats as $cat) {
                $isOn = $current === (string) $cat['id'] ? 'selected' : '';
                echo '<option value="' . (int) $cat['id'] . '" ' . $isOn . '>' . e($cat['name']) . '</option>';
            }
            echo '</optgroup>';
        }
        echo '</select>';
        echo '<p class="field-hint">Only categories from organisations that have approved you are shown.</p>';
        break;

    default:
        echo '<input type="text" name="' . e($name) . '" value="' . e((string) $value) . '" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder) . '" class="' . $class . '" />';
}
