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

    namespace ncc\Objects\Package;

    use Exception;
    use ncc\Classes\PhpExtension\AstWalker;
    use ncc\Enums\Flags\ComponentFlags;
    use ncc\Enums\Options\ComponentDecodeOptions;
    use ncc\Enums\Types\ComponentDataType;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\ThirdParty\nikic\PhpParser\PrettyPrinter\Standard;
    use ncc\Utilities\Functions;
    use RuntimeException;

    class Component implements BytecodeObjectInterface
    {
        /**
         * The name of the component or the file name of the component
         *
         * @var string
         */
        private $name;

        /**
         * Flags associated with the component created by the compiler extension
         *
         * @var array
         */
        private $flags;

        /**
         * The data type of the component
         *
         * @var string
         */
        private $data_type;

        /**
         * A sha1 hash checksum of the component, this will be compared against the data to determine
         * the integrity of the component to ensure that the component is not corrupted.
         *
         * @var string
         */
        private $checksum;

        /**
         * The raw data of the component, this is to be processed by the compiler extension
         *
         * @var string
         */
        private $data;

        /**
         * @param string $name
         * @param string $data
         * @param string $data_type
         */
        public function __construct(string $name, string $data, string $data_type=ComponentDataType::PLAIN)
        {
            $this->name = $name;
            $this->flags = [];
            $this->data_type = $data_type;
            $this->data = $data;
            $this->checksum = hash('sha1', $data, true);
        }

        /**
         * Validates the checksum of the component, returns false if the checksum or data is invalid or if the checksum
         * failed.
         *
         * @return bool
         */
        public function validateChecksum(): bool
        {
            return hash_equals($this->checksum, hash('sha1', $this->data, true));
        }

        /**
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * @return array
         */
        public function getFlags(): array
        {
            return $this->flags;
        }

        /**
         * @param array $flags
         */
        public function setFlags(array $flags): void
        {
            $this->flags = $flags;
        }

        /**
         * @param string $flag
         * @return void
         */
        public function addFlag(string $flag): void
        {
            $this->flags[] = $flag;
        }

        /**
         * @param string $flag
         * @return void
         */
        public function removeFlag(string $flag): void
        {
            $this->flags = array_filter($this->flags, static function($f) use ($flag)
            {
                return $f !== $flag;
            });
        }

        /**
         * @return string
         */
        public function getDataType(): string
        {
            return $this->data_type;
        }

        /**
         * @return string
         */
        public function getChecksum(): string
        {
            return $this->checksum;
        }

        /**
         * Returns the decoded data of the component, this will decode the data based on the data type and flags of the
         * component.
         *
         * @param array $options
         * @return string
         */
        public function getData(array $options=[]): string
        {
            switch($this->data_type)
            {
                case ComponentDataType::PLAIN:
                case ComponentDataType::BINARY:
                    return $this->data;

                case ComponentDataType::BASE64_ENCODED:
                    if(in_array(ComponentFlags::PHP_B64, $this->flags, true))
                    {
                        try
                        {
                            if(in_array(ComponentDecodeOptions::AS_FILE, $options, true))
                            {
                                return (new Standard())->prettyPrintFile(AstWalker::decodeRecursive(base64_decode($this->data)));
                            }

                            return (new Standard())->prettyPrint(AstWalker::decodeRecursive(base64_decode($this->data)));
                        }
                        catch(Exception $e)
                        {
                            throw new RuntimeException(sprintf('Failed to decode component %s with data type %s because the component is corrupted: %s', $this->name, ComponentFlags::PHP_B64, $e->getMessage()), $e->getCode(), $e);
                        }
                    }

                    return base64_decode($this->data);

                case ComponentDataType::AST:
                    if(in_array(ComponentFlags::PHP_AST, $this->flags, true))
                    {
                        try
                        {
                            if(in_array(ComponentDecodeOptions::AS_FILE, $options, true))
                            {
                                return (new Standard())->prettyPrintFile(AstWalker::decodeRecursive(ZiProto::decode($this->data)));
                            }

                            return (new Standard())->prettyPrint(AstWalker::decodeRecursive(ZiProto::decode($this->data)));
                        }
                        catch(Exception $e)
                        {
                            throw new RuntimeException(sprintf('Failed to decode component %s with data type %s because the component is corrupted: %s', $this->name, ComponentFlags::PHP_AST, $e->getMessage()), $e->getCode(), $e);
                        }
                    }

                    throw new RuntimeException(sprintf('Cannot decode component %s with data type %s because the component does not have a flag to decode it properly', $this->name, 'AST'));

                default:
                    throw new RuntimeException(sprintf('Unknown component data type "%s"', $this->data_type));
            }
        }

        /**
         * @param mixed $data
         * @param string $data_type
         */
        public function setData(mixed $data, string $data_type=ComponentDataType::PLAIN): void
        {
            $this->data = $data;
            $this->data_type = $data_type;
            $this->checksum = hash('sha1', $data, true);
        }

        /**
         * Returns an array representation of the component.
         *
         * @param bool $bytecode
         * @return array
         */
        public function toArray(bool $bytecode=false): array
        {
            return [
                ($bytecode ? Functions::cbc('name') : 'name') => $this->name,
                ($bytecode ? Functions::cbc('flags') : 'flags') => $this->flags,
                ($bytecode ? Functions::cbc('data_type') : 'data_type') => $this->data_type,
                ($bytecode ? Functions::cbc('checksum') : 'checksum') => $this->checksum,
                ($bytecode ? Functions::cbc('data') : 'data') => $this->data,
            ];
        }

        /**
         * Constructs a new object from an array representation
         *
         * @param array $data
         * @return Component
         * @throws ConfigurationException
         */
        public static function fromArray(array $data): Component
        {
            $name = Functions::array_bc($data, 'name');
            $component_data = Functions::array_bc($data, 'data');
            $data_type = Functions::array_bc($data, 'data_type') ?? ComponentDataType::PLAIN;

            if($name === null)
            {
                throw new ConfigurationException('The component name is missing');
            }

            if($component_data === null)
            {
                throw new ConfigurationException('The component data is missing');
            }

            $object = new self($name, $component_data, $data_type);

            $object->flags = Functions::array_bc($data, 'flags');
            $object->checksum = Functions::array_bc($data, 'checksum');

            return $object;
        }
    }