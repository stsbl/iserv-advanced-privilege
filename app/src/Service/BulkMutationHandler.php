<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Service;

use Stsbl\IServ\AdvancedPrivilege\Idm\IdmGateway;
use Stsbl\IServ\AdvancedPrivilege\Autocomplete\GodModeUserLookup;
use IServ\Library\Avatar\AvatarSize;
use IServ\Library\Avatar\Renderer\AvatarRendererInterface;
use IServ\Library\Avatar\Renderer\AvatarRenderStyle;
use IServ\Library\Avatar\Renderer\Exception\AvatarRendererException;
use IServ\Library\Avatar\UrlGenerator\AvatarPlaceholderStyle;
use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\TargetSelection;

final readonly class BulkMutationHandler
{
    public function __construct(
        private IdmReferenceProvider $references,
        private IdmGateway $gateway,
        private GodModeUserLookup $users,
        private AvatarRendererInterface $avatars,
    ) {
    }

    public function updateGroups(GroupMutation $mutation): int
    {
        $groups = $this->affectedGroups($mutation);
        foreach ($groups as $group) {
            if (GroupMutation::ASSIGN === $mutation->action) {
                $this->gateway->addPrivileges($group['uuid'], $this->without($mutation->privileges, $group['privileges']));
                $this->gateway->addFlags($group['uuid'], $this->without($mutation->flags, $group['flags']));
            } else {
                foreach ($this->present($mutation->privileges, $group['privileges']) as $uuid) {
                    $this->gateway->removePrivilege($group['uuid'], $uuid);
                }
                foreach ($this->present($mutation->flags, $group['flags']) as $uuid) {
                    $this->gateway->removeFlag($group['uuid'], $uuid);
                }
            }
        }

        return count($groups);
    }

    public function updateOwner(OwnerMutation $mutation): int
    {
        $groups = $this->affectedGroups($mutation);
        foreach ($groups as $group) {
            $this->gateway->setOwner($group['uuid'], $mutation->ownerUuid());
        }

        return count($groups);
    }

    /** @return list<array{uuid: string, group: string, name: string, owner: ?string, privileges: list<string>, flags: list<string>}> */
    public function affectedGroups(GroupMutation|OwnerMutation $mutation): array
    {
        return $this->sortedTargets($this->targets($mutation, $mutation instanceof OwnerMutation && null === $mutation->ownerUuid()));
    }

    /** @return list<array{uuid: string, group: string, name: string, owner: ?string, privileges: list<string>, flags: list<string>}> */
    public function groupsForTarget(string $target, ?string $pattern): array
    {
        if (!in_array($target, TargetSelection::targets(), true)) {
            return [];
        }

        if (TargetSelection::MATCHES === $target && (null === $pattern || null !== $error = TargetSelection::regularExpressionError($pattern))) {
            throw new \InvalidArgumentException(__('Invalid regular expression: %s', $error ?? preg_last_error_msg()));
        }

        $selection = new TargetSelection();
        $selection->target = $target;
        $selection->pattern = $pattern;

        return $this->sortedTargets($this->targets($selection, false));
    }

    /** @return list<array{group: string, name: string, avatarHtml: string|null}> */
    public function groupPreviewsForTarget(string $target, ?string $pattern): array
    {
        return array_map(function (array $group): array {
            $avatar = null;
            if ('' !== $group['name']) {
                try {
                    $avatar = $this->avatars->renderPlaceholder(
                        $group['name'],
                        AvatarSize::default(),
                        AvatarRenderStyle::ROUNDED,
                        AvatarPlaceholderStyle::GROUP,
                    );
                } catch (AvatarRendererException) {
                    $avatar = null;
                }
            }

            return ['group' => $group['group'], 'name' => $group['name'], 'avatarHtml' => $avatar];
        }, $this->groupsForTarget($target, $pattern));
    }

    /** @param list<array{uuid: string, group: string, name: string, owner: ?string, privileges: list<string>, flags: list<string>}> $groups
     * @return list<array{uuid: string, group: string, name: string, owner: ?string, privileges: list<string>, flags: list<string>}> */
    private function sortedTargets(array $groups): array
    {
        usort($groups, static fn(array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));

        return $groups;
    }

    /** @return list<string> */
    public function changes(GroupMutation|OwnerMutation $mutation): array
    {
        if ($mutation instanceof OwnerMutation) {
            if (null === $uuid = $mutation->ownerUuid()) {
                return [_('Remove owner')];
            }

            return [__('Set owner: %s', $this->users->label($uuid) ?? $uuid)];
        }

        $changes = [];
        $privileges = $this->references->privileges();
        $flags = $this->references->flags();
        foreach ($mutation->privileges as $uuid) {
            $changes[] = __(GroupMutation::ASSIGN === $mutation->action ? 'Assign privilege: %s' : 'Revoke privilege: %s', $privileges[$uuid] ?? $uuid);
        }
        foreach ($mutation->flags as $uuid) {
            $changes[] = __(GroupMutation::ASSIGN === $mutation->action ? 'Assign group flag: %s' : 'Revoke group flag: %s', $flags[$uuid] ?? $uuid);
        }

        return $changes;
    }

    /** @return list<string> */
    public function resultMessages(GroupMutation|OwnerMutation $mutation, int $groups): array
    {
        if (0 === $groups) {
            return [_('No matching groups found.')];
        }

        if ($mutation instanceof OwnerMutation) {
            if (null === $uuid = $mutation->ownerUuid()) {
                return [__('Removed owner of %s.', $this->groupCount($groups))];
            }

            return [__('Set owner of %s to %s.', $this->groupCount($groups), $this->users->label($uuid) ?? $uuid)];
        }

        $messages = [];
        if ([] !== $mutation->privileges) {
            $messages[] = GroupMutation::ASSIGN === $mutation->action
                ? $this->assignmentMessage(true, false, count($mutation->privileges), $groups)
                : $this->assignmentMessage(false, false, count($mutation->privileges), $groups);
        }
        if ([] !== $mutation->flags) {
            $messages[] = GroupMutation::ASSIGN === $mutation->action
                ? $this->assignmentMessage(true, true, count($mutation->flags), $groups)
                : $this->assignmentMessage(false, true, count($mutation->flags), $groups);
        }

        return $messages;
    }

    /** @return list<array{uuid: string, group: string, name: string, owner: ?string, privileges: list<string>, flags: list<string>}> */
    private function targets(TargetSelection $selection, bool $onlyWithOwner): array
    {
        return array_values(array_filter($this->references->groups(), static function (array $group) use ($selection, $onlyWithOwner): bool {
            if ($onlyWithOwner && null === $group['owner']) {
                return false;
            }
            return match ($selection->target) {
                TargetSelection::ALL => true,
                TargetSelection::STARTS_WITH => str_starts_with($group['name'], (string) $selection->pattern),
                TargetSelection::ENDS_WITH => str_ends_with($group['name'], (string) $selection->pattern),
                TargetSelection::CONTAINS => str_contains($group['name'], (string) $selection->pattern),
                TargetSelection::MATCHES => 1 === preg_match(TargetSelection::regularExpression((string) $selection->pattern), $group['name']),
                default => false,
            };
        }));
    }

    /** @param list<string> $requested
     * @param list<string> $current
     * @return list<string> */
    private function without(array $requested, array $current): array
    {
        return array_values(array_diff($requested, $current));
    }

    /** @param list<string> $requested
     * @param list<string> $current
     * @return list<string> */
    private function present(array $requested, array $current): array
    {
        return array_values(array_intersect($requested, $current));
    }

    private function assignmentMessage(bool $assign, bool $flags, int $items, int $groups): string
    {
        $items = $flags
            ? __n('one group flag', '%d group flags', $items, $items)
            : __n('one privilege', '%d privileges', $items, $items);
        $groups = $this->groupCount($groups);

        return $assign
            ? __('Added %s to %s.', $items, $groups)
            : __('Removed %s from %s.', $items, $groups);
    }

    private function groupCount(int $groups): string
    {
        return __n('one group', '%d groups', $groups, $groups);
    }
}
