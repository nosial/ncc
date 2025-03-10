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

    namespace ncc\Enums\SpecialConstants;

    enum AssemblyConstants : string
    {
        /**
         * Assembly's Name Property
         */
        case ASSEMBLY_NAME = '%ASSEMBLY.NAME%';

        /**
         * Assembly's Package Property
         */
        case ASSEMBLY_PACKAGE = '%ASSEMBLY.PACKAGE%';

        /**
         * Assembly's Description Property
         */
        case ASSEMBLY_DESCRIPTION = '%ASSEMBLY.DESCRIPTION%';

        /**
         * Assembly's Company Property
         */
        case ASSEMBLY_COMPANY = '%ASSEMBLY.COMPANY%';

        /**
         * Assembly's Product Property
         */
        case ASSEMBLY_PRODUCT = '%ASSEMBLY.PRODUCT%';

        /**
         * Assembly's Copyright Property
         */
        case ASSEMBLY_COPYRIGHT = '%ASSEMBLY.COPYRIGHT%';

        /**
         * Assembly's Trademark Property
         */
        case ASSEMBLY_TRADEMARK = '%ASSEMBLY.TRADEMARK%';

        /**
         * Assembly's Version Property
         */
        case ASSEMBLY_VERSION = '%ASSEMBLY.VERSION%';

        /**
         * Assembly's UUID property
         */
        case ASSEMBLY_UID = '%ASSEMBLY.UID%';
    }