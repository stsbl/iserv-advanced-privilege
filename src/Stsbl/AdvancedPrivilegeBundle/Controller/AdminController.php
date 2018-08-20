<?php
// src/Stsbl/AdvancedPrivilegeBundle/Controller/AdminController.php
namespace Stsbl\AdvancedPrivilegeBundle\Controller;

use IServ\CoreBundle\Controller\AbstractPageController;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Service\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\AdvancedPrivilegeBundle\Form\Type\GroupChoiceType;
use Stsbl\AdvancedPrivilegeBundle\Form\Type\OwnerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
 * Admin controller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @Route("admin/privileges")
 */
class AdminController extends AbstractPageController
{
    /**
     * @var array
     */
    private $messages = ['msg' => []];

    /**
     * Get multiple assign/revoke form
     *
     * @param string $action
     * @return FormInterface
     */
    private function createGroupChoiceForm(string $action): FormInterface
    {
        return $this->get('form.factory')->createNamed(
            $action,
            GroupChoiceType::class,
            null,
            ['action' =>$this->generateUrl('admin_adv_priv'), 'action_type' => $action]
        );
    }
    
    /**
     * Get form for changing the owner of mutliple groups
     *
     * @return FormInterface
     */
    private function getOwnerForm(): FormInterface
    {
        return $this->get('form.factory')->createNamed(
            'owner',
            OwnerType::class,
            null,
            ['action' =>$this->generateUrl('admin_adv_priv')]
        );
    }
    
    /**
     * Handles submitted forms
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @Route("/advanced/send", name="admin_adv_priv_send", options={"expose": true}, methods={"POST"})
     */
    public function sendAction(Request $request)
    {
        $assignForm = $this->createGroupChoiceForm('assign');
        $assignForm->handleRequest($request);
        $revokeForm = $this->createGroupChoiceForm('revoke');
        $revokeForm->handleRequest($request);
        $ownerForm = $this->getOwnerForm();
        $ownerForm->handleRequest($request);

        if ($assignForm->isSubmitted()) {
            $this->handleAssignForm($assignForm);

            return new JsonResponse($this->messages);
        } elseif ($revokeForm->isSubmitted()) {
            $this->handleRevokeForm($revokeForm);

            return new JsonResponse($this->messages);
        } elseif ($ownerForm->isSubmitted()) {
            $this->handleOwnerForm($ownerForm);

            return new JsonResponse($this->messages);
        }
        
        throw new \RuntimeException('This statement should never be reached!');
    }

    /**
     * @Route("/advanced", name="admin_adv_priv")
     * @Template()
     *     *
     * @return array
     */
    public function indexAction()
    {
        $assignForm = $this->createGroupChoiceForm('assign');
        $revokeForm = $this->createGroupChoiceForm('revoke');
        $ownerForm = $this->getOwnerForm();
        
        // track path
        $this->addBreadcrumb(_('Privileges'), $this->generateUrl('admin_privilege_index'));
        $this->addBreadcrumb(_('Advanced privilege assignment'), $this->generateUrl('admin_adv_priv'));
        
        return [
            'multipleAssignForm' => $assignForm->createView(),
            'multipleRevokeForm' => $revokeForm->createView(),
            'multipleOwnerForm' => $ownerForm->createView(),
        ];
    }

    /**
     * Handles response from assign form
     *
     * @param FormInterface $assignForm
     * @throws \IServ\CrudBundle\Exception\DatabaseConstraintException
     * @throws \IServ\CrudBundle\Exception\ObjectManagerException
     */
    private function handleAssignForm(FormInterface $assignForm)
    {
        if ($assignForm->isValid()) {
            /* @var $groupManager \IServ\CoreBundle\Service\GroupManager */
            $groupManager = $this->get('iserv.group_manager');
            $data = $assignForm->getData();
            $flags = $data['flags']->toArray();
            $privileges = $data['privileges']->toArray();
            
            if (empty($data['pattern']) && $data['target'] !== 'all') {
                $this->addEmptyPatternMessage();
                
                goto end;
            }
            
            $groups = $this->findGroups($data['target'], $data['pattern']);
            
            if (count($groups) < 1) {
                $this->addMessage('alert', _('No matching groups found.'));
                
                goto end;
            }
            
            if (count($privileges) < 1 && count($flags) < 1) {
                $this->addMessage('alert', _('Select at least one privilege or group flag.'));
                
                goto end;
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
                $this->addGroupManagerMessages($messages);
            }
            
            $this->log($groups, $flags, $privileges, 'assign');
        } else {
            $errors = preg_replace('/^ERROR: /', '', (string)$assignForm->getErrors(true));
            
            $this->addMessage('error', $errors);
        }
        
        // jump hook
        end:
    }

