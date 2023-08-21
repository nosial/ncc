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
         * @see AccessDeniedException
         */
        public const ACCESS_DENIED = -1704;

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
         * @see InvalidVersionNumberException
         */
        public const INVALID_VERSION_NUMBER = -1711;

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
         * @see PackagePreparationFailedException
         */
        public const PACKAGE_PREPARATION_FAILED = -1720;

        /**
         * @see BuildConfigurationNotFoundException
         */
        public const BUILD_CONFIGURATION_NOT_FOUND = -1721;

        /**
         * @see InvalidProjectBuildConfiguration
         */
        public const INVALID_PROJECT_BUILD_CONFIGURATION = -1722;

        /**
         * @see InvalidPropertyValueException
         */
        public const INVALID_PROPERTY_VALUE = -1724;

        /**
         * @see BuildException
         */
        public const BUILD_EXCEPTION = -1727;

        /**
         * @see PackageParsingException
         */
        public const PACKAGE_PARSING_EXCEPTION = -1728;

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
         * @see ComponentChecksumException
         */
        public const COMPONENT_CHECKSUM_EXCEPTION = -1733;

        /**
         * @see ResourceChecksumException
         */
        public const RESOURCE_CHECKSUM_EXCEPTION = -1734;

        /**
         * @see IOException
         */
        public const IO_EXCEPTION = -1735;

        /**
         * @see VersionNotFoundException
         */
        public const VERSION_NOT_FOUND = -1737;

        /**
         * @see UndefinedExecutionPolicyException
         */
        public const UNDEFINED_EXECUTION_POLICY = -1738;

        /**
         * @see InvalidExecutionPolicyName
         */
        public const INVALID_EXECUTION_POLICY_NAME = -1739;

        /**
         * @see ProjectConfigurationNotFoundException
         */
        public const PROJECT_CONFIGURATION_NOT_FOUND = -1740;

        /**
         * @see RunnerExecutionException
         */
        public const RUNNER_EXECUTION_EXCEPTION = -1741;

        /**
         * @see NoAvailableUnitsException
         */
        public const NO_AVAILABLE_UNITS = -1742;

        /**
         * @see PackageAlreadyInstalledException
         */
        public const PACKAGE_ALREADY_INSTALLED = -1744;

        /**
         * @see PackageNotFoundException
         */
        public const PACKAGE_NOT_FOUND = -1745;

        /**
         * @see ComposerDisabledException
         */
        public const COMPOSER_DISABLED_EXCEPTION = -1746;

        /**
         * @see InternalComposerNotAvailableException
         */
        public const INTERNAL_COMPOSER_NOT_AVAILABLE = -1747;

        /**
         * @see ComposerNotAvailableException
         */
        public const COMPOSER_NOT_AVAILABLE = -1748;

        /**
         * @see ComposerException
         */
        public const COMPOSER_EXCEPTION = -1749;

        /**
         * @see UserAbortedOperationException
         */
        public const USER_ABORTED_OPERATION = -1750;

        /**
         * @see MissingDependencyException
         */
        public const MISSING_DEPENDENCY = -1751;

        /**
         * @see HttpException
         */
        public const HTTP_EXCEPTION = -1752;

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
         * @see PackageFetchException
         */
        public const PACKAGE_FETCH_EXCEPTION = -1765;

        /**
         * @see InvalidDependencyConfiguration
         */
        public const INVALID_DEPENDENCY_CONFIGURATION = -1767;

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
         * @see ResourceNotFoundException
         */
        public const RESOURCE_NOT_FOUND = -1771;

        /**
         * @see ConfigurationException
         */
        public const CONFIGURATION_EXCEPTION = -1772;

        /**
         * All the exception codes from NCC
         */
        public const All = [
            self::ACCESS_DENIED,
            self::MALFORMED_JSON,
            self::RUNTIME,
            self::CONSTANT_READ_ONLY,
            self::INVALID_VERSION_NUMBER,
            self::PROJECT_ALREADY_EXISTS,
            self::AUTOLOAD_GENERATOR,
            self::NO_UNITS_FOUND,
            self::INVALID_CONSTANT_NAME,
            self::PACKAGE_PREPARATION_FAILED,
            self::BUILD_CONFIGURATION_NOT_FOUND,
            self::INVALID_PROJECT_BUILD_CONFIGURATION,
            self::INVALID_PROPERTY_VALUE,
            self::BUILD_EXCEPTION,
            self::PACKAGE_PARSING_EXCEPTION,
            self::PACKAGE_LOCK_EXCEPTION,
            self::INSTALLATION_EXCEPTION,
            self::COMPONENT_DECODE_EXCEPTION,
            self::RESOURCE_CHECKSUM_EXCEPTION,
            self::IO_EXCEPTION,
            self::VERSION_NOT_FOUND,
            self::UNDEFINED_EXECUTION_POLICY,
            self::INVALID_EXECUTION_POLICY_NAME,
            self::PROJECT_CONFIGURATION_NOT_FOUND,
            self::RUNNER_EXECUTION_EXCEPTION,
            self::NO_AVAILABLE_UNITS,
            self::PACKAGE_ALREADY_INSTALLED,
            self::PACKAGE_NOT_FOUND,
            self::COMPOSER_DISABLED_EXCEPTION,
            self::INTERNAL_COMPOSER_NOT_AVAILABLE,
            self::COMPOSER_NOT_AVAILABLE,
            self::COMPOSER_EXCEPTION,
            self::USER_ABORTED_OPERATION,
            self::MISSING_DEPENDENCY,
            self::HTTP_EXCEPTION,
            self::AUTHENTICATION_EXCEPTION,
            self::NOT_SUPPORTED_EXCEPTION,
            self::ARCHIVE_EXCEPTION,
            self::PACKAGE_FETCH_EXCEPTION,
            self::INVALID_DEPENDENCY_CONFIGURATION,
            self::SYMLINK_EXCEPTION,
            self::PATH_NOT_FOUND,
            self::GIT_EXCEPTION,
            self::RESOURCE_NOT_FOUND,
            self::CONFIGURATION_EXCEPTION
        ];
    }