<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\ErrorHandler;

use ncc\ThirdParty\nikic\PhpParser\Error;
use ncc\ThirdParty\nikic\PhpParser\ErrorHandler;

/**
 * Error handler that handles all errors by throwing them.
 *
 * This is the default strategy used by all components.
 */
class Throwing implements ErrorHandler {
    public function handleError(Error $error): void {
        throw $error;
    }
}
