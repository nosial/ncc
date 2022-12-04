<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */

    namespace ncc\Exceptions;

    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class ComposerDisabledException extends \Exception
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
            parent::__construct($message, ExceptionCodes::ComposerDisabledException, $previous);
            $this->message = $message;
            $this->previous = $previous;
        }
    }