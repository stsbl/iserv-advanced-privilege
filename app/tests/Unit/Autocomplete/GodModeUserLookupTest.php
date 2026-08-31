<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Autocomplete;

use IServ\Library\IdmApiClient\Hydrator\HydratorInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Autocomplete\GodModeUserLookup;

final class GodModeUserLookupTest extends TestCase
{
    public function testSearchCombinesAccountAndNameMatches(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::exactly(3))
            ->method('performRequest')
            ->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator): array {
                self::assertSame('GET', $method);
                self::assertStringContainsString('deleted=false', $url);

                return $hydrator->hydrate([[
                    'hexUuid' => '4e338df0-e93e-494e-abcf-72b124a38365',
                    'user' => 'max',
                    'firstname' => 'Max',
                    'lastname' => 'Mustermann',
                    'auxInfo' => '10a',
                ]]);
            })
        ;

        self::assertSame([[
            'label' => 'Max Mustermann',
            'value' => 'user:4e338df0-e93e-494e-abcf-72b124a38365',
            'source' => 'user',
            'extra' => 'max · 10a',
        ]], (new GodModeUserLookup($client))->search('max'));
    }

    public function testLookupUsesUuidFilter(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::once())
            ->method('performRequest')
            ->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator): array {
                self::assertSame('GET', $method);
                self::assertStringContainsString('uuid=id-one%2Cid-two', $url);

                return $hydrator->hydrate([]);
            })
        ;

        self::assertSame([], (new GodModeUserLookup($client))->lookup(['id-one', 'id-two']));
    }
}
