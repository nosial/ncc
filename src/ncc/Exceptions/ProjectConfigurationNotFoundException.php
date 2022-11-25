<?php

    namespace ncc\Exceptions;

    use Exception;

    class ProjectConfigurationNotFoundException extends Exception
    {
        public function __construct(string $message = "", ?Throwable $previous = null)
        {
            parent::__construct($message, $previous);
        }
    }