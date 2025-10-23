<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    enum MacroVariable : string
    {
        // Runtime Macros
        case PROJECT_PATH = '${PROJECT_PATH}';
        case CURRENT_WORKING_DIRECTORY = '${CWD}';

        // Project Macros
        case ASSEMBLY_NAME = '${ASSEMBLY.NAME}';
        case ASSEMBLY_PACKAGE = '${ASSEMBLY.PACKAGE}';
        case ASSEMBLY_VERSION = '${ASSEMBLY.VERSION}';
        case ASSEMBLY_URL = '${ASSEMBLY.URL}';
        case ASSEMBLY_LICENSE = '${ASSEMBLY.LICENSE}';
        case ASSEMBLY_DESCRIPTION = '${ASSEMBLY.DESCRIPTION}';
        case ASSEMBLY_AUTHOR = '${ASSEMBLY.AUTHOR}';
        case ASSEMBLY_ORGANIZATION = '${ASSEMBLY.ORGANIZATION}';
        case ASSEMBLY_PRODUCT = '${ASSEMBLY.PRODUCT}';
        case ASSEMBLY_COPYRIGHT = '${ASSEMBLY.COPYRIGHT}';
        case ASSEMBLY_TRADEMARK = '${ASSEMBLY.TRADEMARK}';

        public static function translateMacros(string $input, bool $strict=false, ?callable $handle=null): string
        {
            // TODO: Implement this method recursively, allow $input to be a textual input containing one or more
            //       macro variables, such as "${PROJECT_PATH}/build_scripts/test.txt" so that the returning value
            //       would be complete. If $strict is True, it would throw an exception one or more macros could not
            //       be translated.

            switch(strtoupper($input))
            {

            }
        }
    }
