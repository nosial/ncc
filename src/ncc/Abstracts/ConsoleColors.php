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

namespace ncc\Abstracts;

    abstract class ConsoleColors
    {
        const Default = "\e[39m";

        const Black = "\e[30m";

        const Red = "\e[31m";

        const Green = "\e[32m";

        const Yellow = "\e[33m";

        const Blue = "\e[34m";

        const Magenta = "\e[35m";

        const Cyan = "\e[36m";

        const LightGray = "\e[37m";

        const DarkGrey = "\e[90m";

        const LightRed = "\e[91m";

        const LightGreen = "\e[92m";

        const LightYellow = "\e[93m";

        const LightBlue = "\e[94m";

        const LightMagenta = "\e[95m";

        const LightCyan = "\e[96m";

        const White = "\e[97m";
    }