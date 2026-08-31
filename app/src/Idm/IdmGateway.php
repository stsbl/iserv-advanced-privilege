<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Idm;

use IServ\Library\IdmApiClient\Hydrator\NullHydrator;
use IServ\Library\IdmApiClient\Hydrator\RawHydrator;
use IServ\Library\IdmApiClient\IdmClientInterface;
use Nyholm\Psr7\Stream;

/** Performs every group mutation through IDM on behalf of the authenticated administrator. */
final readonly class IdmGateway
{
    public function __construct(private IdmClientInterface $client)
    {
    }

    public function setOwner(string $groupUuid, ?string $userUuid): void
    {
        $this->patch($groupUuid, [
            'owner' => null === $userUuid ? null : '/iserv/idm/api/v1/users/' . $userUuid,
        ]);
    }

    /** @param list<string> $uuids */
    public function addPrivileges(string $groupUuid, array $uuids): void
    {
        $this->post($groupUuid . '/privileges', 'addedPrivileges', 'privileges', $uuids);
    }

    public function removePrivilege(string $groupUuid, string $uuid): void
    {
        $this->delete($groupUuid . '/privileges/' . rawurlencode($uuid));
    }

    /** @param list<string> $uuids */
    public function addFlags(string $groupUuid, array $uuids): void
    {
        $this->post($groupUuid . '/group_flags', 'addedFlags', 'group_flags', $uuids);
    }

    public function removeFlag(string $groupUuid, string $uuid): void
    {
        $this->delete($groupUuid . '/group_flags/' . rawurlencode($uuid));
    }

    /** @param array<string, string|null> $data */
    private function patch(string $groupUuid, array $data): void
    {
        $this->client->performRequest(
            'PATCH',
            'iserv/idm/api/v1/groups/' . rawurlencode($groupUuid),
            new NullHydrator(),
            ['Content-Type' => 'application/merge-patch+json'],
            Stream::create(json_encode($data, JSON_THROW_ON_ERROR)),
        );
    }

    /** @param list<string> $uuids */
    private function post(string $path, string $key, string $resource, array $uuids): void
    {
        $this->client->performRequest(
            'POST',
            'iserv/idm/api/v1/groups/' . $path,
            new RawHydrator(),
            ['Content-Type' => 'application/json'],
            Stream::create(json_encode([
                $key => array_map(
                    static fn(string $uuid): string => '/iserv/idm/api/v1/' . $resource . '/' . $uuid,
                    $uuids,
                ),
            ], JSON_THROW_ON_ERROR)),
        );
    }

    private function delete(string $path): void
    {
        $this->client->performRequest('DELETE', 'iserv/idm/api/v1/groups/' . $path, new NullHydrator());
    }
}
