<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */

    namespace old_tests\example_project\src\ExampleLibrary\Exceptions;

    use Exception;
    use Throwable;

    class InvalidNameException extends Exception
    {
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