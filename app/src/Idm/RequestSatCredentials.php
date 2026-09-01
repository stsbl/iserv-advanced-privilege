<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Idm;

use IServ\Bundle\Authentication\Jwt\Provider\JwtProviderInterface;
use IServ\Library\IdmApiClient\Authentication\Credentials;
use IServ\Library\UserToken\AuthenticatedUserTokenInterface;
use Psr\Http\Message\RequestInterface;

final readonly class RequestSatCredentials implements Credentials
{
    public function __construct(private JwtProviderInterface $jwtProvider)
    {
    }

    public function addToRequest(RequestInterface $request): RequestInterface
    {
        $token = $this->jwtProvider->getDecodedJwt()->getUserToken();
        if (!$token instanceof AuthenticatedUserTokenInterface) {
            throw new \LogicException('Missing authenticated user token.');
        }

        return $request->withHeader('Authorization', 'Bearer ' . $token->getAccessToken()->toString());
    }
}
