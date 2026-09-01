<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Service;

use IServ\Library\IdmApiClient\Hydrator\HydratorInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Service\IdmReferenceProvider;

#[CoversClass(\Stsbl\IServ\AdvancedPrivilege\Service\IdmReferenceProvider::class)]
final class IdmReferenceProviderTest extends TestCase
{
    public function testGroupsIncludeCurrentPrivilegeAndFlagReferences(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::once())->method('performRequest')->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator): array {
            self::assertSame('GET', $method);
            self::assertStringContainsString('privileges.hexUuid,flags.hexUuid', $url);

            return $hydrator->hydrate([[
                'hexUuid' => 'group-id', 'group' => 'admins', 'name' => 'Admins',
                'owner' => ['hexUuid' => 'owner-id'],
                'privileges' => [['hexUuid' => 'privilege-id']],
                'flags' => [['hexUuid' => 'flag-id']],
            ], [
                'hexUuid' => 'group-two', 'group' => 'users', 'name' => 'Users',
                'owner' => 'not-a-reference', 'privileges' => 'invalid', 'flags' => [['broken' => true]],
            ], ['invalid' => true]]);
        });

        self::assertSame([[
            'uuid' => 'group-id', 'group' => 'admins', 'name' => 'Admins', 'owner' => 'owner-id',
            'privileges' => ['privilege-id'], 'flags' => ['flag-id'],
        ], [
            'uuid' => 'group-two', 'group' => 'users', 'name' => 'Users', 'owner' => null,
            'privileges' => [], 'flags' => [],
        ]], (new IdmReferenceProvider($client))->groups());
    }

    public function testReferencesAreTranslatedAndNaturallySorted(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::exactly(2))->method('performRequest')->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator): array {
            self::assertSame('GET', $method);

            return $hydrator->hydrate(str_contains($url, '/privileges?') ? [
                ['hexUuid' => 'b', 'name' => 'Zebra'],
                ['hexUuid' => 'a', 'name' => 'Apple'],
                ['name' => 'Skipped'],
            ] : [
                ['hexUuid' => 'flag', 'title' => 'Has home directory', 'name' => 'has_home'],
                ['hexUuid' => 'fallback'],
            ]);
        });
        $provider = new IdmReferenceProvider($client);

        self::assertSame(['a' => 'Apple', 'b' => 'Zebra'], $provider->privileges());
        self::assertSame(['fallback' => 'fallback', 'flag' => 'Has home directory – has_home'], $provider->flags());
    }

}
