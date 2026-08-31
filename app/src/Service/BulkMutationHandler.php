<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Service;

use Stsbl\IServ\AdvancedPrivilege\Idm\IdmGateway;
use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\TargetSelection;

final readonly class BulkMutationHandler
{
    public function __construct(private IdmReferenceProvider $references, private IdmGateway $gateway)
    {
    }

    public function updateGroups(GroupMutation $mutation): int
    {
        $groups = $this->targets($mutation, false);
        foreach ($groups as $group) {
            if (GroupMutation::ASSIGN === $mutation->action) {
                $this->gateway->addPrivileges($group['uuid'], $mutation->privileges);
                $this->gateway->addFlags($group['uuid'], $mutation->flags);
            } else {
                foreach ($mutation->privileges as $uuid) {
                    $this->gateway->removePrivilege($group['uuid'], $uuid);
                }
                foreach ($mutation->flags as $uuid) {
                    $this->gateway->removeFlag($group['uuid'], $uuid);
                }
            }
        }

        return count($groups);
    }

    public function updateOwner(OwnerMutation $mutation): int
    {
        $groups = $this->targets($mutation, null === $mutation->ownerUuid());
        foreach ($groups as $group) {
            $this->gateway->setOwner($group['uuid'], $mutation->ownerUuid());
        }

        return count($groups);
    }

    /** @return list<array{uuid: string, name: string, owner: ?string}> */
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
                TargetSelection::MATCHES => 1 === preg_match('/' . (string) $selection->pattern . '/', $group['name']),
                default => false,
            };
        }));
    }
}
