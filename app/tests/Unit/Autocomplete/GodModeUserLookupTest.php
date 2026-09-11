<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Autocomplete;

use IServ\Library\IdmApiClient\Hydrator\HydratorInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Avatar\Renderer\Exception\AvatarRendererException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Autocomplete\GodModeUserLookup;

#[CoversClass(\Stsbl\IServ\AdvancedPrivilege\Autocomplete\GodModeUserLookup::class)]
final class GodModeUserLookupTest extends TestCase
{
    public function testSearchCombinesAccountAndNameMatches(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::once())
            ->method('performRequest')
            ->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator): array {
                self::assertSame('GET', $method);
                self::assertStringContainsString('lookup/users?', $url);

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
            'avatarHtml' => '',
        ]], (new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class)))->search('max'));
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

        self::assertSame([], (new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class)))->lookup(['id-one', 'id-two']));
    }

    public function testLookupSkipsTheRequestForNoUsersAndLabelUsesTheFirstResult(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::once())->method('performRequest')->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator): array {
            self::assertSame('GET', $method);
            self::assertStringContainsString('uuid=user-id', $url);

            return $hydrator->hydrate([['hexUuid' => '4e338df0-e93e-494e-abcf-72b124a38365', 'user' => 'ada', 'firstname' => 'Ada', 'lastname' => 'Admin']]);
        });
        $lookup = new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class));

        self::assertSame([], $lookup->lookup([]));
        self::assertSame('Ada Admin', $lookup->label('user-id'));
    }

    public function testSearchMergesIdmMatchBucketsAndSkipsMalformedUsers(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->method('performRequest')->willReturnCallback(static function (string $_method, string $_url, HydratorInterface $hydrator): array {
            return $hydrator->hydrate(['exact' => [['hexUuid' => '4e338df0-e93e-494e-abcf-72b124a38365', 'user' => 'ada']], 'partial' => [['invalid' => true]], 'fuzzy' => []]);
        });

        self::assertSame('ada', (new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class)))->search('ada')[0]['label']);
    }


    public function testEmptySearchDoesNotCallIdm(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->expects(self::never())->method('performRequest');

        self::assertSame([], (new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class)))->search('  '));
    }

    public function testAvatarRenderingFailureDoesNotHideUsers(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->method('performRequest')->willReturnCallback(static function (string $_method, string $_url, HydratorInterface $hydrator): array {
            return $hydrator->hydrate([['hexUuid' => '4e338df0-e93e-494e-abcf-72b124a38365', 'user' => 'ada']]);
        });
        $avatars = $this->createMock(AvatarRendererInterface::class);
        $avatars->method('render')->willThrowException(new AvatarRendererException('avatar unavailable'));

        self::assertNull((new GodModeUserLookup($client, $avatars))->search('ada')[0]['avatarHtml']);
    }

}
