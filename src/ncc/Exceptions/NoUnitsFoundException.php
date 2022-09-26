<?php

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class NoUnitsFoundException extends Exception
    {
        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * @param string $message
         * @param Throwable|null $previous
         */
        public function __construct(string $message = "", ?Throwable $previous = null)
        {
            parent::__construct($message, ExceptionCodes::NoUnitsFoundException, $previous);
            $this->message = $message;
            $this->previous = $previous;
        }
    }