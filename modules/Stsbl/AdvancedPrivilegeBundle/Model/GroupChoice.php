<?php

declare(strict_types=1);

namespace Stsbl\AdvancedPrivilegeBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use IServ\CoreBundle\Entity\GroupFlag;
use IServ\CoreBundle\Entity\Privilege;
use Symfony\Component\Validator\Constraints as Assert;

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
final class GroupChoice extends AbstractTargetChoice
{
    public const ACTION_ASSIGN = 'assign';
    public const ACTION_REVOKE = 'revoke';

    /**
     * List of valid values for self::$action.
     *
     * @var string[]
     */
    private static array $validActions = [self::ACTION_ASSIGN, self::ACTION_REVOKE];

    /**
     * @var ArrayCollection&GroupFlag[]
     */
    private ArrayCollection $flags;

    /**
     * @var ArrayCollection&Privilege[]
     */
    private ArrayCollection $privileges;

    /**
     * @Assert\NotBlank()
     */
    private ?string $action;

    public function __construct()
    {
        $this->flags = new ArrayCollection();
        $this->privileges = new ArrayCollection();
    }

    public function __clone()
    {
        $this->flags = clone $this->flags;
        $this->privileges = clone $this->privileges;
    }

    /**
     * @Assert\IsTrue(message="The action is not valid.")
     */
    public function isActionValid(): bool
    {
        return null === $this->action || in_array($this->action, self::$validActions, true);
    }

    public function isAssignAction(): bool
    {
        return self::ACTION_ASSIGN === $this->action;
    }

    public function isRevokeAction(): bool
    {
        return self::ACTION_REVOKE === $this->action;
    }

    /**
     * @Assert\IsTrue(message="Please select at least one privilege or group flag.")
     */
    public function isFlagAndPrivilegeChoiceValid(): bool
    {
        return $this->flags->count() > 0 || $this->privileges->count() > 0;
    }

    /**
     * @return ArrayCollection&GroupFlag[]
     */
    public function getFlags(): ArrayCollection
    {
        return $this->flags;
    }

    /**
     * @param ArrayCollection&GroupFlag[] $flags
     * @return $this
     */
    public function setFlags(ArrayCollection $flags): self
    {
        $this->flags = $flags;

        return $this;
    }

    /**
     * @return $this
     */
    public function addFlag(GroupFlag $flag): self
    {
        $this->flags->add($flag);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeFlag(GroupFlag $flag): self
    {
        $this->flags->removeElement($flag);

        return $this;
    }

    /**
     * @return ArrayCollection&Privilege[]
     */
    public function getPrivileges(): ArrayCollection
    {
        return $this->privileges;
    }

    /**
     * @param ArrayCollection&Privilege[] $privileges
     * @return $this
     */
    public function setPrivileges(ArrayCollection $privileges): self
    {
        $this->privileges = $privileges;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @return $this
     */
    public function setAction(string $action = null): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string[]
     */
    public static function getValidActions(): array
    {
        return self::$validActions;
    }
}
