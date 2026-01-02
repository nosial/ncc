<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    enum PackageStructure : string
    {
        case START_PACKAGE =    "\xA0";     // True start of the package, always followed by MAGIC_BYTES
        case MAGIC_BYTES =      "\x4E\x43\x43\x50\x4B\x47"; // NCCPKG, Always ends with TERMINATE
        case PACKAGE_VERSION =  "\xA1";     // Start of the package version bytes
        case HEADER =           "\xA2";     // Start of Header section
        case ASSEMBLY =         "\xA3";     // Start of Assembly section
        case EXECUTION_UNITS =  "\xA4";     // Used to define the start of execution units
        case COMPONENTS =       "\xA5";     // Used to define the start of components
        case RESOURCES =        "\xA6";     // Used to define the start of resources
        case TERMINATE =        "\xE0\xE0"; // Ends the section definition
        case SOFT_TERMINATE =   "\xE1";     // Ends the current subsection
    }
