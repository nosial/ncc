<?php

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class DirectoryNotFoundException extends Exception
    {
        /**
         * Public Constructor
         *
         * @param string $path
         * @param Throwable|null $previous
         */
        public function __construct(string $path = "", ?Throwable $previous = null)
        {
            parent::__construct('The file \'' . realpath($path) . '\' was not found', ExceptionCodes::DirectoryNotFoundException, $previous);
            $this->code = ExceptionCodes::DirectoryNotFoundException;
        }
    }