    /**
     * Handles response from revoke form
     *
     * @param FormInterface $revokeForm
     * @throws \IServ\CrudBundle\Exception\DatabaseConstraintException
     * @throws \IServ\CrudBundle\Exception\ObjectManagerException
     */
    private function handleRevokeForm(FormInterface $revokeForm)
    {
        if ($revokeForm->isValid()) {
            /* @var $groupManager \IServ\CoreBundle\Service\GroupManager */
            $groupManager = $this->get('iserv.group_manager');
            $data = $revokeForm->getData();
            $flags = $data['flags']->toArray();
            $privileges = $data['privileges']->toArray();
            
            if (empty($data['pattern']) && $data['target'] !== 'all') {
                $this->addEmptyPatternMessage();
                
                goto end;
            }
            
            $groups = $this->findGroups($data['target'], $data['pattern']);
            
            if (count($groups) < 1) {
                $this->addMessage('alert', _('No matching groups found.'));
                
                goto end;
            }
            
            if (count($privileges) < 1 && count($flags) < 1) {
                $this->addMessage('alert', _('Select at least one privilege or group flag.'));
                
                goto end;
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
                $this->addGroupManagerMessages($messages);
            }
            
            $this->log($groups, $flags, $privileges, 'revoke');
        } else {
            $errors = preg_replace('/^ERROR: /', '', (string)$revokeForm->getErrors(true));
            
            $this->addMessage('error', $errors);
        }
        
        // jump hook
        end:
    }

    /**
     * Handles response from owner form
     *
     * @param FormInterface $ownerForm
     * @throws \IServ\CrudBundle\Exception\DatabaseConstraintException
     * @throws \IServ\CrudBundle\Exception\ObjectManagerException
     */
    private function handleOwnerForm(FormInterface $ownerForm)
    {
        if ($ownerForm->isValid()) {
            /* @var $groupManager \IServ\CoreBundle\Service\GroupManager */
            $groupManager = $this->get('iserv.group_manager');
            $data = $ownerForm->getData();
            $owner = $data['owner'];
            
            if (empty($data['pattern']) && $data['target'] !== 'all') {
                $this->addEmptyPatternMessage();
                
                goto end;
            }

            $skipOwner = $owner === null ? true : false;
            $groups = $this->findGroups($data['target'], $data['pattern'], $skipOwner);
            
            if (count($groups) < 1) {
                $this->addMessage('alert', _('No matching groups found.'));
                
                goto end;
            }

            $i = 0;
            /* @var $group \IServ\CoreBundle\Entity\Group */
            foreach ($groups as $group) {
                $group->setOwner($owner);
                $groupManager->update($group);

                $i++;
            }
            
            $messages = $groupManager->getMessages();
                
            if (count($messages) > 0) {
                $this->addGroupManagerMessages($messages);
                
            }

            $this->logOwner($groups, $owner, $i);
        } else {
            $errors = preg_replace('/^ERROR: /', '', (string)$ownerForm->getErrors(true));
            
            $this->addMessage('error', $errors);
        }
        
        // jump hook
        end:
    }
    
