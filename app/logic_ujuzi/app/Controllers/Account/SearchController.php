<?php

namespace App\Controllers\Account;

use App\Core\AccountNav;
use App\Core\Authz;
use App\Core\Request;
use App\Models\AttachmentAudience;
use App\Models\Course;
use App\Models\Organisation;
use App\Models\User;

/**
 * The portal's smart search (top bar). Searches only what this person can
 * open: courses, organisations providing attachment, organisations providing
 * courses and portal pages. Results are ranked by how well they match
 * (whole title, word starts, then anywhere in the details), and words with a
 * small typo still match. Returns JSON for the search panel.
 */
class SearchController extends BaseAccountController
{
    private const LIMIT = 8;

    public function index(): void
    {
        $q = mb_strtolower(trim((string) Request::query('q', '')));
        $type = (string) Request::query('type', 'all');
        $terms = array_values(array_filter(preg_split('/\s+/', $q) ?: [], static fn(string $t): bool => $t !== ''));

        $groups = [];
        if ($terms) {
            $want = static fn(string $g): bool => $type === 'all' || $type === $g;
            if ($want('courses')) {
                $groups['courses'] = $this->rank($terms, $this->courses());
            }
            if ($want('attachment')) {
                $groups['attachment'] = $this->rank($terms, $this->attachmentProviders());
            }
            if ($want('organisations')) {
                $groups['organisations'] = $this->rank($terms, $this->courseOrganisations());
            }
            if ($want('pages')) {
                $groups['pages'] = $this->rank($terms, $this->pages());
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['q' => $q, 'groups' => $groups]);
        exit;
    }

    /** Best matches first; items that miss any search word are dropped. */
    private function rank(array $terms, array $items): array
    {
        $scored = [];
        foreach ($items as $item) {
            $title = mb_strtolower($item['title']);
            $haystack = $title . ' ' . mb_strtolower($item['keywords'] ?? '') . ' ' . mb_strtolower($item['subtitle'] ?? '');
            $words = preg_split('/[^\p{L}\p{N}]+/u', $haystack, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $score = 0;
            foreach ($terms as $term) {
                $best = 0;
                if (str_starts_with($title, $term)) {
                    $best = 100;
                } elseif (preg_match('/\b' . preg_quote($term, '/') . '/u', $title)) {
                    $best = 70;
                } elseif (str_contains($title, $term)) {
                    $best = 50;
                } elseif (preg_match('/\b' . preg_quote($term, '/') . '/u', $haystack)) {
                    $best = 35;
                } elseif (str_contains($haystack, $term)) {
                    $best = 20;
                } elseif (mb_strlen($term) >= 4) {
                    // Typo tolerance: one wrong letter in 4–6, two in longer words.
                    $allowed = mb_strlen($term) >= 7 ? 2 : 1;
                    foreach ($words as $word) {
                        if (abs(mb_strlen($word) - mb_strlen($term)) <= $allowed
                            && levenshtein($term, $word) <= $allowed) {
                            $best = 15;
                            break;
                        }
                        if (mb_strlen($word) > mb_strlen($term) && levenshtein($term, mb_substr($word, 0, mb_strlen($term))) <= 1) {
                            $best = max($best, 10); // typo in the start of a longer word
                        }
                    }
                }
                if ($best === 0) {
                    continue 2;
                }
                $score += $best;
            }
            unset($item['keywords']);
            $item['score'] = $score;
            $scored[] = $item;
        }
        usort($scored, static fn(array $a, array $b): int => [$b['score'], $a['title']] <=> [$a['score'], $b['title']]);
        return array_slice($scored, 0, self::LIMIT);
    }

    private function courses(): array
    {
        try {
            $role = (string) ($this->user['role_slug'] ?? '');
            if (Authz::isStudent($this->user)) {
                $courses = Course::forLearner(Authz::learnerOrganisationIds($this->user), Authz::approvedCategoryIds($this->user));
            } elseif ($role === 'trainer') {
                $courses = Course::forTrainer((int) $this->user['id']);
            } elseif (Authz::isOrganisationAdmin($this->user)) {
                $courses = Course::forOrganisation((int) $this->user['organisation_id']);
            } else {
                $courses = Course::publicListing();
            }
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($courses as $course) {
            $fee = (float) ($course['enrollment_fee_ksh'] ?? 0);
            $out[] = [
                'title' => (string) $course['title'],
                'subtitle' => implode(' · ', array_filter([$course['category_name'] ?? '', $course['organisation_name'] ?? '', $fee > 0 ? 'Ksh ' . number_format($fee) : 'Free'])),
                'keywords' => implode(' ', [$course['description'] ?? '', $course['first_name'] ?? '', $course['last_name'] ?? '']),
                'url' => url('/account/courses/' . (int) $course['id']),
                'image' => !empty($course['cover_image']) && preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $course['cover_image']) ? imageUrl($course['cover_image']) : null,
                'kind' => 'Course',
            ];
        }
        return $out;
    }

    /**
     * Students see the attachment organisations their courses can request
     * (opening straight onto that course's choice); everyone else sees every
     * attachment organisation's details page.
     */
    private function attachmentProviders(): array
    {
        try {
            $providers = User::allAttachmentProviders();
            $courseFor = [];
            if (Authz::isStudent($this->user)) {
                foreach (AttachmentAudience::studentCourses((int) $this->user['id']) as $course) {
                    foreach (AttachmentAudience::providersForCategories(array_keys($course['categories'])) as $providerId => $ids) {
                        $courseFor[$providerId] ??= $course['id'];
                    }
                }
            }
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($providers as $provider) {
            $id = (int) $provider['id'];
            if (Authz::isStudent($this->user)) {
                if (!isset($courseFor[$id])) {
                    continue;
                }
                $url = url('/account/attachment-providers/' . $id . '?course=' . $courseFor[$id]);
            } elseif (!empty($provider['organisation_id'])) {
                $url = url('/account/organisations/' . (int) $provider['organisation_id']);
            } else {
                continue;
            }
            $branches = array_map(static fn(array $b): string => trim(($b['title'] ?? '') . ' ' . ($b['location'] ?? '')), $provider['attachment_branches'] ?? []);
            $out[] = [
                'title' => (string) ($provider['organisation_name'] ?: ($provider['listing_name'] ?: userDisplayName($provider))),
                'subtitle' => implode(' · ', array_filter([$provider['listing_location'] ?? '', count($branches) ? count($branches) . ' branch' . (count($branches) === 1 ? '' : 'es') : ''])),
                'keywords' => implode(' ', array_merge([$provider['listing_offered'] ?? '', $provider['listing_location'] ?? ''], $branches)),
                'url' => $url,
                'image' => null,
                'kind' => 'Attachment',
            ];
        }
        return $out;
    }

    private function courseOrganisations(): array
    {
        try {
            $organisations = Organisation::providingCourses();
        } catch (\Throwable $e) {
            return [];
        }
        return array_map(static fn(array $org): array => [
            'title' => (string) $org['name'],
            'subtitle' => (string) ($org['location'] ?? ''),
            'keywords' => (string) ($org['description'] ?? ''),
            'url' => url('/account/organisations/' . (int) $org['id']),
            'image' => !empty($org['logo_path']) ? imageUrl($org['logo_path']) : null,
            'kind' => 'Organisation',
        ], $organisations);
    }

    /** This portal's menu items plus the pages everyone has. */
    private function pages(): array
    {
        $role = (string) ($this->user['role_slug'] ?? '');
        $portal = Authz::isStudent($this->user) ? AccountNav::PORTAL_STUDENT
            : (Authz::isOrganisationAdmin($this->user) ? AccountNav::PORTAL_ORGANISATION_ADMIN
            : ($role === 'attachment_trainer' ? AccountNav::PORTAL_ATTACHMENT_TRAINER
            : ($role === 'course_branch_admin' ? AccountNav::PORTAL_COURSE_BRANCH_ADMIN : '')));
        $items = $portal !== '' ? AccountNav::orderedItems($portal) : [];
        $items['dashboard'] = ['dashboard', 'Dashboard', '/account/dashboard'];
        $items['profile'] = ['user', 'Profile', '/account/profile'];
        $items['password'] = ['key', 'Change password', '/account/change-password'];
        $out = [];
        foreach ($items as [, $label, $href]) {
            $out[] = ['title' => $label, 'subtitle' => 'Page', 'keywords' => $href, 'url' => url($href), 'image' => null, 'kind' => 'Page'];
        }
        return $out;
    }
}
