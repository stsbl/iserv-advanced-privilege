<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Model;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;

#[CoversClass(OwnerMutation::class)]
final class OwnerMutationTest extends TestCase
{
    public function testOwnerUuidRequiresAUserAutocompleteValue(): void
    {
        $mutation = new OwnerMutation();
        self::assertNull($mutation->ownerUuid());
        $mutation->owner = new AutocompleteTagsData('group:id', 'id', 'group');
        self::assertNull($mutation->ownerUuid());
        $mutation->owner = new AutocompleteTagsData('user:id', 'id', 'user');
        self::assertSame('id', $mutation->ownerUuid());
    }
}
