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

    namespace ncc\Objects;

    use ncc\Enums\ConsoleColors;
    use ncc\Utilities\Console;

    class CliHelpSection
    {
        /**
         * An array of parameters that are accepted to invoke the option
         *
         * @var array|null
         */
        private $parameters;

        /**
         * A description of the option
         *
         * @var string|null
         */
        private $description;

        /**
         * The default value of the option
         *
         * @var string|null
         */
        private $default;

        /**
         * Public Constructor
         *
         * @param array|null $parameters
         * @param string|null $description
         * @param string|null $default
         */
        public function __construct(?array $parameters=null, ?string $description=null, ?string $default=null)
        {
            $this->parameters = $parameters;
            $this->description = $description;
            $this->default = $default;
        }

        /**
         * @return array|null
         */
        public function getParameters(): ?array
        {
            return $this->parameters;
        }

        /**
         * @param array|null $parameters
         */
        public function setParameters(?array $parameters): void
        {
            $this->parameters = $parameters;
        }

        /**
         * @return string|null
         */
        public function getDescription(): ?string
        {
            return $this->description;
        }

        /**
         * @param string|null $description
         */
        public function setDescription(?string $description): void
        {
            $this->description = $description;
        }

        /**
         * @return string|null
         */
        public function getDefault(): ?string
        {
            return $this->default;
        }

        /**
         * @param string|null $default
         */
        public function setDefault(?string $default): void
        {
            $this->default = $default;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            return [
                'parameters' => $this->parameters,
                'description' => $this->description,
                'default' => $this->default
            ];
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return CliHelpSection
         */
        public static function fromArray(array $data): CliHelpSection
        {
            $object = new CliHelpSection();

            if(isset($data['parameters']))
            {
                $object->parameters = $data['parameters'];
            }

            if(isset($data['description']))
            {
                $object->description = $data['description'];
            }

            if(isset($data['default']))
            {
                $object->default = $data['default'];
            }

            return $object;
        }

        /**
         * Returns a string representation of the object
         *
         * @param int $param_padding
         * @param bool $basic
         * @return string
         */
        public function toString(int $param_padding=0, bool $basic=false): string
        {
            $out = [];

            if(count($this->parameters) > 0)
            {
                if($param_padding > 0)
                {
                    /** @noinspection PhpRedundantOptionalArgumentInspection */
                    $result = str_pad(implode(' ', $this->parameters), $param_padding, ' ', STR_PAD_RIGHT);

                    if(!$basic)
                    {
                        $result = Console::formatColor($result, ConsoleColors::GREEN);
                    }

                    $out[] .= $result;
                }
                elseif($basic)
                {
                    $out[] .= implode(' ', $this->parameters);
                }
                else
                {
                    $out[] .= Console::formatColor(implode(' ', $this->parameters), ConsoleColors::GREEN);
                }
            }

            if($this->description !== null)
            {
                $out[] = $this->description;
            }

            if($this->default !== null)
            {
                $out[] = '(Default: ' . $this->default . ')';
            }

            return implode(' ', $out);
        }
    }