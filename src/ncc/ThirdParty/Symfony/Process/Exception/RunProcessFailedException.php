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

use ncc\ThirdParty\Symfony\Process\Messenger\RunProcessContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RunProcessFailedException extends RuntimeException
{
    public function __construct(ProcessFailedException $exception, public readonly RunProcessContext $context)
    {
        parent::__construct($exception->getMessage(), $exception->getCode());
    }
}