    /**
     * Tries to find groups by given criteria
     * 
     * @param string target
     * @param string $pattern
     * @param boolean $skipNoOwner
     * @return array<\IServ\CoreBundle\Entity\Group>
     */
    private function findGroups($target, $pattern = null, $skipNoOwner = false)
    {
        /* @var $group \IServ\CoreBundle\Entity\Group */
        if ($target == 'all') {
            $groups = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->findAll();
        } elseif ($target == 'ending-with') {
            /* @var $qb \Doctrine\ORM\QueryBuilder */
            $qb = $this->getDoctrine()
                ->getRepository('IServCoreBundle:Group')
                ->createQueryBuilder(self::class)
            ;

            $qb
                ->select('g')
                ->from('IServCoreBundle:Group', 'g')
                ->where($qb->expr()->like('g.name', ':query'))
                ->setParameter('query', '%'.$pattern)
            ;

            if ($skipNoOwner) {
                $qb->andWhere($qb->expr()->isNotNull('g.owner'));
            }

            $groups = $qb->getQuery()->getResult();
        } elseif ($target == 'starting-with') {
            /* @var $qb \Doctrine\ORM\QueryBuilder */
            $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
            $qb
                ->select('g')
                ->from('IServCoreBundle:Group', 'g')
                ->where($qb->expr()->like('g.name', ':query'))
                ->setParameter('query', $pattern.'%')
            ;

            if ($skipNoOwner) {
                $qb->andWhere($qb->expr()->isNotNull('g.owner'));
            }

            $groups = $qb->getQuery()->getResult();
        } elseif ($target == 'contains') {
            /* @var $qb \Doctrine\ORM\QueryBuilder */
            $qb = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->createQueryBuilder(self::class);
            $qb
                ->select('g')
                ->from('IServCoreBundle:Group', 'g')
                ->where('g.name LIKE :query')
                ->setParameter('query', '%'.$pattern.'%')
            ;

            $groups = $qb->getQuery()->getResult();
        } elseif ($target == 'matches') {
            $allGroups = $this->getDoctrine()->getRepository('IServCoreBundle:Group')->findAll();
            $groups = [];

            foreach ($allGroups as $g) {
                if ($skipNoOwner && $g->getOwner() === null) {
                    continue;
                }

                if (preg_match(sprintf('/%s/', $pattern), $g->getName())) {
                    $groups[] = $g;
                }
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Not an implemented target: %s.', $target));
        }
        
        return $groups;
    }
    
    /**
     * Add messages by the group manager to ouput array sorted by category (error, sucess e.g)
     *
     * @param array $messages
     * @return array
     */
    private function addGroupManagerMessages(array $messages)
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
            $this->addMessage('success', nl2br(implode("\n", $success)));
        }
                    
        if (count($error) > 0) {
            $this->addMessage('error', nl2br(implode("\n", $error)));
        }
        
