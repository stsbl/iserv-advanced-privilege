<?php
// src/Stsbl/AdvancedPrivilegeBundle/Controller/AdminController.php
namespace Stsbl\AdvancedPrivilegeBundle\Controller;

use IServ\CoreBundle\Controller\PageController;
use IServ\CoreBundle\Form\Type\GettextEntityType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
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
     * Get multiple assign/revoke form
     * 
     * @param string $action
     * @return Form
     */
    private function getForm($action)
    {
        $builder = $this->get('form.factory')->createNamedBuilder($action);
        
        $builder
            ->setAction($this->generateUrl('admin_adv_priv').sprintf('?action=%s', $action))
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
     * Sends a flash message for empty items (flags, privileges).
     */
    private function sendNoItemsMessage()
    {
        $this->get('iserv.flash')->alert(_('Select at least one privilege or group flag.'));
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
        $assignForm = $this->getForm('assign');
        $assignForm->handleRequest($request);
        $revokeForm = $this->getForm('revoke');
        $revokeForm->handleRequest($request);
        
        if ($request->query->has('action')) {
            $action = $request->query->get('action');
        } else {
            $action = null;
        }
        
        if ($action === 'assign') {
            $this->handleAssignForm($assignForm);
        } else if ($action === 'revoke') {
            $this->handleRevokeForm($revokeForm);
        }
        
        // track path
        $this->addBreadcrumb(_('Privileges'), $this->generateUrl('admin_privilege_index'));
        $this->addBreadcrumb(_('Advanced privilege assignment'), $this->generateUrl('admin_adv_priv'));
        
        $assignView = $assignForm->createView();
        $revokeView = $revokeForm->createView();
        
        //$securityHandler = $this->get('iserv.security_handler');
        //die($securityHandler->getSessionPassword());
        
        return ['multiple_assign_form' => $assignView, 'multiple_revoke_form' => $revokeView];
    }
    
    /**
     * Handles response from assignForm
     * 
     * @param Form $assignForm
     */
    private function handleAssignForm(Form $assignForm)
    {
        if ($assignForm->isValid() && $assignForm->isSubmitted()) {
            /* @var $groupManager \IServ\CoreBundle\Service\GroupManager */
            $groupManager = $this->get('iserv.group_manager');
            $data = $assignForm->getData();
            $flags = $data['flags']->toArray();
            $privileges = $data['privileges']->toArray();
            $groups = $this->findGroups($data['target'], $data['pattern']);
            
            if (count($groups) < 1) {
                $this->sendNoGroupsMessage();
                goto end;                   
            }
            
            if (count($privileges) < 1 && count($flags) < 1) {
                $this->sendNoItemsMessage();
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
                $this->sendMessages($messages);
                
            }
            
            $this->log($groups, $flags, $privileges, 'assign');
        }
        
        // jump hook
        end:
    }
    
    /**
     * Handles response from revokeForm
     * 
     * @param Form $assignForm
     */
    private function handleRevokeForm(Form $revokeForm)
    {
        if ($revokeForm->isValid() && $revokeForm->isSubmitted()) {
            /* @var $groupManager \IServ\CoreBundle\Service\GroupManager */
            $groupManager = $this->get('iserv.group_manager');
            $data = $revokeForm->getData();
            $flags = $data['flags']->toArray();
            $privileges = $data['privileges']->toArray();
            $groups = $this->findGroups($data['target'], $data['pattern']);
            
            if (count($groups) < 1) {
                $this->sendNoGroupsMessage();
                goto end;                   
            }
            
            if (count($privileges) < 1 && count($flags) < 1) {
                $this->sendNoItemsMessage();
            }
            
            /* @var $flag \IServ\CoreBundle\Entity\GroupFlag */
            /* @var $privilege \IServ\CoreBundle\Entity\Privilege */
            /* @var $group \IServ\CoreBundle\Entity\Group */
            foreach ($groups as $group) {
                foreach ($flags as $flag) {
                    $group->removeFlag($flag);
                }
                
                foreach ($privileges as $privilege) {
                    $group->removePrivilege($privilege);
                }
                
                $groupManager->update($group);
            }
            
            $messages = $groupManager->getMessages();
                
            if (count($messages) > 0) {
                $this->sendMessages($messages);
                
            }
            
            $this->log($groups, $flags, $privileges, 'revoke');
        }
        
        // jump hook
        end:
    }
    
    /**
     * Trys to find groups by given criteria
     * 
     * @param string target
     * @param string $pattern
     * @return array
     */
    private function findGroups($target, $pattern = null)
    {
        /* @var $group \IServ\CoreBundle\Entity\Group */
        if ($target == 'all') {
            $groups = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->findAll();
        } else if ($target == 'ending-with') {
            if (empty($pattern)) {
                $this->sendEmptyPatternMessage();
                goto empty_result;
            }
                
            try {
                /* @var $qb \Doctrine\ORM\QueryBuilder */
                $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
                $qb
                    ->select('g')
                    ->from('IServCoreBundle:Group', 'g')
                    ->where('g.name LIKE :query')
                    ->setParameter('query', '%'.$pattern);
                ;
                    
                $groups = $qb->getQuery()->getResult();
            } catch (Doctrine\ORM\NoResultException $e) {
                // Just ignore no results \o/
            }
                
        } else if ($target == 'starting-with') {
            if (empty($pattern)) {
                $this->sendEmptyPatternMessage();
                goto empty_result;
            }
                
            try {
                /* @var $qb \Doctrine\ORM\QueryBuilder */
                $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
                $qb
                    ->select('g')
                    ->from('IServCoreBundle:Group', 'g')
                    ->where('g.name LIKE :query')
                    ->setParameter('query', $pattern.'%');
                ;
                    
                $groups = $qb->getQuery()->getResult();
            } catch (Doctrine\ORM\NoResultException $e) {
                // Just ignore no results \o/
            }

        } else if ($target == 'contains') {
            if (empty($pattern)) {
                $this->sendEmptyPatternMessage();
                goto empty_result;
            }
            
            try {
                /* @var $qb \Doctrine\ORM\QueryBuilder */
                $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
                $qb
                    ->select('g')
                    ->from('IServCoreBundle:Group', 'g')
                    ->where('g.name LIKE :query')
                    ->setParameter('query', '%'.$pattern.'%');
                ;
                    
                $groups = $qb->getQuery()->getResult();
            } catch (Doctrine\ORM\NoResultException $e) {
                // Just ignore no results \o/
            }

        } else if ($target == 'matches') {
            $allGroups = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->findAll();
            $groups = [];

            foreach ($allGroups as $g) {
                if (preg_match(sprintf('/%s/', $pattern), $g->getName())) {
                    $groups[] = $g;
                }
            }
               
        } else {
            throw new \InvalidArgumentException(sprintf('Not an implemented target: %s.', $target));
        }
        
        return $groups;
        
        empty_result:
            return [];
            
    }
    
    /**
     * Send messages as flash sorted by category (error, sucess e.g)
     * 
     * @param array $messages
     */
    private function sendMessages(array $messages)
    {
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
    }
    
    /**
     * Log operations and sends finally a conclusion flash message
     * 
     * @param array $groups
     * @param array $flags
     * @param array $privileges
     * @param string $action
     */
    private function log(array $groups, array $flags, array $privileges, $action = 'assign')
    {
        if ($action !== 'assign' && $action !== 'revoke') {
            throw new \InvalidArgumentException(sprintf('action must be either "assign" or "revoke", "%s" given.', $action));
        }
        
        if ($action == 'assign') {
            $prefix = 'Added';
            $preposition = 'to';
            $logSuffix = 'hinzugefügt';
            $logPreposition = 'zu';
        } else {
            $prefix = 'Removed';
            $preposition = 'from';
            $logSuffix = 'entfernt';
            $logPreposition = 'von';
        }
               
        if (count($groups) > 0 && count($flags) > 0) {
            if (count($groups) == 1 && count($flags) == 1) {
                $message = _(sprintf('%s one group flag %s one group.', $prefix, $preposition));
                $log = sprintf('Ein Gruppenmerkmal %s einer Gruppe %s', $logPreposition, $logSuffix);
            } else if (count($groups) == 1 && count($flags) > 1) {
                $message = sprintf(_(sprintf('%s %%s group flags %s one group.', $prefix, $preposition)), count($flag));
                $log = sprintf('%s Gruppenmerkmale %s einer Gruppe %s', count($flags), $logPreposition, $logSuffix);
            } else if (count($groups) > 1 && count($flags) == 1) {
                $message = sprintf(_(sprintf('%s one group flag %s %%s groups.', $prefix, $preposition)), count($groups));
                $log = sprintf('Ein Gruppenmerkmal %s %s Gruppen %s', $logPreposition, count($groups), $logSuffix);
            } else {
                $message = sprintf(_(sptrinf('%s %%s group flags %s %%s groups.', $prefix, $preposition)), count($flags), count($groups));
                $log = sprintf('%s Gruppenmerkmale %s %s Gruppen %s', count($flags), $preposition, count($groups), $logSuffix);
            }
                    
            $this->get('iserv.flash')->info($message);
            $this->get('iserv.logger')->write($log);
        }

        if (count($groups) > 0 && count($privileges) > 0) {
            if (count($groups) == 1 && count($privileges) == 1) {
                $message = _(sprintf('%s one privilege %s one group.', $prefix, $preposition));
                $log = sprintf('Ein Recht %s einer Gruppe %s', $logPreposition, $logSuffix);
            } else if (count($groups) == 1 && count($privileges) > 1) {
                $message = sprintf(_(sprintf('%s %%s privileges %s one group.', $prefix, $preposition)), count($privileges));
                $log = sprintf('%s Rechte %s einer Gruppe %s', count($privileges), $logPreposition, $logSuffix);
            } else if (count($groups) > 1 && count($privileges) == 1) {
                $message = sprintf(_(sprintf('%s one privilege %s %%s groups.', $prefix, $preposition)), count($groups));
                $log = sprintf('Ein Recht %s %s Gruppen %s', $logPreposition, count($groups), $logSuffix);
            } else {
                $message = sprintf(_(sprintf('%s %%s privileges %s %%s groups.', $prefix, $preposition)), count($privileges), count($groups));
                $log = sprintf('%s Rechte %s %s Gruppen %s', count($privileges), $logPreposition, count($groups), $logSuffix);
            }
                    
            $this->get('iserv.flash')->info($message);
            $this->get('iserv.logger')->write($log);
        }
    }
}
