<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Idm;

use IServ\Library\IdmApiClient\Hydrator\RawHydrator;
use IServ\Library\IdmApiClient\IdmClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Stsbl\IServ\AdvancedPrivilege\Idm\IdmGateway;

#[CoversClass(\Stsbl\IServ\AdvancedPrivilege\Idm\IdmGateway::class)]
final class IdmGatewayTest extends TestCase
{
    public function testAddingNoReferencesIsANoOp(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::never())->method('performRequest');

        (new IdmGateway($client))->addPrivileges('group-id', []);
    }

    public function testAddingPrivilegesUsesIdmReferences(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::once())->method('performRequest')->willReturnCallback(static function (string $method, string $url, RawHydrator $hydrator, array $headers, StreamInterface $body): array {
            self::assertSame('POST', $method);
            self::assertSame('iserv/idm/api/v1/groups/group-id/privileges', $url);
            self::assertSame(['Content-Type' => 'application/json'], $headers);
            self::assertSame(['addedPrivileges' => ['/iserv/idm/api/v1/privileges/privilege-id']], json_decode((string) $body, true, flags: JSON_THROW_ON_ERROR));

            return [];
        });

        (new IdmGateway($client))->addPrivileges('group-id', ['privilege-id']);
    }

    public function testItMutatesOwnersFlagsAndReferencesAtIdmEndpoints(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $calls = [];
        $client->expects(self::exactly(5))->method('performRequest')->willReturnCallback(static function (string $method, string $url, mixed $hydrator, array $headers = [], ?StreamInterface $body = null) use (&$calls): mixed {
            $calls[] = [$method, $url, $headers, null === $body ? null : json_decode((string) $body, true, flags: JSON_THROW_ON_ERROR)];

            return $hydrator->hydrate([]);
        });
        $gateway = new IdmGateway($client);
        $gateway->setOwner('group id', 'user-id');
        $gateway->setOwner('group-id', null);
        $gateway->addFlags('group-id', ['flag-id']);
        $gateway->removePrivilege('group-id', 'privilege/id');
        $gateway->removeFlag('group-id', 'flag/id');

        self::assertSame([
            ['PATCH', 'iserv/idm/api/v1/groups/group%20id', ['Content-Type' => 'application/merge-patch+json'], ['owner' => '/iserv/idm/api/v1/users/user-id']],
            ['PATCH', 'iserv/idm/api/v1/groups/group-id', ['Content-Type' => 'application/merge-patch+json'], ['owner' => null]],
            ['POST', 'iserv/idm/api/v1/groups/group-id/group_flags', ['Content-Type' => 'application/json'], ['addedFlags' => ['/iserv/idm/api/v1/group_flags/flag-id']]],
            ['DELETE', 'iserv/idm/api/v1/groups/group-id/privileges/privilege%2Fid', [], null],
            ['DELETE', 'iserv/idm/api/v1/groups/group-id/group_flags/flag%2Fid', [], null],
        ], $calls);
    }

}
