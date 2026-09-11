<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Model\TargetSelection;
use Symfony\Component\Validator\Validation;

#[CoversClass(\Stsbl\IServ\AdvancedPrivilege\Model\TargetSelection::class)]
final class TargetSelectionTest extends TestCase
{
    public function testTargetsContainEverySupportedSelection(): void
    {
        self::assertSame(['all', 'starts-with', 'ends-with', 'contains', 'matches'], TargetSelection::targets());
    }

    public function testRegularExpressionEscapesItsDelimiter(): void
    {
        self::assertSame('~Admins\~Group~u', TargetSelection::regularExpression('Admins~Group'));
        self::assertNull(TargetSelection::regularExpressionError('^(?:Domain )?Admins$'));
    }

    public function testRegularExpressionReturnsCompilerError(): void
    {
        self::assertStringContainsString('preg_match()', (string) TargetSelection::regularExpressionError('('));
    }

    public function testValidationReportsInvalidSelectionAndMissingPattern(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $invalid = new TargetSelection();
        $invalid->target = 'unknown';
        self::assertStringContainsString('The target choice is not valid.', (string) $validator->validate($invalid));

        $missingPattern = new TargetSelection();
        $missingPattern->target = TargetSelection::CONTAINS;
        self::assertStringContainsString('Pattern should not be empty.', (string) $validator->validate($missingPattern));
    }


    public function testValidationAcceptsAllAndReportsInvalidRegularExpressions(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $all = new TargetSelection();
        $all->target = TargetSelection::ALL;
        self::assertCount(0, $validator->validate($all));

        $invalidRegex = new TargetSelection();
        $invalidRegex->target = TargetSelection::MATCHES;
        $invalidRegex->pattern = '(';
        self::assertStringContainsString('Invalid regular expression:', (string) $validator->validate($invalidRegex));
    }

}
