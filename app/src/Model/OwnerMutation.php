<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Model;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;

final class OwnerMutation extends TargetSelection
{
    /** @var list<AutocompleteTagsData> */
    public array $owner = [];

    public function ownerUuid(): ?string
    {
        $owner = $this->owner[0] ?? null;
        if (!$owner instanceof AutocompleteTagsData || 'user' !== $owner->getSource()) {
            return null;
        }

        return $owner->getId();
    }
}
