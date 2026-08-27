<?php

namespace App\Models;

class FormFieldTypes
{
    public static function groups(): array
    {
        return [
            'Basic' => [
                'text' => 'Short text',
                'paragraph' => 'Paragraph',
                'name' => 'Name (first & last)',
                'email' => 'Email',
                'phone' => 'Phone',
                'url' => 'Website / URL',
                'number' => 'Number',
                'password' => 'Password / PIN',
            ],
            'Choices' => [
                'dropdown' => 'Dropdown',
                'multiselect' => 'Multi-select',
                'radio' => 'Radio buttons',
                'checkboxes' => 'Checkboxes',
                'checkbox' => 'Single checkbox',
                'yesno' => 'Yes / No',
                'toggle' => 'Toggle',
                'consent' => 'Consent / agreement',
            ],
            'Date & time' => [
                'date' => 'Date',
                'time' => 'Time',
                'datetime' => 'Date & time',
                'duration' => 'Attachment duration (start & end)',
            ],
            'Location' => [
                'county' => 'Kenya county',
                'address' => 'Address (Kenya county)',
                'organisation' => 'Organisations (from database)',
                'category' => 'Organisation category',
            ],
            'Files' => [
                'file' => 'File upload',
                'image' => 'Image upload',
                'files' => 'Documents (PDF, images, Word)',
            ],
            'Advanced' => [
                'color' => 'Colour picker',
                'range' => 'Range slider',
                'rating' => 'Rating',
                'signature' => 'Signature',
                'list' => 'Repeater list',
            ],
            'Layout' => [
                'heading' => 'Section heading',
                'instructions' => 'Instructions',
                'hidden' => 'Hidden field',
            ],
        ];
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::groups() as $group) {
            foreach ($group as $slug => $label) {
                $labels[$slug] = $label;
            }
        }
        return $labels;
    }

    public static function slugs(): array
    {
        return array_keys(self::labels());
    }

    public static function label(string $type): string
    {
        return self::labels()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::slugs(), true);
    }

    public static function needsChoices(string $type): bool
    {
        return in_array($type, ['dropdown', 'multiselect', 'radio', 'checkboxes'], true);
    }

    public static function isMulti(string $type): bool
    {
        return in_array($type, ['checkboxes', 'multiselect'], true);
    }

    public static function isLayout(string $type): bool
    {
        return in_array($type, ['heading', 'instructions'], true);
    }

    public static function isFile(string $type): bool
    {
        return in_array($type, ['file', 'image', 'files'], true);
    }

    public static function isComposite(string $type): bool
    {
        return in_array($type, ['name', 'duration'], true);
    }

    public static function allowsOther(string $type): bool
    {
        return in_array($type, ['dropdown', 'radio', 'checkboxes'], true);
    }

    public static function hasColumns(string $type): bool
    {
        return in_array($type, ['radio', 'checkboxes'], true);
    }

    public static function hasMinMaxSelect(string $type): bool
    {
        return in_array($type, ['checkboxes', 'multiselect'], true);
    }

    public static function needsPlaceholder(string $type): bool
    {
        return in_array($type, ['text', 'paragraph', 'email', 'phone', 'url', 'number', 'password', 'hidden', 'signature', 'list'], true);
    }

    public static function needsRange(string $type): bool
    {
        return in_array($type, ['range', 'number', 'rating'], true);
    }

    public static function countries(): array
    {
        return [
            'Kenya', 'Uganda', 'Tanzania', 'Rwanda', 'Burundi', 'South Sudan', 'Ethiopia', 'Somalia',
            'Nigeria', 'Ghana', 'South Africa', 'Egypt', 'Morocco', 'Algeria', 'Tunisia',
            'United Kingdom', 'United States', 'Canada', 'India', 'China', 'Germany', 'France',
            'United Arab Emirates', 'Saudi Arabia', 'Australia', 'Other',
        ];
    }

    public static function counties(): array
    {
        return [
            'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Garissa', 'Homa Bay',
            'Isiolo', 'Kajiado', 'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii',
            'Kisumu', 'Kitui', 'Kwale', 'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera',
            'Marsabit', 'Meru', 'Migori', 'Mombasa', "Murang'a", 'Nairobi', 'Nakuru', 'Nandi',
            'Narok', 'Nyamira', 'Nyandarua', 'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River',
            'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu', 'Vihiga', 'Wajir', 'West Pokot',
        ];
    }
}
