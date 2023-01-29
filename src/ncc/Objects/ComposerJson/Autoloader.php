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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects\ComposerJson;

    class Autoloader
    {

        /**
         * @var NamespacePointer[]|null
         */
        public $Psr4;

        /**
         * @var NamespacePointer[]|null
         */
        public $Psr0;

        /**
         * @var string[]|null
         */
        public $Classmap;

        /**
         * @var string[]|null
         */
        public $Files;

        /**
         * @var string[]|null
         */
        public $ExcludeFromClassMap;

        /**
         * @param array $psr
         * @param mixed $pointer
         * @return array
         */
        private static function psrRestructure(array $psr, NamespacePointer $pointer): array
        {
            if (isset($psr[(string)$pointer->Namespace]))
            {
                if (!is_array($psr[(string)$pointer->Namespace]))
                {
                    $r = [
                        $psr[(string)$pointer->Namespace],
                        $pointer->Path
                    ];

                    $psr[(string)$pointer->Namespace] = $r;
                }
                elseif (!in_array($pointer->Path, $psr[(string)$pointer->Namespace]))
                {
                    $psr[(string)$pointer->Namespace][] = $pointer->Path;
                }
            }
            else
            {
                $psr[(string)$pointer->Namespace] = $pointer->Path;
            }
            return $psr;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $_psr4 = null;
            if($this->Psr4 !== null && count($this->Psr4) > 0)
            {
                $_psr4 = [];
                foreach($this->Psr4 as $_psr)
                    $_psr4 = self::psrRestructure($_psr4, $_psr);
            }

            $_psr0 = null;
            if($this->Psr0 !== null && count($this->Psr0) > 0)
            {
                $_psr0 = [];
                foreach($this->Psr0 as $_psr)
                    $_psr4 = self::psrRestructure($_psr0, $_psr);
            }

            return [
                'psr-4' => $_psr4,
                'psr-0' => $_psr0,
                'classmap' => $this->Classmap,
                'files' => $this->Files,
                'exclude-from-classmap' => $this->ExcludeFromClassMap,
            ];
        }

        /**
         * @param array $data
         * @return static
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['psr-4']))
            {
                $object->Psr4 = [];
                foreach($data['psr-4'] as $namespace => $path)
                {
                    if(is_array($path))
                    {
                        foreach($path as $datum)
                        {
                            $object->Psr4[] = new NamespacePointer($namespace, $datum);
                        }
                    }
                    else
                    {
                        $object->Psr4[] = new NamespacePointer($namespace, $path);
                    }
                }
            }

            if(isset($data['psr-0']))
            {
                $object->Psr0 = [];
                foreach($data['psr-0'] as $namespace => $path)
                {
                    if(is_array($path))
                    {
                        foreach($path as $datum)
                        {
                            $object->Psr0[] = new NamespacePointer($namespace, $datum);
                        }
                    }
                    else
                    {
                        $object->Psr0[] = new NamespacePointer($namespace, $path);
                    }
                }
            }

            if(isset($data['classmap']))
                $object->Classmap = $data['classmap'];

            if(isset($data['files']))
                $object->Files = $data['files'];

            if(isset($data['exclude-from-classmap']))
                $object->ExcludeFromClassMap = $data['exclude-from-classmap'];

            return $object;
        }
    }