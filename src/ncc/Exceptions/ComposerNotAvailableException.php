<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;

    class ComposerNotAvailableException extends Exception
    {
        /**
         * @var null
         */
        private $previous;

        /**
         * @param string $message
         * @param $previous
         */
        public function __construct(string $message = "", $previous = null)
        {
            parent::__construct($message, ExceptionCodes::ComposerNotAvailableException, $previous);
            $this->message = $message;
            $this->previous = $previous;
        }
    }