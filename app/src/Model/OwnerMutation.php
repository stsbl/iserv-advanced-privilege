<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Model;

use IServ\Bundle\Autocomplete\Form\Data\AutocompleteTagsData;

final class OwnerMutation extends TargetSelection
{
    public ?AutocompleteTagsData $owner = null;

    public function ownerUuid(): ?string
    {
        if (!$this->owner instanceof AutocompleteTagsData || 'user' !== $this->owner->getSource()) {
            return null;
        }

        return $this->owner->getId();
    }
}
