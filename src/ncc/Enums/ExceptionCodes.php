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
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    final class ExceptionCodes
    {
        /**
         * @see RuntimeException
         */
        public const RUNTIME = -1706;

        /**
         * @see BuildException
         */
        public const BUILD_EXCEPTION = -1727;

        /**
         * @see IOException
         */
        public const IO_EXCEPTION = -1735;

        /**
         * @see ComposerException
         */
        public const COMPOSER_EXCEPTION = -1749;

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
         * @see IntegrityException
         */
        public const INTEGRITY_EXCEPTION = -1775;

        /**
         * @see OperationException
         */
        public const OPERATION_EXCEPTION = -1776;

        public const IMPORT_EXCEPTION = -1777;

        /**
         * All the exception codes from NCC
         */
        public const All = [
            self::RUNTIME,
            self::BUILD_EXCEPTION,
            self::IO_EXCEPTION,
            self::COMPOSER_EXCEPTION,
            self::AUTHENTICATION_EXCEPTION,
            self::NOT_SUPPORTED_EXCEPTION,
            self::ARCHIVE_EXCEPTION,
            self::PATH_NOT_FOUND,
            self::GIT_EXCEPTION,
            self::CONFIGURATION_EXCEPTION,
            self::PACKAGE_EXCEPTION,
            self::NETWORK_EXCEPTION,
            self::INTEGRITY_EXCEPTION,
            self::OPERATION_EXCEPTION,
            self::IMPORT_EXCEPTION
        ];
    }