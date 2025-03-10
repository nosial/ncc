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
    use ncc\Classes\PackageWriter;
    use ncc\Enums\Flags\ComponentFlags;
    use ncc\Enums\Types\ComponentDataType;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Objects\Package\Component;
    use ncc\ThirdParty\nikic\PhpParser\NodeDumper;
    use ncc\ThirdParty\nikic\PhpParser\ParserFactory;
    use ncc\ThirdParty\nikic\PhpParser\PhpVersion;
    use ncc\Utilities\Base64;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;

    class NccCompiler extends \ncc\Classes\NccExtension\NccCompiler
    {
        /**
         * @param PackageWriter $package_writer
         * @param string $file_path
         * @return void
         * @throws IOException
         * @throws PathNotFoundException
         * @noinspection UnusedFunctionResultInspection
         */
        public function processComponent(PackageWriter $package_writer, string $file_path): void
        {
            $component_name = Functions::removeBasename($file_path, $this->getProjectManager()->getProjectPath());

            try
            {
                $stmts = ((new ParserFactory())->createForNewestSupportedVersion())->parse(IO::fread($file_path));
                $component = new Component($component_name, ZiProto::encode(Serializer::nodesToArray($stmts)), ComponentDataType::AST);
                $component->addFlag(ComponentFlags::PHP_AST->value);
                $pointer = $package_writer->addComponent($component);

                foreach(AstWalker::extractClasses($stmts) as $class)
                {
                    $package_writer->mapClass($class, (int)$pointer[0], (int)$pointer[1]);
                }

                return;
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('Failed to compile file "%s" with error "%s"', $file_path, $e->getMessage()));
            }

            $component = new Component($component_name, Base64::encode(IO::fread($file_path)), ComponentDataType::BASE64_ENCODED);
            $component->addFlag(ComponentFlags::PHP_B64->value);
            $package_writer->addComponent($component);
        }
    }