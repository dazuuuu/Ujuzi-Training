<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Models\Organisation;
use App\Models\OrganisationBranch;
use App\Models\User;

class FormLookupController
{
    public function organisations(): void
    {
        $q = trim((string) Request::query('q', ''));
        $items = array_map(static fn(array $org): array => [
            'id' => (int) $org['id'],
            'name' => (string) $org['name'],
        ], Organisation::searchActive($q, 25));

        $this->respond(['items' => $items]);
    }

    public function branches(): void
    {
        $raw = (string) Request::query('organisation_ids', '');
        $ids = array_values(array_filter(array_map('intval', preg_split('/,/', $raw) ?: []), fn(int $id): bool => $id > 0));
        $providerRaw = (string) Request::query('attachment_provider_ids', '');
        $providerIds = array_values(array_filter(array_map('intval', preg_split('/,/', $providerRaw) ?: []), fn(int $id): bool => $id > 0));
        $branches = $providerIds
            ? OrganisationBranch::forActiveAttachmentProviderIds($providerIds)
            : OrganisationBranch::forActiveOrganisationIds($ids);
        $items = array_map(static fn(array $branch): array => [
            'id' => (int) $branch['id'],
            'organisation_id' => (int) ($branch['organisation_id'] ?? 0),
            'organisation_name' => (string) ($branch['organisation_name'] ?? ''),
            'owner_type' => (string) ($branch['owner_type'] ?? 'organisation'),
            'owner_user_id' => (int) ($branch['owner_user_id'] ?? 0),
            'owner_name' => (string) ($branch['owner_name'] ?? ''),
            'title' => (string) ($branch['title'] ?? ''),
            'location' => (string) ($branch['location'] ?? ''),
        ], $branches);

        $this->respond(['items' => $items]);
    }

    public function attachmentProviders(): void
    {
        $q = trim((string) Request::query('q', ''));
        $items = array_map(static fn(array $provider): array => [
            'id' => (int) $provider['id'],
            'name' => trim((string) (($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? ''))) ?: (string) ($provider['email'] ?? ''),
            'organisation_name' => (string) ($provider['organisation_name'] ?? ''),
        ], User::searchAttachmentProviders($q, 25));

        $this->respond(['items' => $items]);
    }

    private function respond(array $payload, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}
