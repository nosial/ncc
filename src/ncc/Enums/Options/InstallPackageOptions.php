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

    namespace ncc\Enums\Options;

    enum InstallPackageOptions : string
    {
        /**
         * Skips the installation of dependencies of the package
         *
         * @warning This will cause the package to fail to import of
         *          the dependencies are not met
         */
        case SKIP_DEPENDENCIES = 'skip-dependencies';

        /**
         * Reinstall all packages if they are already installed,
         * Including dependencies if they are being processed.
         */
        case REINSTALL = 'reinstall';

        /**
         * Installs a static version of the package if it's available
         * otherwise it will install non-static version
         */
        case PREFER_STATIC = 'prefer-static';

        /**
         * Forces ncc to build packages from source rather than trying to obtain
         * a pre-built version of the package
         */
        case BUILD_SOURCE  = 'build-source';
    }