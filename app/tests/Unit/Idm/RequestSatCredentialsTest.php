<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Idm;

use IServ\Bundle\Authentication\Jwt\DecodedJwt;
use IServ\Bundle\Authentication\Jwt\Provider\JwtProviderInterface;
use IServ\Library\UserToken\AccessToken\AccessToken;
use IServ\Library\UserToken\Stamps\Stamps;
use IServ\Library\UserToken\Test\User\TestUserBuilder;
use IServ\Library\UserToken\Token\AnonymousUserToken;
use IServ\Library\UserToken\Token\UserToken;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Idm\RequestSatCredentials;

#[CoversClass(RequestSatCredentials::class)]
final class RequestSatCredentialsTest extends TestCase
{
    public function testItForwardsTheAuthenticatedUsersSat(): void
    {
        $provider = $this->createMock(JwtProviderInterface::class);
        $provider->method('getDecodedJwt')->willReturn(DecodedJwt::createFromUserToken(new UserToken(
            new AccessToken('user-sat'),
            TestUserBuilder::create()->autousername()->getUser(),
            new Stamps(),
        )));

        $request = (new RequestSatCredentials($provider))->addToRequest(new Request('GET', '/groups'));

        self::assertSame('Bearer user-sat', $request->getHeaderLine('Authorization'));
    }

    public function testItRejectsAnAnonymousToken(): void
    {
        $provider = $this->createMock(JwtProviderInterface::class);
        $provider->method('getDecodedJwt')->willReturn(DecodedJwt::createFromUserToken(new AnonymousUserToken(new Stamps())));

        $this->expectException(\LogicException::class);
        (new RequestSatCredentials($provider))->addToRequest(new Request('GET', '/groups'));
    }
}
