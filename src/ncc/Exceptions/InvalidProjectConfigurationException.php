<?php

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
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