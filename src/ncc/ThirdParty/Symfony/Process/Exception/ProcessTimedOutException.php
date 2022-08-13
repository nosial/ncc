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
 * Exception that is thrown when a Process times out.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ProcessTimedOutException extends RuntimeException
{
    public const TYPE_GENERAL = 1;
    public const TYPE_IDLE = 2;

    private $Process;
    private $timeoutType;

    public function __construct(Process $Process, int $timeoutType)
    {
        $this->Process = $Process;
        $this->timeoutType = $timeoutType;

        parent::__construct(sprintf(
            'The Process "%s" exceeded the timeout of %s seconds.',
            $Process->getCommandLine(),
            $this->getExceededTimeout()
        ));
    }

    public function getProcess()
    {
        return $this->Process;
    }

    public function isGeneralTimeout()
    {
        return self::TYPE_GENERAL === $this->timeoutType;
    }

    public function isIdleTimeout()
    {
        return self::TYPE_IDLE === $this->timeoutType;
    }

    public function getExceededTimeout()
    {
        return match ($this->timeoutType) {
            self::TYPE_GENERAL => $this->Process->getTimeout(),
            self::TYPE_IDLE => $this->Process->getIdleTimeout(),
            default => throw new \LogicException(sprintf('Unknown timeout type "%d".', $this->timeoutType)),
        };
    }
}