        return $this->messages;
    }
    
    /**
     * Adds a message to result message collection
     *
     * @param string $type
     * @param string $message
     * @return array
     */
    private function addMessage($type, $message)
    {
        $this->messages['msg'][] = [
            'type' => $type,
            'message' => $message
        ];
        
        return $this->messages;
    }
    
    /**
     * Add messages for empty pattern
     *
     * @return array
     */
    private function addEmptyPatternMessage()
    {
        return $this->addMessage('alert', _('Pattern should not be empty.'));
    }
    
    /**
     * Log owner operations and add conclusion to response array.
     *
     * @param array $groups
     * @param User $owner
     * @param integer $count
     * @return array
     */
    private function logOwner(array $groups, User $owner = null, $count = null)
    {
        if ($count === null) {
            $count = count($groups);
        }

        if ($count > 0) {
            if ($owner === null) {
                if ($count === 1) {
                    $message = _('Removed owner of one group.');
                    $log = 'Besitzer von einer Gruppe entfernt';
                } else {
                    $log = sprintf('Besitzer von %s Gruppen entfernt', $count);
                    $message = __('Removed owner of %s groups.', $count);
                }
            } else {
                if ($count === 1) {
                    $message = __('Set owner of one group to %s.', (string)$owner);
                    $log = sprintf('Besitzer von einer Gruppe gesetzt auf %s', (string)$owner);
                } else {
                    $message = __('Set owner of %s groups to %s.', $count, (string)$owner);
                    $log = sprintf('Besitzer von %s Gruppen gesetzt auf %s', $count, (string)$owner);
                }
            }
            
            $this->get('iserv.logger')->write($log);
            $this->addMessage('info', $message);
        }
        
        return $this->messages;
    }

    /**
     * Log operations and add conclusion to response array.
     *
     * @param array $groups
     * @param array $flags
     * @param array $privileges
     * @param string $action
     * @return array
     */
    private function log(array $groups, array $flags, array $privileges, $action = 'assign')
    {
        if ($action === 'assign') {
            $prefix = 'Added';
            $preposition = 'to';
            $logSuffix = 'hinzugefügt';
            $logPreposition = 'zu';
        } elseif ($action === 'revoke') {
            $prefix = 'Removed';
            $preposition = 'from';
            $logSuffix = 'entfernt';
            $logPreposition = 'von';
        } else {
            throw new \InvalidArgumentException(sprintf('$action must be either "assign" or "revoke", "%s" given.', $action));
        }
        
        $countGroups = count($groups);
        $countFlags = count($flags);
        $countPrivileges = count($privileges);
        
        if ($countGroups > 0 && $countFlags > 0) {
            if ($countGroups == 1 && $countFlags == 1) {
                $message = _(sprintf('%s one group flag %s one group.', $prefix, $preposition));
                $log = sprintf('Ein Gruppenmerkmal %s einer Gruppe %s', $logPreposition, $logSuffix);
            } elseif ($countGroups == 1 && $countFlags > 1) {
                $message = sprintf(_(sprintf('%s %%s group flags %s one group.', $prefix, $preposition)), $countFlags);
                $log = sprintf('%s Gruppenmerkmale %s einer Gruppe %s', $countFlags, $logPreposition, $logSuffix);
            } elseif ($countGroups > 1 && $countFlags == 1) {
                $message = sprintf(_(sprintf('%s one group flag %s %%s groups.', $prefix, $preposition)), $countGroups);
                $log = sprintf('Ein Gruppenmerkmal %s %s Gruppen %s', $logPreposition, $countGroups, $logSuffix);
            } else {
                $message = sprintf(_(sprintf('%s %%s group flags %s %%s groups.', $prefix, $preposition)), $countFlags, $countGroups);
                $log = sprintf('%s Gruppenmerkmale %s %s Gruppen %s', $countFlags, $preposition, $countGroups, $logSuffix);
            }
            
            $this->get('iserv.logger')->write($log);
            $this->addMessage('info', $message);
        }

        if ($countGroups > 0 && $countPrivileges > 0) {
            if ($countGroups == 1 && $countPrivileges == 1) {
                $message = _(sprintf('%s one privilege %s one group.', $prefix, $preposition));
                $log = sprintf('Ein Recht %s einer Gruppe %s', $logPreposition, $logSuffix);
            } elseif ($countGroups == 1 && $countPrivileges > 1) {
                $message = sprintf(_(sprintf('%s %%s privileges %s one group.', $prefix, $preposition)), $countPrivileges);
                $log = sprintf('%s Rechte %s einer Gruppe %s', $countPrivileges, $logPreposition, $logSuffix);
            } elseif ($countGroups > 1 && $countPrivileges == 1) {
                $message = sprintf(_(sprintf('%s one privilege %s %%s groups.', $prefix, $preposition)), $countGroups);
                $log = sprintf('Ein Recht %s %s Gruppen %s', $logPreposition, $countGroups, $logSuffix);
            } else {
                $message = sprintf(_(sprintf('%s %%s privileges %s %%s groups.', $prefix, $preposition)), $countPrivileges, $countGroups);
                $log = sprintf('%s Rechte %s %s Gruppen %s', $countPrivileges, $logPreposition, $countGroups, $logSuffix);
            }
            
            $this->get('iserv.logger')->write($log);
            $this->addMessage('info', $message);
        }
        
        return $this->messages;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['form.factory'] = FormFactoryInterface::class;
        $services['iserv.logger'] = Logger::class;

        return $services;
    }
}
