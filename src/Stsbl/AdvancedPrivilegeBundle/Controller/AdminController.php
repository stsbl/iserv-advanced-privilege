<?php
// src/Stsbl/AdvancedPrivilegeBundle/Controller/AdminController.php
namespace Stsbl\AdvancedPrivilegeBundle\Controller;

use IServ\CoreBundle\Controller\PageController;
use IServ\CoreBundle\Form\Type\GettextEntityType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

/*
 * The MIT License
 *
 * Copyright 2017 felix.
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
 * Admin controller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @Route("admin/privileges")
 */
class AdminController extends PageController
{
    /**
     * Get multiple assign form
     * 
     * @return Form
     */
    private function getForm()
    {
        $builder = $this->createFormBuilder();
        
        $builder
            ->add('target', ChoiceType::class, [
                'choices' => [
                    _('All groups') => 'all',
                    _('Groups whose name starting with ...') => 'starting-with',
                    _('Groups whose name ending with ...') => 'ending-with',
                    _('Groups whose name contains ...') => 'contains',
                    _('Groups whose name match with the following regular expression ...') => 'matches'
                ],
                'label' => _('Select target'),
                'expanded' => true,
                'choices_as_values' => true,
                'constraints' => new NotBlank(['message' => _('Please select a target.')])
            ])
            ->add('pattern', TextType::class, [
                'required' => false, // handle by js on client side
                'label' => false,
                'attr' => [
                    'placeholder' => _('Enter a pattern...')
                ]
            ])
            ->add('privileges', GettextEntityType::class, [
                'label' => _('Privileges'),
                'class' => 'IServCoreBundle:Privilege',
                'select2-icon' => 'legacy-keys',
                'select2-style' => 'stack',
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
                'choice_label' => 'fullTitle',
                'order_by' => array('module', 'title'),
            ])
            ->add('flags', GettextEntityType::class, [
                'label' => _('Group flags'),
                'class' => 'IServCoreBundle:GroupFlag',
                'select2-icon' => 'fugue-tag-label',
                'select2-style' => 'stack',
                'multiple' => true,
                'required' => false,
                'by_reference' => false,
                'choice_label' => 'title',
                'order_by' => array('title'),
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Apply'),
                'buttonClass' => 'btn-success',
                'icon' => 'ok'
            ])
        ;
        
        return $builder->getForm();
    }
    
    /**
     * Sends a flash message for empty pattern.
     */
    private function sendEmptyPatternMessage()
    {
        $this->get('iserv.flash')->error(_('Pattern should not be empty.'));
    }
    
    /**
     * Sends a flash message for empty pattern.
     */
    private function sendNoGroupsMessage()
    {
        $this->get('iserv.flash')->alert(_('No matching groups found.'));
    }
    
    /**
     * index action
     * 
     * @param Request $request
     * @return array
     * @Route("/advanced", name="admin_adv_priv")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $form = $this->getForm();
        $form->handleRequest($request);
        
        if ($form->isValid() && $form->isSubmitted()) {
            /* @var $groupManager \IServ\CoreBundle\Service\GroupManager */
            $groupManager = $this->get('iserv.group_manager');
            $data = $form->getData();
            $flags = $data['flags']->toArray();
            $privileges = $data['privileges']->toArray();
           
            /* @var $group \IServ\CoreBundle\Entity\Group */
            if ($data['target'] == 'all') {
                $groups = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->findAll();
            } else if ($data['target'] == 'ending-with') {
                if (empty($data['pattern'])) {
                    $this->sendEmptyPatternMessage();
                    goto render;
                }
                
                try {
                    /* @var $qb \Doctrine\ORM\QueryBuilder */
                    $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
                    $qb
                        ->select('g')
                        ->from('IServCoreBundle:Group', 'g')
                        ->where('g.name LIKE :query')
                        ->setParameter('query', '%'.$data['pattern']);
                    ;
                    
                    $groups = $qb->getQuery()->getResult();
                } catch (Doctrine\ORM\NoResultException $e) {
                    // Just ignore no results \o/
                }
                
            } else if ($data['target'] == 'starting-with') {
                if (empty($data['pattern'])) {
                    $this->sendEmptyPatternMessage();
                    goto render;
                }
                
                try {
                    /* @var $qb \Doctrine\ORM\QueryBuilder */
                    $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
                    $qb
                        ->select('g')
                        ->from('IServCoreBundle:Group', 'g')
                        ->where('g.name LIKE :query')
                        ->setParameter('query', $data['pattern'].'%');
                    ;
                    
                    $groups = $qb->getQuery()->getResult();
                } catch (Doctrine\ORM\NoResultException $e) {
                    // Just ignore no results \o/
                }

            } else if ($data['target'] == 'contains') {
                if (empty($data['pattern'])) {
                    $this->sendEmptyPatternMessage();
                    goto render;
                }
                
                try {
                    /* @var $qb \Doctrine\ORM\QueryBuilder */
                    $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
                    $qb
                        ->select('g')
                        ->from('IServCoreBundle:Group', 'g')
                        ->where('g.name LIKE :query')
                        ->setParameter('query', '%'.$data['pattern'].'%');
                    ;
                    
                    $groups = $qb->getQuery()->getResult();
                } catch (Doctrine\ORM\NoResultException $e) {
                    // Just ignore no results \o/
                }

            } else if ($data['target'] == 'matches') {
                $allGroups = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->findAll();
                $groups = [];
               
                foreach ($allGroups as $g) {
                    if (preg_match(sprintf('/%s/', $data['pattern']), $g->getName())) {
                        $groups[] = $g;
                    }
                }
               
            } else {
               throw new \InvalidArgumentException(sprintf('Not an implemented target: %s.', $data['target']));
            }
            
