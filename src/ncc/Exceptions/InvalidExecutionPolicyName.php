<?php

    namespace ncc\Exceptions;

    use Exception;
    use Throwable;

    class InvalidExecutionPolicyName extends Exception
    {
        public function __construct(string $message = "", ?Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }
    }