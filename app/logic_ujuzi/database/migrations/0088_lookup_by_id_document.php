<?php

/**
 * Student lookup also finds a student by National ID, passport or birth
 * certificate number: update the wording on saved public lookup pages.
 */
return [
    'title' => 'Student lookup by ID, passport or birth certificate number',
    'up' => static function (PDO $pdo): void {
        $swaps = [
            'Enter the registration number or certificate number printed on their certificate.' =>
                'Enter the registration number or certificate number printed on their certificate, or their National ID, passport or birth certificate number.',
            'e.g. UJ-2026-00012 or UJC-…' => 'Reg. no., certificate no., ID, passport or birth cert. no.',
            'e.g. UJ-2026-00012 or UJC-…' => 'Reg. no., certificate no., ID, passport or birth cert. no.',
        ];
        $rows = $pdo->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_value LIKE '%UJ-2026-00012%' OR setting_value LIKE '%printed on their certificate.%'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $save = $pdo->prepare('UPDATE store_settings SET setting_value = ? WHERE setting_key = ?');
        foreach ($rows as $key => $value) {
            $new = strtr((string) $value, $swaps);
            if ($new !== $value) {
                $save->execute([$new, $key]);
            }
        }
    },
    'down' => static function (PDO $pdo): void {
    },
];
