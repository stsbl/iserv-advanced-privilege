<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Autocomplete;

use IServ\Library\IdmApiClient\Hydrator\CallbackHydrator;
use IServ\Library\IdmApiClient\IdmClientInterface;

/** Queries IDM directly so an authenticated administrator can select every active user as owner. */
/** @psalm-suppress MixedReturnTypeCoercion */
final readonly class GodModeUserLookup
{
    public function __construct(private IdmClientInterface $client)
    {
    }

    /** @return list<array{label: string, value: string, source: string, extra: string}> */
    public function search(string $query): array
    {
        $query = trim($query);
        if ('' === $query) {
            return [];
        }

        $suggestions = [];
        foreach (['user', 'firstname', 'lastname'] as $field) {
            $users = $this->client->performRequest(
                'GET',
                'iserv/idm/api/v1/users?' . http_build_query([
                    'deleted' => 'false',
                    $field . '[icontains]' => $query,
                    '_attributes' => 'hexUuid,user,firstname,lastname,auxInfo',
                ]),
                new CallbackHydrator(self::toSuggestions(...)),
            );
            foreach ($users as $user) {
                $suggestions[$user['value']] = $user;
            }
        }

        return array_values($suggestions);
    }

    /** @param list<string> $uuids
     * @return list<array{label: string, value: string, source: string, extra: string}> */
    public function lookup(array $uuids): array
    {
        if ([] === $uuids) {
            return [];
        }

        /** @var list<array{label: string, value: string, source: string, extra: string}> $users */
        $users = $this->client->performRequest(
            'GET',
            'iserv/idm/api/v1/users?' . http_build_query([
                'uuid' => implode(',', $uuids),
                '_attributes' => 'hexUuid,user,firstname,lastname,auxInfo',
            ]),
            new CallbackHydrator(self::toSuggestions(...)),
        );

        return $users;
    }

    /** @param array<array-key, mixed> $users
     * @return list<array{label: string, value: string, source: string, extra: string}> */
    private static function toSuggestions(array $users): array
    {
        $suggestions = [];
        foreach ($users as $user) {
            if (!is_array($user) || !is_string($user['hexUuid'] ?? null) || !is_string($user['user'] ?? null)) {
                continue;
            }

            $firstName = is_string($user['firstname'] ?? null) ? $user['firstname'] : '';
            $lastName = is_string($user['lastname'] ?? null) ? $user['lastname'] : '';
            $name = trim($firstName . ' ' . $lastName);
            $suggestions[] = [
                'label' => '' === $name ? $user['user'] : $name,
                'value' => 'user:' . (string) $user['hexUuid'],
                'source' => 'user',
                'extra' => $user['user'] . (is_string($user['auxInfo'] ?? null) ? ' · ' . $user['auxInfo'] : ''),
            ];
        }

        return $suggestions;
    }
}
