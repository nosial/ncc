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

    use ncc\Interfaces\SerializableObjectInterface;

    class Autoloader implements SerializableObjectInterface
    {

        /**
         * @var NamespacePointer[]|null
         */
        private $psr_4;

        /**
         * @var NamespacePointer[]|null
         */
        private $psr_0;

        /**
         * @var string[]|null
         */
        private $class_map;

        /**
         * @var string[]|null
         */
        private $files;

        /**
         * @var string[]|null
         */
        private $exclude_from_class_map;

        /**
         * @return NamespacePointer[]|null
         */
        public function getPsr4(): ?array
        {
            return $this->psr_4;
        }

        /**
         * @return NamespacePointer[]|null
         */
        public function getPsr0(): ?array
        {
            return $this->psr_0;
        }

        /**
         * @return string[]|null
         */
        public function getClassMap(): ?array
        {
            return $this->class_map;
        }

        /**
         * @return string[]|null
         */
        public function getFiles(): ?array
        {
            return $this->files;
        }

        /**
         * @return string[]|null
         */
        public function getExcludeFromClassMap(): ?array
        {
            return $this->exclude_from_class_map;
        }

        /**
         * @param array $psr
         * @param mixed $pointer
         * @return array
         * @noinspection PhpUnusedPrivateMethodInspection
         */
        private static function psrRestructure(array $psr, NamespacePointer $pointer): array
        {
            if (isset($psr[(string)$pointer->getNamespace()]))
            {
                if (!is_array($psr[(string)$pointer->getNamespace()]))
                {
                    $r = [
                        $psr[(string)$pointer->getNamespace()],
                        $pointer->getPath()
                    ];

                    $psr[(string)$pointer->getNamespace()] = $r;
                }
                elseif (!in_array($pointer->getPath(), $psr[(string)$pointer->getNamespace()], true))
                {
                    $psr[(string)$pointer->getNamespace()][] = $pointer->getPath();
                }
            }
            else
            {
                $psr[(string)$pointer->getNamespace()] = $pointer->getPath();
            }
            return $psr;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $_psr4 = null;
            if($this->psr_4 !== null && count($this->psr_4) > 0)
            {
                $_psr4 = array_map(static function(NamespacePointer $pointer) {
                    return $pointer->toArray();
                }, $this->psr_4);
            }

            $_psr0 = null;
            if($this->psr_0 !== null && count($this->psr_0) > 0)
            {
                $_psr0 = array_map(static function(NamespacePointer $pointer) {
                    return $pointer->toArray();
                }, $this->psr_0);
            }

            return [
                'psr-4' => $_psr4,
                'psr-0' => $_psr0,
                'classmap' => $this->class_map,
                'files' => $this->files,
                'exclude-from-classmap' => $this->exclude_from_class_map,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Autoloader
        {
            $object = new self();

            if(isset($data['psr-4']))
            {
                $object->psr_4 = [];
                foreach($data['psr-4'] as $namespace => $path)
                {
                    if(is_array($path))
                    {
                        foreach($path as $datum)
                        {
                            $object->psr_4[] = new NamespacePointer($namespace, $datum);
                        }
                    }
                    else
                    {
                        $object->psr_4[] = new NamespacePointer($namespace, $path);
                    }
                }
            }

            if(isset($data['psr-0']))
            {
                $object->psr_0 = [];
                foreach($data['psr-0'] as $namespace => $path)
                {
                    if(is_array($path))
                    {
                        foreach($path as $datum)
                        {
                            $object->psr_0[] = new NamespacePointer($namespace, $datum);
                        }
                    }
                    else
                    {
                        $object->psr_0[] = new NamespacePointer($namespace, $path);
                    }
                }
            }

            if(isset($data['classmap']))
            {
                $object->class_map = $data['classmap'];
            }

            if(isset($data['files']))
            {
                $object->files = $data['files'];
            }

            if(isset($data['exclude-from-classmap']))
            {
                $object->exclude_from_class_map = $data['exclude-from-classmap'];
            }

            return $object;
        }
    }