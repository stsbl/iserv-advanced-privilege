<?php declare(strict_types = 1);
// src/Stsbl/AdvancedPrivilegeBundle/Service/GroupHandler.php
namespace Stsbl\AdvancedPrivilegeBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Entity\GroupFlag;
use IServ\CoreBundle\Entity\Privilege;
use IServ\CoreBundle\Entity\User;
use IServ\CoreBundle\Service\GroupManager;
use IServ\CoreBundle\Service\Logger;
use IServ\CrudBundle\Exception\DatabaseConstraintException;
use IServ\CrudBundle\Exception\ObjectManagerException;
use Psr\Log\LoggerInterface;
use Stsbl\AdvancedPrivilegeBundle\Model\AbstractTargetChoice;
use Stsbl\AdvancedPrivilegeBundle\Model\GroupChoice;
use Stsbl\AdvancedPrivilegeBundle\Model\OwnerChoice;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

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
class GroupHandler
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Logger
     */
    private $iservLogger;

    public function __construct(
        RegistryInterface $doctrine,
        GroupManager $groupManager,
        LoggerInterface $logger,
        Logger $iservLogger
    ) {
        $this->doctrine = $doctrine;
        $this->groupManager = $groupManager;
        $this->logger = $logger;
        $this->iservLogger = $iservLogger;

        $this->flashBag = new FlashBag();
    }

    /**
     * Adds a message to the service flash bag.
     *
     * @param string $type
     * @param string $message
     */
    private function addMessage(string $type, string $message)
    {
        $this->flashBag->add($type, $message);
    }

    /**
     * Add messages by the group manager to output array sorted by category (error, success e.g)
     *
     * @param array $messages
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
    }

    /**
     * Get the messages formatted for JSON response.
     *
     * @return array
     */
    public function getMessages(): array
    {
        $result = [];

        foreach ($this->flashBag->keys() as $type) {
            foreach ($this->flashBag->get($type) as $message) {
                $result[] = [
                    'type' => $type,
                    'message' => $message,
                ];
            }
        }

        return $result;
    }

    /**
     * @param OwnerChoice $ownerChoice
     * @return bool
     */
    public function updateOwner(OwnerChoice $ownerChoice): bool
    {
        $owner = $ownerChoice->getOwner();

        $groups = $this->findGroups($ownerChoice->getTarget(), $ownerChoice->getPattern(), $owner === null);

        if (empty($groups)) {
            $this->addMessage('alert', _('No matching groups found.'));

            return true;
        }

        $numberUpdated = 0;
        /* @var $group \IServ\CoreBundle\Entity\Group */
        foreach ($groups as $group) {
            $group->setOwner($owner);

            try {
                $this->groupManager->update($group);
            } catch (DatabaseConstraintException $e) {
                $this->logger->error(sprintf(
                    'Unexpected database constraint exception during updating owner of group: %s',
                    $e->getMessage()
                ), ['exception' => $e]);

                return false;
            } catch (ObjectManagerException $e) {
                $this->addMessage('error', $e->getMessage());

                return false;
            }

            $numberUpdated++;
        }

        $messages = $this->groupManager->getMessages();

        if (!empty($messages)) {
            $this->addGroupManagerMessages($messages);

        }

        $this->logOwner($groups, $owner, $numberUpdated);

        return true;
    }

    /**
     * @param GroupChoice $groupChoice
     * @return bool
     */
    public function updateGroups(GroupChoice $groupChoice): bool
    {
        $flags = $groupChoice->getFlags();
        $privileges = $groupChoice->getPrivileges();

        $groups = $this->findGroups($groupChoice->getTarget(), $groupChoice->getPattern());

        if (empty($groups)) {
            $this->addMessage('alert', _('No matching groups found.'));

            return true;
        }

        foreach ($groups as $group) {
            foreach ($flags as $flag) {
                if ($groupChoice->isAssignAction()) {
                    $group->addFlag($flag);
                } elseif ($groupChoice->isRevokeAction()) {
                    $group->removeFlag($flag);
                }
            }

            foreach ($privileges as $privilege) {
                if ($groupChoice->isAssignAction()) {
                    $group->addPrivilege($privilege);
                } elseif ($groupChoice->isRevokeAction()) {
                    $group->removePrivilege($privilege);
                }
            }

            try {
                $this->groupManager->update($group);
            } catch (DatabaseConstraintException $e) {
                $this->logger->error(sprintf(
                    'Unexpected database constraint exception during updating owner of group: %s',
                    $e->getMessage()
                ), ['exception' => $e]);

                return false;
            } catch (ObjectManagerException $e) {
                $this->addMessage('error', $e->getMessage());

                return false;
            }
        }

        $messages = $this->groupManager->getMessages();

        if (!empty($messages)) {
            $this->addGroupManagerMessages($messages);
        }

        $this->log($groups, $flags, $privileges, $groupChoice->getAction());

        return true;
    }


    /**
     * Tries to find groups by given criteria
     *
     * @param string target
     * @param string $pattern
     * @param boolean $skipNoOwner
     * @return Group[]
     */
    private function findGroups(string $target, string $pattern = null, bool $skipNoOwner = false): array
    {
        /* @var $group \IServ\CoreBundle\Entity\Group */
        if (AbstractTargetChoice::TARGET_ALL === $target) {
            $groups = $this->doctrine->getRepository(Group::class)->findAll();
        } elseif (AbstractTargetChoice::TARGET_ENDING_WITH === $target) {
            $queryBuilder = $this->doctrine
                ->getRepository('IServCoreBundle:Group')
                ->createQueryBuilder(self::class)
            ;

            $queryBuilder
                ->select('g')
                ->from('IServCoreBundle:Group', 'g')
                ->where($queryBuilder->expr()->like('g.name', ':query'))
                ->setParameter('query', '%'.$pattern)
            ;

            if ($skipNoOwner) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNotNull('g.owner'));
            }

            $groups = $queryBuilder->getQuery()->getResult();
        } elseif (AbstractTargetChoice::TARGET_STARTING_WITH === $target) {
            $queryBuilder = $this->doctrine->getRepository(Group::class)->createQueryBuilder(self::class);
            $queryBuilder
                ->select('g')
                ->from('IServCoreBundle:Group', 'g')
                ->where($queryBuilder->expr()->like('g.name', ':query'))
                ->setParameter('query', $pattern.'%')
            ;

            if ($skipNoOwner) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNotNull('g.owner'));
            }

            $groups = $queryBuilder->getQuery()->getResult();
        } elseif (AbstractTargetChoice::TARGET_CONTAINS === $target) {
            $queryBuilder = $this->doctrine->getRepository(Group::class)->createQueryBuilder(self::class);
            $queryBuilder
                ->select('g')
                ->from('IServCoreBundle:Group', 'g')
                ->where('g.name LIKE :query')
                ->setParameter('query', '%'.$pattern.'%')
            ;

            $groups = $queryBuilder->getQuery()->getResult();
        } elseif (AbstractTargetChoice::TARGET_MATCHES === $target) {
            if (null === $pattern || 0 === strlen($pattern)) {
                throw new \InvalidArgumentException('$pattern must not be null for pattern search!');
            }

            $allGroups = $this->doctrine->getRepository(Group::class)->findAll();
            $groups = [];

            foreach ($allGroups as $group) {
                if ($skipNoOwner && $group->getOwner() === null) {
                    continue;
                }

                if (preg_match(sprintf('/%s/', $pattern), $group->getName())) {
                    $groups[] = $group;
                }
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Not an implemented target: %s.', $target));
        }

        return $groups;
    }

    /**
     * Log owner operations and add conclusion to response array.
     *
     * @param array $groups
     * @param User $owner
     * @param int $count
     */
    private function logOwner(array $groups, User $owner = null, int $count = null)
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

            $this->iservLogger->write($log);
            $this->addMessage('info', $message);
        }
    }

    /**
     * Log operations and add conclusion to response array.
     *
     * @param array $groups
     * @param ArrayCollection|GroupFlag[] $flags
     * @param ArrayCollection|Privilege[] $privileges
     * @param string $action
     */
    private function log(
        array $groups,
        ArrayCollection $flags,
        ArrayCollection $privileges,
        string $action = GroupChoice::ACTION_ASSIGN
    ) {
        if (GroupChoice::ACTION_ASSIGN === $action) {
            $prefix = 'Added';
            $preposition = 'to';
            $logSuffix = 'hinzugefügt';
            $logPreposition = 'zu';
        } elseif (GroupChoice::ACTION_REVOKE === $action) {
            $prefix = 'Removed';
            $preposition = 'from';
            $logSuffix = 'entfernt';
            $logPreposition = 'von';
        } else {
            throw new \InvalidArgumentException(sprintf(
                '$action must be either "assign" or "revoke", "%s" given.',
                $action
            ));
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
                $message = sprintf(_(
                    sprintf('%s %%s group flags %s %%s groups.', $prefix, $preposition)
                ), $countFlags, $countGroups);
                $log = sprintf(
                    '%s Gruppenmerkmale %s %s Gruppen %s',
                    $countFlags,
                    $preposition,
                    $countGroups,
                    $logSuffix
                );
            }

            $this->iservLogger->write($log);
            $this->addMessage('info', $message);
        }

        if ($countGroups > 0 && $countPrivileges > 0) {
            if ($countGroups == 1 && $countPrivileges == 1) {
                $message = _(sprintf('%s one privilege %s one group.', $prefix, $preposition));
                $log = sprintf('Ein Recht %s einer Gruppe %s', $logPreposition, $logSuffix);
            } elseif ($countGroups == 1 && $countPrivileges > 1) {
                $message = sprintf(_(
                    sprintf('%s %%s privileges %s one group.', $prefix, $preposition)
                ), $countPrivileges);
                $log = sprintf('%s Rechte %s einer Gruppe %s', $countPrivileges, $logPreposition, $logSuffix);
            } elseif ($countGroups > 1 && $countPrivileges == 1) {
                $message = sprintf(_(sprintf('%s one privilege %s %%s groups.', $prefix, $preposition)), $countGroups);
                $log = sprintf('Ein Recht %s %s Gruppen %s', $logPreposition, $countGroups, $logSuffix);
            } else {
                $message = sprintf(_(
                    sprintf('%s %%s privileges %s %%s groups.', $prefix, $preposition)
                ), $countPrivileges, $countGroups);
                $log = sprintf(
                    '%s Rechte %s %s Gruppen %s',
                    $countPrivileges,
                    $logPreposition,
                    $countGroups,
                    $logSuffix
                );
            }

            $this->iservLogger->write($log);
            $this->addMessage('info', $message);
        }
    }
}
