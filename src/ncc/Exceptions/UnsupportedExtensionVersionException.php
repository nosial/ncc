<?php

    namespace ncc\Exceptions;

    use Exception;

    class UnsupportedExtensionVersionException extends Exception
    {
        public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }
    }