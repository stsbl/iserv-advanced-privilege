<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class GroupMutation extends TargetSelection
{
    public const ASSIGN = 'assign';
    public const REVOKE = 'revoke';

    public ?string $action = null;
    /** @var list<string> */
    public array $privileges = [];
    /** @var list<string> */
    public array $flags = [];

    /** @psalm-suppress PossiblyUnusedMethod */
    #[Assert\Callback]
    public function validateMutation(ExecutionContextInterface $context): void
    {
        if (!in_array($this->action, [self::ASSIGN, self::REVOKE], true)) {
            $context->buildViolation(_('The action is not valid.'))->atPath('action')->addViolation();
        }
        if ([] === $this->privileges && [] === $this->flags) {
            $context->buildViolation(_('Please select at least one privilege or group flag.'))->addViolation();
        }
    }
}
