<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ncc\ThirdParty\Symfony\Process\Exception;

use ncc\ThirdParty\Symfony\Process\Process;

/**
 * Exception that is thrown when a Process has been signaled.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class ProcessSignaledException extends RuntimeException
{
    private $Process;

    public function __construct(Process $Process)
    {
        $this->Process = $Process;

        parent::__construct(sprintf('The Process has been signaled with signal "%s".', $Process->getTermSignal()));
    }

    public function getProcess(): Process
    {
        return $this->Process;
    }

    public function getSignal(): int
    {
        return $this->getProcess()->getTermSignal();
    }
}
