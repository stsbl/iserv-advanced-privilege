<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Service;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\IdmApiClient\Hydrator\HydratorInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Autocomplete\GodModeUserLookup;
use Stsbl\IServ\AdvancedPrivilege\Idm\IdmGateway;
use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;
use Stsbl\IServ\AdvancedPrivilege\Service\BulkMutationHandler;
use Stsbl\IServ\AdvancedPrivilege\Service\IdmReferenceProvider;

#[CoversClass(BulkMutationHandler::class)]
final class BulkMutationHandlerTest extends TestCase
{
    public function testResultMessagesUseSingularAndPluralFormsForPrivilegesAndFlags(): void
    {
        $handler = new BulkMutationHandler(
            new IdmReferenceProvider($this->createMock(IdmClientInterface::class)),
            new IdmGateway($this->createMock(IdmClientInterface::class)),
            new GodModeUserLookup($this->createMock(IdmClientInterface::class), $this->createMock(AvatarRendererInterface::class)),
            $this->createMock(AvatarRendererInterface::class),
        );

        self::assertSame(['Added one privilege to one group.'], $handler->resultMessages($this->mutation(['privilege'], []), 1));
        self::assertSame(['Added one privilege to 2 groups.'], $handler->resultMessages($this->mutation(['privilege'], []), 2));
        self::assertSame(['Added 2 privileges to one group.'], $handler->resultMessages($this->mutation(['one', 'two'], []), 1));
        self::assertSame(['Added 2 privileges to 2 groups.'], $handler->resultMessages($this->mutation(['one', 'two'], []), 2));
        self::assertSame(['Added one group flag to one group.'], $handler->resultMessages($this->mutation([], ['flag']), 1));
        self::assertSame(['Added 2 group flags to 2 groups.'], $handler->resultMessages($this->mutation([], ['one', 'two']), 2));
    }


