<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Form;

use Stsbl\IServ\AdvancedPrivilege\Model\TargetSelection;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

trait TargetSelectionFields
{
    /**
     * @template TData of TargetSelection
     * @param FormBuilderInterface<TData|null> $builder
     */
    private function addTargetSelection(FormBuilderInterface $builder): void
    {
        $builder
            ->add('target', ChoiceType::class, ['label' => _('Select target'), 'expanded' => true, 'choices' => [
                _('All groups') => TargetSelection::ALL,
                _('Groups whose name starts with ...') => TargetSelection::STARTS_WITH,
                _('Groups whose name ends with ...') => TargetSelection::ENDS_WITH,
                _('Groups whose name contains ...') => TargetSelection::CONTAINS,
                _('Groups whose name matches the following regular expression ...') => TargetSelection::MATCHES,
            ]])
            ->add('pattern', TextType::class, ['required' => false, 'label' => false, 'attr' => ['placeholder' => _('Enter a pattern...')]])
        ;
    }
}
