<?php

    namespace ncc\Exceptions;

    use Exception;
    use ncc\Abstracts\ExceptionCodes;
    use Throwable;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class FileNotFoundException extends Exception
    {
        /**
         * @param string $path
         * @param Throwable|null $previous
         */
        public function __construct(string $path = "", ?Throwable $previous = null)
        {
            parent::__construct('The file \'' . realpath($path) . '\' was not found', ExceptionCodes::FileNotFoundException, $previous);
            $this->code = ExceptionCodes::FileNotFoundException;
        }
    }