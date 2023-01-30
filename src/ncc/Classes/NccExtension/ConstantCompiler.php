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

    use ncc\Abstracts\SpecialConstants\BuildConstants;
    use ncc\Abstracts\SpecialConstants\DateTimeConstants;
    use ncc\Abstracts\SpecialConstants\InstallConstants;
    use ncc\Abstracts\SpecialConstants\AssemblyConstants;
    use ncc\Abstracts\SpecialConstants\RuntimeConstants;
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
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileAssemblyConstants(?string $input, Assembly $assembly): ?string
        {
            if($input == null)
                return null;

            if($assembly->Name !== null)
                $input = str_replace(AssemblyConstants::AssemblyName, $assembly->Name, $input);
            if($assembly->Package !== null)
                $input = str_replace(AssemblyConstants::AssemblyPackage, $assembly->Package, $input);
            if($assembly->Description !== null)
                $input = str_replace(AssemblyConstants::AssemblyDescription, $assembly->Description, $input);
            if($assembly->Company !== null)
                $input = str_replace(AssemblyConstants::AssemblyCompany, $assembly->Company, $input);
            if($assembly->Product !== null)
                $input = str_replace(AssemblyConstants::AssemblyProduct, $assembly->Product, $input);
            if($assembly->Copyright !== null)
                $input = str_replace(AssemblyConstants::AssemblyCopyright, $assembly->Copyright, $input);
            if($assembly->Trademark !== null)
                $input = str_replace(AssemblyConstants::AssemblyTrademark, $assembly->Trademark, $input);
            if($assembly->Version !== null)
                $input = str_replace(AssemblyConstants::AssemblyVersion, $assembly->Version, $input);
            if($assembly->UUID !== null)
                $input = str_replace(AssemblyConstants::AssemblyUid, $assembly->UUID, $input);

            return $input;
        }

        /**
         * Compiles build constants about the NCC build (Usually used during compiling time)
         *
         * @param string|null $input
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileBuildConstants(?string $input): ?string
        {
            if($input == null)
                return null;

            $input = str_replace(BuildConstants::CompileTimestamp, time(), $input);
            $input = str_replace(BuildConstants::NccBuildVersion, NCC_VERSION_NUMBER, $input);
            $input = str_replace(BuildConstants::NccBuildFlags, implode(' ', NCC_VERSION_FLAGS), $input);
            $input = str_replace(BuildConstants::NccBuildBranch, NCC_VERSION_BRANCH, $input);

            return $input;
        }

        /**
         * Compiles installation constants (Usually used during compiling time)
         *
         * @param string|null $input
         * @param InstallationPaths $installationPaths
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileInstallConstants(?string $input, InstallationPaths $installationPaths): ?string
        {
            if($input == null)
                return null;

            $input = str_replace(InstallConstants::InstallationPath, $installationPaths->getInstallationPath(), $input);
            $input = str_replace(InstallConstants::BinPath, $installationPaths->getBinPath(), $input);
            $input = str_replace(InstallConstants::SourcePath, $installationPaths->getSourcePath(), $input);
            $input = str_replace(InstallConstants::DataPath, $installationPaths->getDataPath(), $input);

            return $input;
        }

        /**
         * Compiles DateTime constants from a Unix Timestamp
         *
         * @param string|null $input
         * @param int $timestamp
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileDateTimeConstants(?string $input, int $timestamp): ?string
        {
            if($input == null)
                return null;

            $input = str_replace(DateTimeConstants::d, date('d', $timestamp), $input);
            $input = str_replace(DateTimeConstants::D, date('D', $timestamp), $input);
            $input = str_replace(DateTimeConstants::j, date('j', $timestamp), $input);
            $input = str_replace(DateTimeConstants::l, date('l', $timestamp), $input);
            $input = str_replace(DateTimeConstants::N, date('N', $timestamp), $input);
            $input = str_replace(DateTimeConstants::S, date('S', $timestamp), $input);
            $input = str_replace(DateTimeConstants::w, date('w', $timestamp), $input);
            $input = str_replace(DateTimeConstants::z, date('z', $timestamp), $input);
            $input = str_replace(DateTimeConstants::W, date('W', $timestamp), $input);
            $input = str_replace(DateTimeConstants::F, date('F', $timestamp), $input);
            $input = str_replace(DateTimeConstants::m, date('m', $timestamp), $input);
            $input = str_replace(DateTimeConstants::M, date('M', $timestamp), $input);
            $input = str_replace(DateTimeConstants::n, date('n', $timestamp), $input);
            $input = str_replace(DateTimeConstants::t, date('t', $timestamp), $input);
            $input = str_replace(DateTimeConstants::L, date('L', $timestamp), $input);
            $input = str_replace(DateTimeConstants::o, date('o', $timestamp), $input);
            $input = str_replace(DateTimeConstants::Y, date('Y', $timestamp), $input);
            $input = str_replace(DateTimeConstants::y, date('y', $timestamp), $input);
            $input = str_replace(DateTimeConstants::a, date('a', $timestamp), $input);
            $input = str_replace(DateTimeConstants::A, date('A', $timestamp), $input);
            $input = str_replace(DateTimeConstants::B, date('B', $timestamp), $input);
            $input = str_replace(DateTimeConstants::g, date('g', $timestamp), $input);
            $input = str_replace(DateTimeConstants::G, date('G', $timestamp), $input);
            $input = str_replace(DateTimeConstants::h, date('h', $timestamp), $input);
            $input = str_replace(DateTimeConstants::H, date('H', $timestamp), $input);
            $input = str_replace(DateTimeConstants::i, date('i', $timestamp), $input);
            $input = str_replace(DateTimeConstants::s, date('s', $timestamp), $input);
            $input = str_replace(DateTimeConstants::c, date('c', $timestamp), $input);
            $input = str_replace(DateTimeConstants::r, date('r', $timestamp), $input);
            $input = str_replace(DateTimeConstants::u, date('u', $timestamp), $input);

            return $input;
        }

        /**
         * @param string|null $input
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileRuntimeConstants(?string $input): ?string
        {
            if ($input == null)
                return null;

            if(function_exists('getcwd'))
                $input = str_replace(RuntimeConstants::CWD, getcwd(), $input);
            if(function_exists('getmypid'))
                $input = str_replace(RuntimeConstants::PID, getmypid(), $input);
            if(function_exists('getmyuid'))
                $input = str_replace(RuntimeConstants::UID, getmyuid(), $input);
            if(function_exists('getmygid'))
                $input = str_replace(RuntimeConstants::GID, getmygid(), $input);
            if(function_exists('get_current_user'))
                $input = str_replace(RuntimeConstants::User, get_current_user(), $input);

            return $input;
        }
    }