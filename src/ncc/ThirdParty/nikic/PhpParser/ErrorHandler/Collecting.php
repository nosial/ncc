<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\ErrorHandler;

use ncc\ThirdParty\nikic\PhpParser\Error;
use ncc\ThirdParty\nikic\PhpParser\ErrorHandler;

/**
 * Error handler that collects all errors into an array.
 *
 * This allows graceful handling of errors.
 */
class Collecting implements ErrorHandler {
    /** @var Error[] Collected errors */
    private array $errors = [];

    public function handleError(Error $error): void {
        $this->errors[] = $error;
    }

    /**
     * Get collected errors.
     *
     * @return Error[]
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Check whether there are any errors.
     */
    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    /**
     * Reset/clear collected errors.
     */
    public function clearErrors(): void {
        $this->errors = [];
    }
}
