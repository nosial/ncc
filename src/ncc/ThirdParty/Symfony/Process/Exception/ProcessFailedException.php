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
 * Exception for failed Processes.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ProcessFailedException extends RuntimeException
{
    private $Process;

    public function __construct(Process $Process)
    {
        if ($Process->isSuccessful()) {
            throw new InvalidArgumentException('Expected a failed Process, but the given Process was successful.');
        }

        $error = sprintf('The command "%s" failed.'."\n\nExit Code: %s(%s)\n\nWorking directory: %s",
            $Process->getCommandLine(),
            $Process->getExitCode(),
            $Process->getExitCodeText(),
            $Process->getWorkingDirectory()
        );

        if (!$Process->isOutputDisabled()) {
            $error .= sprintf("\n\nOutput:\n================\n%s\n\nError Output:\n================\n%s",
                $Process->getOutput(),
                $Process->getErrorOutput()
            );
        }

        parent::__construct($error);

        $this->Process = $Process;
    }

    public function getProcess()
    {
        return $this->Process;
    }
}
