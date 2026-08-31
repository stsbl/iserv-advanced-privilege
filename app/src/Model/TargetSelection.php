<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class TargetSelection
{
    public const ALL = 'all';
    public const STARTS_WITH = 'starts-with';
    public const ENDS_WITH = 'ends-with';
    public const CONTAINS = 'contains';
    public const MATCHES = 'matches';

    public ?string $target = null;
    public ?string $pattern = null;

    /** @return list<string> */
    public static function targets(): array
    {
        return [self::ALL, self::STARTS_WITH, self::ENDS_WITH, self::CONTAINS, self::MATCHES];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!in_array($this->target, self::targets(), true)) {
            $context->buildViolation(_('The target choice is not valid.'))->atPath('target')->addViolation();

            return;
        }
        if (self::ALL === $this->target) {
            return;
        }
        if (null === $this->pattern || '' === trim($this->pattern)) {
            $context->buildViolation(_('Pattern should not be empty.'))->atPath('pattern')->addViolation();

            return;
        }
        if (self::MATCHES === $this->target && false === @preg_match('/' . $this->pattern . '/', 'group')) {
            $context->buildViolation(_('Invalid regular expression.'))->atPath('pattern')->addViolation();
        }
    }
}
