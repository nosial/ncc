<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */

    namespace ExampleLibrary\Exceptions;

    use Exception;
    use Throwable;

    class FileNotFoundException extends Exception
    {
        /**
         * @var Throwable|null
         */
        private ?Throwable $previous;

        /**
         * @param string $message
         * @param int $code
         * @param Throwable|null $previous
         */
        public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
            $this->message = $message;
            $this->code = $code;
            $this->previous = $previous;
        }
    }