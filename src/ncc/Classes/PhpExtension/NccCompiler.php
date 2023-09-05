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
    use ncc\Enums\ComponentDataType;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Objects\Package\Component;
    use ncc\ThirdParty\nikic\PhpParser\ParserFactory;
    use ncc\Utilities\Base64;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\ZiProto\ZiProto;

    class NccCompiler extends \ncc\Classes\NccExtension\NccCompiler
    {
        /**
         * @param string $file_path
         * @return Component
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function buildComponent(string $file_path): Component
        {
            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

            try
            {
                $encoded = json_encode($parser->parse(IO::fread($file_path)), JSON_THROW_ON_ERROR);
                return new Component(Functions::removeBasename($file_path), ZiProto::encode(json_decode($encoded, true, 512, JSON_THROW_ON_ERROR)), ComponentDataType::AST);
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('Failed to compile file "%s" with error "%s"', $file_path, $e->getMessage()));
            }

            return new Component(
                Functions::removeBasename($file_path),
                Base64::encode(IO::fread($file_path)), ComponentDataType::BASE64_ENCODED
            );
        }

    }