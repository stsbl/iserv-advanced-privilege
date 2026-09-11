<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Common;

use IServ\Library\IdmApiClient\Exception\ClientException;
use IServ\Library\IdmApiClient\Hydrator\HydratorInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use Psr\Http\Message\StreamInterface;

/** In-memory IDM fixture for container and browser tests. */
final class StaticIdmClient implements IdmClientInterface
{
    /** @var list<array{method: string, url: string, body: string}> */
    public array $requests = [];

    public ?ClientException $exception = null;

    public ?string $exceptionOnUrl = null;

    public function performRequest(string $method, string $url, HydratorInterface $hydrator, array $headers = [], StreamInterface $body = null): mixed
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'body' => null === $body ? '' : (string) $body];
        if (null !== $this->exception && (null === $this->exceptionOnUrl || str_contains($url, $this->exceptionOnUrl))) {
            throw $this->exception;
        }

        return $hydrator->hydrate($this->response($url));
    }

    /** @return array<mixed> */
    private function response(string $url): array
    {
        if (str_contains($url, '/privileges?')) {
            return [['hexUuid' => 'privilege-id', 'name' => 'Manage groups']];
        }
        if (str_contains($url, '/group_flags?')) {
            return [['hexUuid' => 'flag-id', 'name' => 'has_home', 'title' => 'Has home directory']];
        }
        if (str_contains($url, '/lookup/users?') || str_contains($url, '/users?')) {
            return [['hexUuid' => '9b8241a1-5f7d-4d42-a8c6-2d2d6605ef68', 'user' => 'admin', 'firstname' => 'Ada', 'lastname' => 'Admin', 'auxInfo' => 'IT']];
        }
        if (str_contains($url, '/groups?')) {
            return [[
                'hexUuid' => 'group-id',
                'group' => 'admins',
                'name' => 'Admins',
                'owner' => null,
                'privileges' => [],
                'flags' => [],
            ]];
        }

        return [];
    }
}
