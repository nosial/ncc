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

    namespace ncc\Enums\Types;

    final class ComposerPackageTypes
    {
        /**
         * This is the default. It will copy the files to `vendor`
         */
        public const LIBRARY = 'library';

        /**
         * This denotes a project rather than a library. For example
         * application shells like the Symfony standard edition, CMSs
         * like the SilverStripe installer or full-fledged applications
         * distributed as packages. This can for example be used by IDEs
         * to provide listings of projects to initialize when creating
         * a new workspace.
         */
        public const PROJECT = 'project';

        /**
         * An empty package that contains requirements and will trigger
         * their installation, but contains no files and will not write
         * anything to the filesystem. As such, it does not require a
         * a dist or source key to be installable
         */
        public const METAPACKAGE = 'metapackage';

        /**
         * A package of type `composer-plugin` may provide an installer
         * for other packages that have a custom type.
         */
        public const COMPOSER_PLUGIN = 'composer-plugin';
    }