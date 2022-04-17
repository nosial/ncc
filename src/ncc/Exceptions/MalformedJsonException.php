<?php

    namespace ncc\Exceptions;


    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class MalformedJsonException extends Exception
    {
        /**
         * @param string $message
         * @param Throwable|null $previous
         */
        public function __construct(string $message = "", ?Throwable $previous = null)
        {
            parent::__construct($message, ExceptionCodes::MalformedJsonException, $previous);
        }
    }