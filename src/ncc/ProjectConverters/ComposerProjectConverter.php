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

    namespace ncc\ProjectConverters;

    use ncc\Abstracts\AbstractProjectConverter;
    use ncc\Classes\IO;
    use ncc\Exceptions\IOException;
    use ncc\Objects\Project;

    class ComposerProjectConverter extends AbstractProjectConverter
    {
        public function convert(string $filePath): Project
        {
            $content = IO::readFile($filePath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE)
            {
                throw new IOException('Failed to parse JSON: ' . json_last_error_msg());
            }

            $project = new Project();
        }

        private function generateAssembly(array $composerData): Project\Assembly
        {
            $assembly = new Project\Assembly();

            // Description
            if(isset($composerData['description']))
            {
                $assembly->setDescription($composerData['description']);
            }

            // Homepage
            if(isset($composerData['homepage']))
            {
                $assembly->setUrl($composerData['homepage']);
            }

            // Authors
            if(isset($composerData['authors']) && count($composerData['authors']) > 0)
            {
                if(isset($composerData['authors']['name']))
                {
                    $assembly->setAuthor(sprintf("%s %s%s",
                        $composerData['authors']['name'],
                        ($composerData['authors']['email'] ?' <' . $composerData['authors']['email'] . '>' : ''),
                        ($composerData['authors']['homepage'] ? ' (' . $composerData['authors']['homepage'] . ')' : '')
                    ));
                }
                else
                {
                    $authorString = (string)null;
                    foreach($composerData['authors'] as $author)
                    {
                        if(isset($authorString[0]))
                        {
                            $authorString .= ', ';
                        }

                        $authorString .= sprintf("%s %s%s",
                            $author['name'],
                            (isset($author['email']) ? ' <' . $author['email'] . '>' : ''),
                            (isset($author['homepage']) ? ' (' . $author['homepage'] . ')' : '')
                        );
                    }

                    $assembly->setAuthor($authorString);
                }
            }

            // License
            if(isset($composerData['license']))
            {
                $assembly->setLicense($composerData['license']);
            }


        }

        private function generatePackageName(string $composerPackageName): string
        {
            return sprintf("%s.%s.%s",
                'com',
                str_replace('-', '', explode('/', $composerPackageName)[0]),
                str_replace('-', '', explode('/', $composerPackageName)[1])
            );
        }
    }