<?php

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class InvalidProjectConfigurationException extends Exception
    {

        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * The full property name that needs correcting
         *
         * @var string|null
         */
        private ?string $property;

        /**
         * @param string $message
         * @param string|null $property
         * @param Throwable|null $previous
         */
        public function __construct(string $message = "", ?string $property=null, ?Throwable $previous = null)
        {
            parent::__construct($message, ExceptionCodes::InvalidProjectConfigurationException, $previous);
            $this->message = $message;
            $this->previous = $previous;
            $this->property = $property;
        }
    }