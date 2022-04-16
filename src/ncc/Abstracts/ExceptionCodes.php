<?php

    namespace ncc\Abstracts;

    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidProjectConfigurationException;

    abstract class ExceptionCodes
    {
        /**
         * @see InvalidProjectConfigurationException
         */
        const InvalidProjectConfigurationException = -1700;

        /**
         * @see FileNotFoundException;
         */
        const FileNotFoundException = -1701;
    }