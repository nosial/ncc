<?php

    namespace ncc\Abstracts;

    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidProjectConfigurationException;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
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