            if (count($groups) < 1) {
                $this->sendNoGroupsMessage();
                goto render;                   
            }
               
            /* @var $flag \IServ\CoreBundle\Entity\GroupFlag */
            /* @var $privilege \IServ\CoreBundle\Entity\Privilege */
            /* @var $group \IServ\CoreBundle\Entity\Group */
            foreach ($groups as $group) {
                foreach ($flags as $flag) {
                    $group->addFlag($flag);
                }
                
                foreach ($privileges as $privilege) {
                    $group->addPrivilege($privilege);
                }
                
                $groupManager->update($group);
            }
            
            $messages = $groupManager->getMessages();
                
            if (count($messages) > 0) {
                $success = [];
                $error = [];
                
                foreach ($messages as $message) {
                    switch ($message['type']) {
                        case 'success':
                            $success[] = $message['message'];
                            break;
                        case 'error':
                            $error[] = $message['message'];
                            break;
                    }
                }
                    
                if (count($success) > 0) {
                    $this->get('iserv.flash')->success(implode("\n", $success)); 
                }
                    
                if (count($error) > 0) {
                   $this->get('iserv.flash')->error(implode("\n", $error)); 
                }
                
                if (count($groups) > 0 && count($flags) > 0) {
                    if (count($groups) == 1 && count($flags) == 1) {
                        $message = _('Added one group flag to one group.');
                        $log = 'Ein Gruppenmerkmal zu einer Gruppe hinzugefügt';
                    } else if (count($groups) == 1 && count($flags) > 1) {
                        $message = sprintf(_('Added %s group flags to one group.'), count($flags));
                        $log = sprintf('%s Gruppenmerkmale zu einer Gruppe hinzugefügt', count($flags));
                    } else if (count($groups) > 1 && count($flags) == 1) {
                        $message = sprintf(_('Added one group flag to %s groups.'), count($groups));
                        $log = sprintf('Ein Gruppenmerkmal zu %s Gruppen hinzugefügt', count($groups));
                    } else {
                        $message = sprintf(_('Added %s group flags to %s groups.'), count($flags), count($groups));
                        $log = sprintf('%s Gruppenmerkmale zu %s Gruppen hinzugefügt', count($flags), count($groups));
                    }
                    
                    $this->get('iserv.flash')->info($message);
                    $this->get('iserv.logger')->write($log);
                }

                if (count($groups) > 0 && count($privileges) > 0) {
                    if (count($groups) == 1 && count($privileges) == 1) {
                        $message = _('Added one privilege to one group.');
                        $log = 'Ein Recht zu einer Gruppe hinzugefügt';
                    } else if (count($groups) == 1 && count($privileges) > 1) {
                        $message = sprintf(_('Added %s privileges to one group.'), count($privileges));
                        $log = sprintf('%s Rechte zu einer Gruppe hinzugefügt', count($privileges));
                    } else if (count($groups) > 1 && count($privileges) == 1) {
                        $message = sprintf(_('Added one privilege to %s groups.'), count($groups));
                        $log = sprintf('Ein Recht zu %s Gruppen hinzugefügt', count($groups));
                    } else {
                        $message = sprintf(_('Added %s privileges to %s groups.'), count($privileges), count($groups));
                        $log = sprintf('%s Rechte zu %s Gruppen hinzugefügt', count($privileges), count($groups));
                    }
                    
                    $this->get('iserv.flash')->info($message);
                    $this->get('iserv.logger')->write($log);
                }
            }
        }
        
        // jump hook
        render:
        
        // track path
        $this->addBreadcrumb(_('Privileges'), $this->generateUrl('admin_privilege_index'));
        $this->addBreadcrumb(_('Advanced privilege assignment'), $this->generateUrl('admin_adv_priv'));
        
        $view = $form->createView();
        
        //$securityHandler = $this->get('iserv.security_handler');
        //die($securityHandler->getSessionPassword());
        
        return ['multiple_assign_form' => $view];
    }
}
