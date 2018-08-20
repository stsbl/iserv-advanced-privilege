<?php declare(strict_types = 1);
// src/Stsbl/AdvancedPrivilegeBundle/Form/Type/TargetChoiceTrait.php
namespace Stsbl\AdvancedPrivilegeBundle\Form\Type;

use Stsbl\AdvancedPrivilegeBundle\Model\AbstractTargetChoice;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/*
 * The MIT License
 *
 * Copyright 2018 Felix Jacobi.
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
trait TargetChoiceTrait
{
    /**
     * Adds target choice to supplied builder
     *
     * @param FormBuilderInterface $builder
     */
    protected function addTargetChoice(FormBuilderInterface $builder)
    {
        $builder
            ->add('target', ChoiceType::class, [
                'choices' => [
                    _('All groups') => AbstractTargetChoice::TARGET_ALL,
                    _('Groups whose name starting with ...') => AbstractTargetChoice::TARGET_STARTING_WITH,
                    _('Groups whose name ending with ...') => AbstractTargetChoice::TARGET_ENDING_WITH,
                    _('Groups whose name contains ...') => AbstractTargetChoice::TARGET_CONTAINS,
                    _('Groups whose name match with the following regular expression ...') =>
                        AbstractTargetChoice::TARGET_MATCHES,
                ],
                'label' => _('Select target'),
                'expanded' => true,
            ])
            ->add('pattern', TextType::class, [
                'required' => false, // handled by js on client side
                'label' => false,
                'attr' => [
                    'placeholder' => _('Enter a pattern...')
                ]
            ])
        ;
    }
}
