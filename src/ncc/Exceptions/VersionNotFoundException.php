<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class VersionNotFoundException extends Exception
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
            parent::__construct($message, ExceptionCodes::VersionNotFoundException, $previous);
            $this->message = $message;
            $this->previous = $previous;
        }
    }