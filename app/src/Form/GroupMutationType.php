<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Form;

use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Stsbl\IServ\AdvancedPrivilege\Service\IdmReferenceProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<GroupMutation> */
final class GroupMutationType extends AbstractType
{
    use TargetSelectionFields;

    public function __construct(private readonly IdmReferenceProvider $references)
    {
    }

    /** @psalm-suppress MixedArgumentTypeCoercion */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTargetSelection($builder);
        $builder
            ->add('privileges', ChoiceType::class, ['label' => _('Privileges'), 'choices' => array_flip($this->references->privileges()), 'multiple' => true, 'required' => false])
            ->add('flags', ChoiceType::class, ['label' => _('Group flags'), 'choices' => array_flip($this->references->flags()), 'multiple' => true, 'required' => false])
            ->add('action', HiddenType::class, ['data' => $options['mutation_action']])
            ->add('submit', SubmitType::class, ['label' => _('Apply')])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => GroupMutation::class, 'mutation_action' => null]);
        $resolver->setAllowedValues('mutation_action', [GroupMutation::ASSIGN, GroupMutation::REVOKE]);
    }
}
