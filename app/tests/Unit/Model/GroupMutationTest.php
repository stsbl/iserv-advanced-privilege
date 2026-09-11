<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Symfony\Component\Validator\Validation;

#[CoversClass(GroupMutation::class)]
final class GroupMutationTest extends TestCase
{
    public function testValidationRequiresValidActionAndAReference(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $mutation = new GroupMutation();
        self::assertStringContainsString('The action is not valid.', (string) $validator->validate($mutation));
        self::assertStringContainsString('Please select at least one privilege or group flag.', (string) $validator->validate($mutation));

        $mutation->target = 'all';
        $mutation->action = GroupMutation::REVOKE;
        $mutation->flags = ['flag-id'];
        self::assertCount(0, $validator->validate($mutation));
    }
}