    public function testUpdateGroupsOnlySendsReferencesThatNeedChanging(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $requests = [];
        $client->method('performRequest')->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator, array $headers = [], ?StreamInterface $body = null) use (&$requests): mixed {
            $requests[] = [$method, $url, null === $body ? null : json_decode((string) $body, true, flags: JSON_THROW_ON_ERROR)];
            if (str_contains($url, '/groups?')) {
                return $hydrator->hydrate([['hexUuid' => 'group-id', 'group' => 'admins', 'name' => 'Admins', 'owner' => null, 'privileges' => [['hexUuid' => 'already']], 'flags' => [['hexUuid' => 'flag']]]]);
            }

            return $hydrator->hydrate([]);
        });
        $handler = new BulkMutationHandler(
            new IdmReferenceProvider($client),
            new IdmGateway($client),
            new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class)),
            $this->createMock(AvatarRendererInterface::class),
        );
        $mutation = $this->mutation(['already', 'missing'], ['flag']);
        $mutation->target = 'all';

        self::assertSame(1, $handler->updateGroups($mutation));
        self::assertSame(['POST', 'iserv/idm/api/v1/groups/group-id/privileges', ['addedPrivileges' => ['/iserv/idm/api/v1/privileges/missing']]], $requests[1]);
        self::assertCount(2, $requests);
    }


    public function testUpdateOwnerAndGroupPreviewsUseFilteredSortedTargets(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $requests = [];
        $client->method('performRequest')->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator, array $headers = [], ?StreamInterface $body = null) use (&$requests): mixed {
            $requests[] = [$method, $url, null === $body ? null : json_decode((string) $body, true, flags: JSON_THROW_ON_ERROR)];
            if (str_contains($url, '/groups?')) {
                return $hydrator->hydrate([
                    ['hexUuid' => 'z-id', 'group' => 'zadmins', 'name' => 'Z Admins', 'owner' => ['hexUuid' => 'old-owner'], 'privileges' => [], 'flags' => []],
                    ['hexUuid' => 'a-id', 'group' => 'admins', 'name' => 'Admins', 'owner' => ['hexUuid' => 'old-owner'], 'privileges' => [], 'flags' => []],
                ]);
            }

            return $hydrator->hydrate([]);
        });
        $avatars = $this->createMock(AvatarRendererInterface::class);
        $avatars->method('renderPlaceholder')->willReturn('avatar');
        $handler = new BulkMutationHandler(
            new IdmReferenceProvider($client), new IdmGateway($client),
            new GodModeUserLookup($client, $avatars), $avatars,
        );
        $owner = new OwnerMutation();
        $owner->target = 'contains';
        $owner->pattern = 'Admins';
        $owner->owner = new AutocompleteTagsData('user:new-owner', 'new-owner', 'user');

        self::assertSame(2, $handler->updateOwner($owner));
        self::assertSame(['PATCH', 'iserv/idm/api/v1/groups/a-id', ['owner' => '/iserv/idm/api/v1/users/new-owner']], $requests[1]);
        self::assertSame(['Admins', 'Z Admins'], array_column($handler->groupPreviewsForTarget('contains', 'Admins'), 'name'));
        self::assertSame([], $handler->groupsForTarget('unknown', null));
        self::assertSame(['Admins'], array_column($handler->groupsForTarget('matches', '^Admins$'), 'name'));
        self::assertSame(['Admins'], array_column($handler->groupsForTarget('starts-with', 'Admin'), 'name'));
        self::assertSame(['Admins', 'Z Admins'], array_column($handler->groupsForTarget('ends-with', 'Admins'), 'name'));
        $removeOwner = new OwnerMutation();
        self::assertSame(['Remove owner'], $handler->changes($removeOwner));
        self::assertSame(['No matching groups found.'], $handler->resultMessages($removeOwner, 0));
        self::assertSame(['Removed owner of one group.'], $handler->resultMessages($removeOwner, 1));
    }


    public function testRevokeOnlyRemovesPresentReferencesAndDescribesChanges(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $requests = [];
        $client->method('performRequest')->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator, array $headers = [], ?StreamInterface $body = null) use (&$requests): mixed {
            $requests[] = [$method, $url];
            if (str_contains($url, '/groups?')) {
                return $hydrator->hydrate([['hexUuid' => 'group-id', 'group' => 'admins', 'name' => 'Admins', 'owner' => null, 'privileges' => [['hexUuid' => 'present']], 'flags' => [['hexUuid' => 'flag']]]]);
            }
            if (str_contains($url, '/privileges?')) {
                return $hydrator->hydrate([['hexUuid' => 'present', 'name' => 'Manage groups']]);
            }
            if (str_contains($url, '/group_flags?')) {
                return $hydrator->hydrate([['hexUuid' => 'flag', 'name' => 'has_home', 'title' => 'Has home directory']]);
            }

            return $hydrator->hydrate([]);
        });
        $handler = new BulkMutationHandler(
            new IdmReferenceProvider($client), new IdmGateway($client),
            new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class)),
            $this->createMock(AvatarRendererInterface::class),
        );
        $mutation = $this->mutation(['present', 'missing'], ['flag']);
        $mutation->action = GroupMutation::REVOKE;
        $mutation->target = 'all';

        self::assertSame(1, $handler->updateGroups($mutation));
        self::assertContains(['DELETE', 'iserv/idm/api/v1/groups/group-id/privileges/present'], $requests);
        self::assertContains(['DELETE', 'iserv/idm/api/v1/groups/group-id/group_flags/flag'], $requests);
        self::assertSame(['Revoke privilege: Manage groups', 'Revoke privilege: missing', 'Revoke group flag: Has home directory – has_home'], $handler->changes($mutation));
        self::assertSame(['Removed 2 privileges from one group.', 'Removed one group flag from one group.'], $handler->resultMessages($mutation, 1));
    }


    public function testOwnerChangesUseTheResolvedUserName(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->method('performRequest')->willReturnCallback(static function (string $_method, string $url, HydratorInterface $hydrator): array {
            if (str_contains($url, '/users?')) {
                return $hydrator->hydrate([['hexUuid' => '9b8241a1-5f7d-4d42-a8c6-2d2d6605ef68', 'user' => 'ada', 'firstname' => 'Ada', 'lastname' => 'Admin']]);
            }

            return $hydrator->hydrate([]);
        });
        $handler = new BulkMutationHandler(
            new IdmReferenceProvider($client), new IdmGateway($client),
            new GodModeUserLookup($client, $this->createMock(AvatarRendererInterface::class)),
            $this->createMock(AvatarRendererInterface::class),
        );
        $mutation = new OwnerMutation();
        $mutation->owner = new AutocompleteTagsData('user:9b8241a1-5f7d-4d42-a8c6-2d2d6605ef68', '9b8241a1-5f7d-4d42-a8c6-2d2d6605ef68', 'user');

        self::assertSame(['Set owner: Ada Admin'], $handler->changes($mutation));
        self::assertSame(['Set owner of 2 groups to Ada Admin.'], $handler->resultMessages($mutation, 2));
    }


    public function testInvalidRegexTargetIsRejectedBeforeIdmLookup(): void
    {
        $handler = new BulkMutationHandler(
            new IdmReferenceProvider($this->createMock(IdmClientInterface::class)),
            new IdmGateway($this->createMock(IdmClientInterface::class)),
            new GodModeUserLookup($this->createMock(IdmClientInterface::class), $this->createMock(AvatarRendererInterface::class)),
            $this->createMock(AvatarRendererInterface::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        $handler->groupsForTarget('matches', '(');
    }


    public function testGroupPreviewSurvivesAvatarPlaceholderFailures(): void
    {
        $client = $this->createMock(IdmClientInterface::class);
        $client->method('performRequest')->willReturnCallback(static function (string $_method, string $_url, HydratorInterface $hydrator): array {
            return $hydrator->hydrate([['hexUuid' => 'group-id', 'group' => 'admins', 'name' => 'Admins', 'owner' => null, 'privileges' => [], 'flags' => []]]);
        });
        $avatars = $this->createMock(AvatarRendererInterface::class);
        $avatars->method('renderPlaceholder')->willThrowException(new \IServ\Library\Avatar\Renderer\Exception\AvatarRendererException('avatar unavailable'));
        $handler = new BulkMutationHandler(
            new IdmReferenceProvider($client), new IdmGateway($client),
            new GodModeUserLookup($client, $avatars), $avatars,
        );

        self::assertSame([['group' => 'admins', 'name' => 'Admins', 'avatarHtml' => null]], $handler->groupPreviewsForTarget('all', null));
        $removeOwner = new OwnerMutation();
        self::assertSame([], $handler->affectedGroups($removeOwner));
    }

    /** @param list<string> $privileges
     * @param list<string> $flags */
    private function mutation(array $privileges, array $flags): GroupMutation
    {
        $mutation = new GroupMutation();
        $mutation->action = GroupMutation::ASSIGN;
        $mutation->privileges = $privileges;
        $mutation->flags = $flags;

        return $mutation;
    }
}
