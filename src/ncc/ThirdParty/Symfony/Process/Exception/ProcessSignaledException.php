<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ncc\ThirdParty\Symfony\process\Exception;

use ncc\ThirdParty\Symfony\process\process;

/**
 * Exception that is thrown when a process has been signaled.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class processSignaledException extends RuntimeException
{
    private $process;

    public function __construct(process $process)
    {
        $this->process = $process;

        parent::__construct(sprintf('The process has been signaled with signal "%s".', $process->getTermSignal()));
    }

    public function getprocess(): process
    {
        return $this->process;
    }

    public function getSignal(): int
    {
        return $this->getprocess()->getTermSignal();
    }
}
