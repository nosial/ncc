<?php

    namespace ncc\Abstracts;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\ComponentVersionNotFoundException;
    use ncc\Exceptions\ConstantReadonlyException;
    use ncc\Exceptions\DirectoryNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidCredentialsEntryException;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\RuntimeException;

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

        /**
         * @see RuntimeException
         */
        const RuntimeException = -1706;

        /**
         * @see InvalidCredentialsEntryException
         */
        const InvalidCredentialsEntryException = -1707;

        /**
         * @see ComponentVersionNotFoundException
         */
        const ComponentVersionNotFoundException = -1708;

        /**
         * @see ConstantReadonlyException
         */
        const ConstantReadonlyException = -1709;
    }