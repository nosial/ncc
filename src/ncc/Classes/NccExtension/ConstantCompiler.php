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

namespace ncc\Classes\NccExtension;

    use ncc\Enums\SpecialConstants\BuildConstants;
    use ncc\Enums\SpecialConstants\DateTimeConstants;
    use ncc\Enums\SpecialConstants\InstallConstants;
    use ncc\Enums\SpecialConstants\AssemblyConstants;
    use ncc\Enums\SpecialConstants\RuntimeConstants;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\ProjectConfiguration\Assembly;

    class ConstantCompiler
    {
        /**
         * Compiles assembly constants about the project (Usually used during compiling time)
         *
         * @param string|null $input
         * @param Assembly $assembly
         * @return string|null
         */
        public static function compileAssemblyConstants(?string $input, Assembly $assembly): ?string
        {
            if($input === null)
            {
                return null;
            }

            if($assembly->name !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_NAME, $assembly->name, $input);
            }

            if($assembly->package !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_PACKAGE, $assembly->package, $input);
            }

            if($assembly->description !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_DESCRIPTION, $assembly->description, $input);
            }

            if($assembly->company !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_COMPANY, $assembly->company, $input);
            }

            if($assembly->product !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_PRODUCT, $assembly->product, $input);
            }

            if($assembly->copyright !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_COPYRIGHT, $assembly->copyright, $input);
            }

            if($assembly->trademark !== null)
            {
                $input =str_replace(AssemblyConstants::ASSEMBLY_TRADEMARK, $assembly->trademark, $input);
            }

            if($assembly->version !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_VERSION, $assembly->version, $input);
            }

            if($assembly->uuid !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_UID, $assembly->uuid, $input);
            }

            return $input;
        }

        /**
         * Compiles build constants about the NCC build (Usually used during compiling time)
         *
         * @param string|null $input
         * @return string|null
         */
        public static function compileBuildConstants(?string $input): ?string
        {
            if($input === null)
            {
                return null;
            }

            return str_replace(
                [
                    BuildConstants::COMPILE_TIMESTAMP,
                    BuildConstants::NCC_BUILD_VERSION,
                    BuildConstants::NCC_BUILD_FLAGS,
                    BuildConstants::NCC_BUILD_BRANCH
                ],
                [
                    time(),
                    NCC_VERSION_NUMBER,
                    implode(' ', NCC_VERSION_FLAGS), NCC_VERSION_BRANCH
                ], $input
            );
        }

        /**
         * Compiles installation constants (Usually used during compiling time)
         *
         * @param string|null $input
         * @param InstallationPaths $installationPaths
         * @return string|null
         */
        public static function compileInstallConstants(?string $input, InstallationPaths $installationPaths): ?string
        {
            if($input === null)
            {
                return null;
            }

            return str_replace(
                [
                    InstallConstants::INSTALL_PATH,
                    InstallConstants::INSTALL_PATH_BIN,
                    InstallConstants::INSTALL_PATH_SRC,
                    InstallConstants::INSTALL_PATH_DATA
                ],
                [
                    $installationPaths->getInstallationPath(),
                    $installationPaths->getBinPath(),
                    $installationPaths->getSourcePath(),
                    $installationPaths->getDataPath()
                ], $input);
        }

        /**
         * Compiles DateTime constants from a Unix Timestamp
         *
         * @param string|null $input
         * @param int $timestamp
         * @return string|null
         */
        public static function compileDateTimeConstants(?string $input, int $timestamp): ?string
        {
            if($input === null)
            {
                return null;
            }

            return str_replace([
                DateTimeConstants::d,
                DateTimeConstants::D,
                DateTimeConstants::j,
                DateTimeConstants::l,
                DateTimeConstants::N,
                DateTimeConstants::S,
                DateTimeConstants::w,
                DateTimeConstants::z,
                DateTimeConstants::W,
                DateTimeConstants::F,
                DateTimeConstants::m,
                DateTimeConstants::M,
                DateTimeConstants::n,
                DateTimeConstants::t,
                DateTimeConstants::L,
                DateTimeConstants::o,
                DateTimeConstants::Y,
                DateTimeConstants::y,
                DateTimeConstants::a,
                DateTimeConstants::A,
                DateTimeConstants::B,
                DateTimeConstants::g,
                DateTimeConstants::G,
                DateTimeConstants::h,
                DateTimeConstants::H,
                DateTimeConstants::i,
                DateTimeConstants::s,
                DateTimeConstants::c,
                DateTimeConstants::r,
                DateTimeConstants::u
            ],
            [
                date('d', $timestamp),
                date('D', $timestamp),
                date('j', $timestamp),
                date('l', $timestamp),
                date('N', $timestamp),
                date('S', $timestamp),
                date('w', $timestamp),
                date('z', $timestamp),
                date('W', $timestamp),
                date('F', $timestamp),
                date('m', $timestamp),
                date('M', $timestamp),
                date('n', $timestamp),
                date('t', $timestamp),
                date('L', $timestamp),
                date('o', $timestamp),
                date('Y', $timestamp),
                date('y', $timestamp),
                date('a', $timestamp),
                date('A', $timestamp),
                date('B', $timestamp),
                date('g', $timestamp),
                date('G', $timestamp),
                date('h', $timestamp),
                date('H', $timestamp),
                date('i', $timestamp),
                date('s', $timestamp),
                date('c', $timestamp),
                date('r', $timestamp),
                date('u', $timestamp)
            ], $input);

        }

        /**
         * @param string|null $input
         * @return string|null
         */
        public static function compileRuntimeConstants(?string $input): ?string
        {
            if ($input === null)
            {
                return null;
            }

            if(function_exists('getcwd'))
            {
                $input = str_replace(RuntimeConstants::CWD, getcwd(), $input);
            }

            if(function_exists('getmypid'))
            {
                $input = str_replace(RuntimeConstants::PID, getmypid(), $input);
            }

            if(function_exists('getmyuid'))
            {
                $input = str_replace(RuntimeConstants::UID, getmyuid(), $input);
            }

            if(function_exists('getmygid'))
            {
                $input = str_replace(RuntimeConstants::GID, getmygid(), $input);
            }

            if(function_exists('get_current_user'))
            {
                $input = str_replace(RuntimeConstants::USER, get_current_user(), $input);
            }

            return $input;
        }
    }