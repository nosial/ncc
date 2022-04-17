<?php

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    class InvalidScopeException extends Exception
    {
        /**
         * Public Constructor
         *
         * @param string $scope
         * @param Throwable|null $previous
         */
        public function __construct(string $scope = "", ?Throwable $previous = null)
        {
            parent::__construct('The given scope \'' . $scope . '\' is not valid', ExceptionCodes::InvalidScopeException, $previous);
        }
    }