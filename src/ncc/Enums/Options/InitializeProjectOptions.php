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

    enum InitializeProjectOptions : string
    {
        /**
         * A custom path to the project's source directory
         */
        case PROJECT_SRC_PATH = 'PROJECT_SRC_PATH';

        /**
         * A boolean option that indicates whether to overwrite the project file if it already exists
         */
        case OVERWRITE_PROJECT_FILE = 'OVERWRITE_PROJECT_FILE';

        /**
         * Composer Only, used to define the package's real version
         */
        case COMPOSER_PACKAGE_VERSION = 'COMPOSER_PACKAGE_VERSION';

        /**
         * Composer Only, used to define the package's update source
         */
        case COMPOSER_REMOTE_SOURCE = 'COMPOSER_REMOTE_SOURCE';
    }