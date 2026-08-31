<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Service;

use IServ\Library\IdmApiClient\Hydrator\CallbackHydrator;
use IServ\Library\IdmApiClient\IdmClientInterface;

final readonly class IdmReferenceProvider
{
    public function __construct(private IdmClientInterface $client)
    {
    }

    /** @return array<string, string> */
    public function privileges(): array
    {
        return $this->references('privileges', 'privilege', ['module', 'name']);
    }

    /** @return array<string, string> */
    public function flags(): array
    {
        return $this->references('group_flags', 'groupFlag', ['title', 'name']);
    }

    /** @return list<array{uuid: string, name: string, owner: ?string}> */
    public function groups(): array
    {
        /** @var list<array{uuid: string, name: string, owner: ?string}> $groups */
        $groups = $this->client->performRequest('GET', 'iserv/idm/api/v1/groups?_attributes=hexUuid,name,owner', new CallbackHydrator(
            static function (array $items): array {
                $groups = [];
                foreach ($items as $item) {
                    if (!is_array($item) || !is_string($item['hexUuid'] ?? null) || !is_string($item['name'] ?? null)) {
                        continue;
                    }
                    $ownerData = $item['owner'] ?? null;
                    $owner = is_array($ownerData) ? ($ownerData['hexUuid'] ?? null) : null;
                    $groups[] = ['uuid' => $item['hexUuid'], 'name' => $item['name'], 'owner' => is_string($owner) ? $owner : null];
                }

                return $groups;
            },
        ));

        return $groups;
    }

    /** @param list<string> $labels
     * @return array<string, string> */
    private function references(string $resource, string $fallback, array $labels): array
    {
        /** @var array<string, string> $references */
        $references = $this->client->performRequest('GET', 'iserv/idm/api/v1/' . $resource . '?_attributes=hexUuid,' . implode(',', $labels), new CallbackHydrator(
            static function (array $items) use ($labels, $fallback): array {
                $result = [];
                foreach ($items as $item) {
                    if (!is_array($item) || !is_string($item['hexUuid'] ?? null)) {
                        continue;
                    }
                    $parts = array_filter(array_map(static fn(string $label): mixed => $item[$label] ?? null, $labels), 'is_string');
                    $result[$item['hexUuid']] = '' === implode(' – ', $parts) ? ($item[$fallback] ?? $item['hexUuid']) : implode(' – ', $parts);
                }
                asort($result, SORT_NATURAL | SORT_FLAG_CASE);

                return $result;
            },
        ));

        return $references;
    }
}
