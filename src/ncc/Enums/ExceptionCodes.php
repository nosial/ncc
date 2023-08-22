<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
     *
     *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
     *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
     *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
     *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
     *  conditions:
     *
     *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
     *  of the Software.
     *
     *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
     *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
     *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
     *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
     *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
     *  DEALINGS IN THE SOFTWARE.
     *
     */

    namespace ncc\Enums;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    final class ExceptionCodes
    {
        /**
         * @see MalformedJsonException
         */
        public const MALFORMED_JSON = -1705;

        /**
         * @see RuntimeException
         */
        public const RUNTIME = -1706;

        /**
         * @see ConstantReadonlyException
         */
        public const CONSTANT_READ_ONLY = -1709;

        /**
         * @see ProjectAlreadyExistsException
         */
        public const PROJECT_ALREADY_EXISTS = -1713;

        /**
         * @see AutoloadGeneratorException
         */
        public const AUTOLOAD_GENERATOR = -1714;

        /**
         * @see NoUnitsFoundException
         */
        public const NO_UNITS_FOUND = -1715;

        /**
         * @see InvalidConstantNameException
         */
        public const INVALID_CONSTANT_NAME = -1719;

        /**
         * @see BuildException
         */
        public const BUILD_EXCEPTION = -1727;

        /**
         * @see PackageLockException
         */
        public const PACKAGE_LOCK_EXCEPTION = -1729;

        /**
         * @see InstallationException
         */
        public const INSTALLATION_EXCEPTION = -1730;

        /**
         * @see ComponentDecodeException
         */
        public const COMPONENT_DECODE_EXCEPTION = -1732;

        /**
         * @see IOException
         */
        public const IO_EXCEPTION = -1735;

        /**
         * @see VersionNotFoundException
         */
        public const VERSION_NOT_FOUND = -1737;

        /**
         * @see RunnerExecutionException
         */
        public const RUNNER_EXECUTION_EXCEPTION = -1741;

        /**
         * @see NoAvailableUnitsException
         */
        public const NO_AVAILABLE_UNITS = -1742;

        /**
         * @see PackageNotFoundException
         */
        public const PACKAGE_NOT_FOUND = -1745;

        /**
         * @see ComposerException
         */
        public const COMPOSER_EXCEPTION = -1749;

        /**
         * @see UserAbortedOperationException
         */
        public const USER_ABORTED_OPERATION = -1750;

        /**
         * @see ImportException
         */
        public const IMPORT_EXCEPTION = -1757;

        /**
         * @see AuthenticationException
         */
        public const AUTHENTICATION_EXCEPTION = -1760;

        /**
         * @see NotSupportedException
         */
        public const NOT_SUPPORTED_EXCEPTION = -1761;

        /**
         * @see ArchiveException
         */
        public const ARCHIVE_EXCEPTION = -1764;

        /**
         * @see SymlinkException
         */
        public const SYMLINK_EXCEPTION = -1768;

        /**
         * @see PathNotFoundException
         */
        public const PATH_NOT_FOUND = -1769;

        /**
         * @see GitException
         */
        public const GIT_EXCEPTION = -1770;

        /**
         * @see ConfigurationException
         */
        public const CONFIGURATION_EXCEPTION = -1772;

        /**
         * @see PackageException
         */
        public const PACKAGE_EXCEPTION = -1773;

        /**
         * @see NetworkException
         */
        public const NETWORK_EXCEPTION = -1774;

        /**
         * All the exception codes from NCC
         */
        public const All = [
            self::MALFORMED_JSON,
            self::RUNTIME,
            self::CONSTANT_READ_ONLY,
            self::PROJECT_ALREADY_EXISTS,
            self::AUTOLOAD_GENERATOR,
            self::NO_UNITS_FOUND,
            self::INVALID_CONSTANT_NAME,
            self::BUILD_EXCEPTION,
            self::PACKAGE_LOCK_EXCEPTION,
            self::INSTALLATION_EXCEPTION,
            self::COMPONENT_DECODE_EXCEPTION,
            self::IO_EXCEPTION,
            self::VERSION_NOT_FOUND,
            self::RUNNER_EXECUTION_EXCEPTION,
            self::NO_AVAILABLE_UNITS,
            self::PACKAGE_NOT_FOUND,
            self::COMPOSER_EXCEPTION,
            self::USER_ABORTED_OPERATION,
            self::AUTHENTICATION_EXCEPTION,
            self::NOT_SUPPORTED_EXCEPTION,
            self::ARCHIVE_EXCEPTION,
            self::SYMLINK_EXCEPTION,
            self::PATH_NOT_FOUND,
            self::GIT_EXCEPTION,
            self::CONFIGURATION_EXCEPTION,
            self::PACKAGE_EXCEPTION,
            self::NETWORK_EXCEPTION
        ];
    }