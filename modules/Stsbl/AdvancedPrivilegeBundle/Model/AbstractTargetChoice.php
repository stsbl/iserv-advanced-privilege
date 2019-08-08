<?php
declare(strict_types=1);

namespace Stsbl\AdvancedPrivilegeBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
abstract class AbstractTargetChoice
{
    const TARGET_ALL = 'all';
    const TARGET_STARTING_WITH = 'starting-with';
    const TARGET_ENDING_WITH = 'ending-with';
    const TARGET_CONTAINS = 'contains';
    const TARGET_MATCHES = 'matches';

    /**
     * List of valid values for self::$target.
     *
     * @var string[]
     */
    private static $validTargets = [
        self::TARGET_ALL,
        self::TARGET_STARTING_WITH,
        self::TARGET_ENDING_WITH,
        self::TARGET_CONTAINS,
        self::TARGET_MATCHES,
    ];

    /**
     * List of well known regular expression errors.
     *
     * @var string[]
     */
    private $pregErrorMap = [
        PREG_NO_ERROR => 'No errors',
        PREG_INTERNAL_ERROR => 'There was an internal PCRE error',
        PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit was exhausted',
        PREG_RECURSION_LIMIT_ERROR => 'Recursion limit was exhausted',
        PREG_BAD_UTF8_ERROR => 'The offset didn\'t correspond to the begin of a valid UTF-8 code point',
        PREG_BAD_UTF8_OFFSET_ERROR => 'Malformed UTF-8 data',
    ];

    /**
     * @Assert\NotBlank(message="Please select a target.")
     *
     * @var string|null
     */
    protected $target;

    /**
     * @var string|null
     */
    protected $pattern;

    /**
     * @Assert\IsTrue(message="The target choice is not valid.")
     *
     * @return bool
     */
    public function isTargetValid(): bool
    {
        return null === $this->target || in_array($this->target, self::$validTargets);
    }

    /**
     * Special callback to validate pattern.
     *
     * @Assert\Callback()
     *
     * @param ExecutionContextInterface $context
     */
    public function validatePattern(ExecutionContextInterface $context): void
    {
        if (self::TARGET_ALL === $this->target) {
            return;
        }

        if (null === $this->pattern || strlen($this->pattern) < 1) {
            $context
                ->buildViolation(_('Pattern should not be empty.'))
                ->atPath('pattern')
                ->addViolation()
            ;

            return;
        }

        if (self::TARGET_MATCHES !== $this->target) {
            return;
        }

        // test regular expression against dummy value
        $dummy = 'SomeGroupName';

        @preg_match(sprintf('/%s/', $this->pattern), $dummy);
        $lastError = preg_last_error();

        if ($lastError !== PREG_NO_ERROR) {
            $context
                ->buildViolation(__('Invalid pattern: %s', $this->pregErrorMap[$lastError] ?? _('Unknown error')))
                ->atPath('pattern')
                ->addViolation()
            ;
        }
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @return $this
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    /**
     * @return $this
     */
    public function setPattern(string $pattern = null): self
    {
        $this->pattern = $pattern;

        return $this;
    }
}
