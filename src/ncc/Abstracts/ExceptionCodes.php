<?php

    namespace ncc\Abstracts;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\DirectoryNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\MalformedJsonException;

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

        /**
         * @see DirectoryNotFoundException
         */
        const DirectoryNotFoundException = -1702;

        /**
         * @see InvalidScopeException
         */
        const InvalidScopeException = -1703;

        /**
         * @see AccessDeniedException
         */
        const AccessDeniedException = -1704;

        /**
         * @see MalformedJsonException
         */
        const MalformedJsonException = -1705;
    }