<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Autocomplete;

use IServ\Library\IdmApiClient\Hydrator\CallbackHydrator;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\Avatar\AvatarSize;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Avatar\Renderer\Exception\AvatarRendererException;
use IServ\Library\Uuid\Uuid;

/** Queries IDM directly so an authenticated administrator can select every active user as owner. */
/** @psalm-suppress MixedReturnTypeCoercion */
final readonly class GodModeUserLookup
{
    private const AUTOCOMPLETE_INCLUDE_PRIVILEGE_UUID = '464e390f-5cea-4835-b40c-c3d3303ae234';

    public function __construct(
        private IdmClientInterface $client,
        private AvatarRendererInterface $avatars,
    ) {
    }

    /** @return list<array{label: string, value: string, source: string, extra: string}> */
    public function search(string $query): array
    {
        $query = trim($query);
        if ('' === $query) {
            return [];
        }

        return $this->client->performRequest(
            'GET',
            'iserv/idm/api/v1/lookup/users?' . http_build_query([
                'query' => $query,
                'type' => 'user',
                'includePrivilege' => self::AUTOCOMPLETE_INCLUDE_PRIVILEGE_UUID,
                '_attributes' => 'hexUuid,user,firstname,lastname,auxInfo',
            ]),
            new CallbackHydrator($this->toSuggestions(...)),
        );
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
            new CallbackHydrator($this->toSuggestions(...)),
        );

        return $users;
    }

    public function label(string $uuid): ?string
    {
        return $this->lookup([$uuid])[0]['label'] ?? null;
    }

    /** @param array<array-key, mixed> $lookup
     * @return list<array{label: string, value: string, source: string, extra: string, avatarHtml: string|null}> */
    private function toSuggestions(array $lookup): array
    {
        $suggestions = [];
        $users = array_is_list($lookup) ? $lookup : [];
        if ([] === $users) {
            foreach (['exact', 'partial', 'fuzzy'] as $matches) {
                if (is_array($lookup[$matches] ?? null)) {
                    $users = array_merge($users, $lookup[$matches]);
                }
            }
        }
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
                'avatarHtml' => $this->avatar((string) $user['hexUuid']),
            ];
        }

        return $suggestions;
    }

    private function avatar(string $uuid): ?string
    {
        try {
            return $this->avatars->render(Uuid::createFromString($uuid), AvatarSize::default());
        } catch (AvatarRendererException|\InvalidArgumentException) {
            return null;
        }
    }
}
