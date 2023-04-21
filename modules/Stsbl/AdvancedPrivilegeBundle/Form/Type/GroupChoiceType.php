<?php

declare(strict_types=1);

namespace Stsbl\AdvancedPrivilegeBundle\Form\Type;

use IServ\Bundle\Form\Form\Type\GettextEntityType;
use IServ\CoreBundle\Entity\GroupFlag;
use IServ\CoreBundle\Entity\Privilege;
use Stsbl\AdvancedPrivilegeBundle\Model\GroupChoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class GroupChoiceType extends AbstractType
{
    use TargetChoiceTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTargetChoice($builder);

        $builder
            ->add('privileges', GettextEntityType::class, [
                'label' => _('Privileges'),
                'class' => Privilege::class,
                'select2-icon' => 'legacy-keys',
                'select2-style' => 'stack',
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
                'choice_label' => 'fullTitle',
                'order_by' => ['module', 'title'],
            ])
            ->add('flags', GettextEntityType::class, [
                'label' => _('Group flags'),
                'class' => GroupFlag::class,
                'select2-icon' => 'fugue-tag-label',
                'select2-style' => 'stack',
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
                'choice_label' => 'title',
                'order_by' => ['title'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Apply'),
                'buttonClass' => 'btn-success has-spinner',
                'icon' => 'ok',
            ])
            ->add('action', HiddenType::class, [
                'data' => $options['action_type'],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['action_type' => null, 'data_class' => GroupChoice::class])
            ->setAllowedTypes('action_type', 'string')
            ->setAllowedValues('action_type', GroupChoice::getValidActions())
        ;
    }
}
