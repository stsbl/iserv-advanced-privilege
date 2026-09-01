<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Form;

use IServ\Bundle\Autocomplete\Domain\AutocompleteType;
use IServ\Bundle\Autocomplete\Form\Type\AutocompleteTagsType;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** @extends AbstractType<OwnerMutation> */
final class OwnerMutationType extends AbstractType
{
    use TargetSelectionFields;

    public function __construct(private readonly UrlGeneratorInterface $router)
    {
    }

    /** @psalm-suppress MixedArgumentTypeCoercion */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTargetSelection($builder);
        $builder
            ->add('owner', AutocompleteTagsType::class, [
                'label' => _('Owner'),
                'required' => false,
                'multiple' => false,
                'autocomplete_types' => AutocompleteType::USER,
                'tag_source' => $this->router->generate('advanced_privilege_owner_autocomplete'),
                'autocomplete_lookup_url' => $this->router->generate('advanced_privilege_owner_autocomplete'),
                'attr' => ['help_text' => _('To remove the owner from the targets, select no owner.')],
            ])
            ->add('submit', SubmitType::class, ['label' => _('Apply'), 'button_class' => 'success', 'icon' => 'fa-check'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OwnerMutation::class]);
    }
}
