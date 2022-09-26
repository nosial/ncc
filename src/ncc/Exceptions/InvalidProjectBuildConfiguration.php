<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class InvalidProjectBuildConfiguration extends Exception
    {
        private ?Throwable $previous;

        /**
         * @param string $message
         * @param Throwable|null $previous
         */
        public function __construct(string $message = "", ?Throwable $previous = null)
        {
            parent::__construct($message, ExceptionCodes::InvalidProjectBuildConfiguration, $previous);
            $this->message = $message;
            $this->previous = $previous;
        }
    }