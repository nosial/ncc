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

    namespace ncc\Classes\PhpExtension;

    use Exception;
    use ncc\Enums\Options\RuntimeImportOptions;
    use ncc\Classes\NccExtension\ConstantCompiler;
    use ncc\Exceptions\ImportException;
    use ncc\Exceptions\IntegrityException;
    use ncc\Interfaces\RuntimeInterface;
    use ncc\Objects\PackageLock\VersionEntry;
    use ncc\Objects\ProjectConfiguration\Assembly;
    use ncc\Runtime\Constants;
    use ncc\Utilities\IO;
    use ncc\ZiProto\ZiProto;

    class PhpRuntime implements RuntimeInterface
    {

        /**
         * Attempts to import a PHP package
         *
         * @param VersionEntry $versionEntry
         * @param array $options
         * @return bool
         * @throws ImportException
         */
        public static function import(VersionEntry $versionEntry, array $options=[]): bool
        {
            $autoload_path = $versionEntry->getInstallPaths()->getBinPath() . DIRECTORY_SEPARATOR . 'autoload.php';
            $static_files = $versionEntry->getInstallPaths()->getBinPath() . DIRECTORY_SEPARATOR . 'static_autoload.bin';
            $constants_path = $versionEntry->getInstallPaths()->getDataPath() . DIRECTORY_SEPARATOR . 'const';
            $assembly_path = $versionEntry->getInstallPaths()->getDataPath() . DIRECTORY_SEPARATOR . 'assembly';

            if(!file_exists($assembly_path))
            {
                throw new ImportException('Cannot locate assembly file \'' . $assembly_path . '\'');
            }

            try
            {
                $assembly_content = ZiProto::decode(IO::fread($assembly_path));
                $assembly = Assembly::fromArray($assembly_content);
            }
            catch(Exception $e)
            {
                throw new ImportException('Failed to load assembly file \'' . $assembly_path . '\': ' . $e->getMessage());
            }

            if(file_exists($constants_path))
            {
                try
                {
                    $constants = ZiProto::decode(IO::fread($constants_path));
                }
                catch(Exception $e)
                {
                    throw new ImportException('Failed to load constants file \'' . $constants_path . '\': ' . $e->getMessage());
                }

                foreach($constants as $name => $value)
                {
                    $value = ConstantCompiler::compileRuntimeConstants($value);

                    try
                    {
                        Constants::register($assembly->getPackage(), $name, $value, true);
                    }
                    catch (IntegrityException $e)
                    {
                        trigger_error('Cannot set constant \'' . $name . '\', ' . $e->getMessage(), E_USER_WARNING);
                    }
                }
            }

            if(file_exists($autoload_path) && !in_array(RuntimeImportOptions::IMPORT_AUTOLOADER, $options, true))
            {
                require_once($autoload_path);
            }

            if(file_exists($static_files) && !in_array(RuntimeImportOptions::IMPORT_STATIC_FILES, $options, true))
            {
                try
                {
                    $static_files = ZiProto::decode(IO::fread($static_files));
                    foreach($static_files as $file)
                    {
                        require_once($file);
                    }
                }
                catch(Exception $e)
                {
                    throw new ImportException('Failed to load static files: ' . $e->getMessage(), $e);
                }

            }

            return !(!file_exists($autoload_path) && !file_exists($static_files));
        }
    }