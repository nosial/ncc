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
    enum ExceptionCodes : int
    {
        /**
         * @see RuntimeException
         */
        case RUNTIME = -1706;

        /**
         * @see BuildException
         */
        case BUILD_EXCEPTION = -1727;

        /**
         * @see IOException
         */
        case IO_EXCEPTION = -1735;

        /**
         * @see ComposerException
         */
        case COMPOSER_EXCEPTION = -1749;

        /**
         * @see AuthenticationException
         */
        case AUTHENTICATION_EXCEPTION = -1760;

        /**
         * @see NotSupportedException
         */
        case NOT_SUPPORTED_EXCEPTION = -1761;

        /**
         * @see ArchiveException
         */
        case ARCHIVE_EXCEPTION = -1764;

        /**
         * @see PathNotFoundException
         */
        case PATH_NOT_FOUND = -1769;

        /**
         * @see GitException
         */
        case GIT_EXCEPTION = -1770;

        /**
         * @see ConfigurationException
         */
        case CONFIGURATION_EXCEPTION = -1772;

        /**
         * @see PackageException
         */
        case PACKAGE_EXCEPTION = -1773;

        /**
         * @see NetworkException
         */
        case NETWORK_EXCEPTION = -1774;

        /**
         * @see IntegrityException
         */
        case INTEGRITY_EXCEPTION = -1775;

        /**
         * @see OperationException
         */
        case OPERATION_EXCEPTION = -1776;

        /**
         * @see ImportException
         */
        case IMPORT_EXCEPTION = -1777;
    }