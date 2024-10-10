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
    use ncc\Enums\SpecialConstants\GeneralConstants;
    use ncc\Enums\SpecialConstants\InstallConstants;
    use ncc\Enums\SpecialConstants\AssemblyConstants;
    use ncc\Enums\SpecialConstants\RuntimeConstants;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Utilities\Console;

    class ConstantCompiler
    {
        /**
         * Compiles all constants (Usually used during pre-compiling time)
         *
         * @param ProjectConfiguration $project_configuration
         * @param string|null $input
         * @return string
         */
        public static function compileConstants(ProjectConfiguration $project_configuration, ?string $input): string
        {
            $input = self::compileAssemblyConstants($input, $project_configuration->getAssembly());
            $input = self::compileBuildConstants($input);
            $input = self::compileDateTimeConstants($input, time());
            $input = self::compileRuntimeConstants($input);
            $input = self::compileGeneralConstants($input, $project_configuration);

            return $input;
        }

        public static function compileGeneralConstants(?string $input, ProjectConfiguration $project_configuration): ?string
        {
            if($input === null)
            {
                return null;
            }

            return str_replace(
                [
                    GeneralConstants::DEFAULT_BUILD_CONFIGURATION->value
                ],
                [
                    $project_configuration->getBuild()->getDefaultConfiguration()
                ],

                $input
            );
        }

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

            $input = str_replace(
                [
                    AssemblyConstants::ASSEMBLY_NAME->value,
                    AssemblyConstants::ASSEMBLY_PACKAGE->value,
                    AssemblyConstants::ASSEMBLY_VERSION->value,
                    AssemblyConstants::ASSEMBLY_UID->value,
                ],
                [
                    $assembly->getName(),
                    $assembly->getPackage(),
                    $assembly->getVersion(),
                    $assembly->getUuid()
                ], $input);

            if($assembly->getDescription() !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_DESCRIPTION->value, $assembly->getDescription(), $input);
            }

            if($assembly->getCompany() !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_COMPANY->value, $assembly->getCompany(), $input);
            }

            if($assembly->getProduct() !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_PRODUCT->value, $assembly->getProduct(), $input);
            }

            if($assembly->getCopyright() !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_COPYRIGHT->value, $assembly->getCopyright(), $input);
            }

            if($assembly->getTrademark() !== null)
            {
                $input = str_replace(AssemblyConstants::ASSEMBLY_TRADEMARK->value, $assembly->getTrademark(), $input);
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
                    BuildConstants::COMPILE_TIMESTAMP->value,
                    BuildConstants::NCC_BUILD_VERSION->value,
                    BuildConstants::NCC_BUILD_FLAGS->value,
                    BuildConstants::NCC_BUILD_BRANCH->value
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
         * @param InstallationPaths $installation_paths
         * @return string|null
         */
        public static function compileInstallConstants(?string $input, InstallationPaths $installation_paths): ?string
        {
            if($input === null)
            {
                return null;
            }

            return str_replace(
                [
                    InstallConstants::INSTALL_PATH->value,
                    InstallConstants::INSTALL_PATH_BIN->value,
                    InstallConstants::INSTALL_PATH_SRC->value,
                    InstallConstants::INSTALL_PATH_DATA->value
                ],
                [
                    $installation_paths->getInstallationpath(),
                    $installation_paths->getBinPath(),
                    $installation_paths->getSourcePath(),
                    $installation_paths->getDataPath()
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
                DateTimeConstants::d->value,
                DateTimeConstants::D->value,
                DateTimeConstants::j->value,
                DateTimeConstants::l->value,
                DateTimeConstants::N->value,
                DateTimeConstants::S->value,
                DateTimeConstants::w->value,
                DateTimeConstants::z->value,
                DateTimeConstants::W->value,
                DateTimeConstants::F->value,
                DateTimeConstants::m->value,
                DateTimeConstants::M->value,
                DateTimeConstants::n->value,
                DateTimeConstants::t->value,
                DateTimeConstants::L->value,
                DateTimeConstants::o->value,
                DateTimeConstants::Y->value,
                DateTimeConstants::y->value,
                DateTimeConstants::a->value,
                DateTimeConstants::A->value,
                DateTimeConstants::B->value,
                DateTimeConstants::g->value,
                DateTimeConstants::G->value,
                DateTimeConstants::h->value,
                DateTimeConstants::H->value,
                DateTimeConstants::i->value,
                DateTimeConstants::s->value,
                DateTimeConstants::c->value,
                DateTimeConstants::r->value,
                DateTimeConstants::u->value
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
                $input = str_replace(RuntimeConstants::CWD->value, getcwd(), $input);
            }
            else
            {
                Console::outWarning('Cannot compile RuntimeConstants::CWD, getcwd() is not available');
            }

            if(function_exists('getmypid'))
            {
                $input = str_replace(RuntimeConstants::PID->value, getmypid(), $input);
            }
            else
            {
                Console::outWarning('Cannot compile RuntimeConstants::PID, getmypid() is not available');
            }

            if(function_exists('getmyuid'))
            {
                $input = str_replace(RuntimeConstants::UID->value, getmyuid(), $input);
            }
            else
            {
                Console::outWarning('Cannot compile RuntimeConstants::UID, getmyuid() is not available');
            }

            if(function_exists('getmygid'))
            {
                $input = str_replace(RuntimeConstants::GID->value, getmygid(), $input);
            }
            else
            {
                Console::outWarning('Cannot compile RuntimeConstants::GID, getmygid() is not available');
            }

            if(function_exists('get_current_user'))
            {
                $input = str_replace(RuntimeConstants::USER->value, get_current_user(), $input);
            }
            else
            {
                Console::outWarning('Cannot compile RuntimeConstants::USER, get_current_user() is not available');
            }

            return $input;
        }
    }