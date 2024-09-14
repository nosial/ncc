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
    use InvalidArgumentException;
    use ncc\Classes\PhpExtension\AstWalker;
    use ncc\Enums\Flags\ComponentFlags;
    use ncc\Enums\Options\ComponentDecodeOptions;
    use ncc\Enums\Types\ComponentDataType;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\OperationException;
    use ncc\Extensions\ZiProto\ZiProto;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\ThirdParty\nikic\PhpParser\PrettyPrinter\Standard;
    use ncc\Utilities\Functions;

    class Component implements BytecodeObjectInterface
    {
        /**
         * @var string
         */
        private $name;

        /**
         * @var array
         */
        private $flags;

        /**
         * @var string
         */
        private $data_type;

        /**
         * @var string
         */
        private $data;

        /**
         * Component constructor.
         *
         * @param string $name
         * @param string $data
         * @param string $data_type
         */
        // TODO: $data_type Can be a enum case
        public function __construct(string $name, string $data, string $data_type=ComponentDataType::PLAIN->value)
        {
            $this->name = $name;
            $this->flags = [];
            $this->data_type = $data_type;
            $this->data = $data;
        }

        /**
         * Returns the name of the component
         *
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Sets the name of the component
         *
         * @param string $name
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * Returns an array of flags associated with the component
         *
         * @return array
         */
        public function getFlags(): array
        {
            return $this->flags;
        }

        /**
         * Replaces the current array of flags with the new array of flags
         *
         * @param array $flags
         */
        public function setFlags(array $flags): void
        {
            $this->flags = $flags;
        }

        /**
         * Appends a new flag to the component if it does not exist
         *
         * @param string $flag
         * @return void
         */
        public function addFlag(string $flag): void
        {
            if(in_array($flag, $this->flags, true))
            {
                return;
            }

            $this->flags[] = $flag;
        }

        /**
         * Removes a flag from the component if it exists
         *
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
         * Returns the data type of the component
         *
         * @return string
         * @see ComponentDataType
         */
        public function getDataType(): string
        {
            return $this->data_type;
        }

        /**
         * Returns the decoded data of the component, this will decode the data based on the data type and flags of the
         * component.
         *
         * @param array $options
         * @return string
         * @throws OperationException
         */
        public function getData(array $options=[]): string
        {
            switch($this->data_type)
            {
                case ComponentDataType::PLAIN->value:
                case ComponentDataType::BINARY->value:
                    return $this->data;

                case ComponentDataType::BASE64_ENCODED->value:
                    if(in_array(ComponentFlags::PHP_B64, $this->flags, true))
                    {
                        try
                        {
                            if(in_array(ComponentDecodeOptions::AS_FILE->value, $options, true))
                            {
                                return (new Standard())->prettyPrintFile(AstWalker::decodeRecursive(base64_decode($this->data)));
                            }

                            return (new Standard())->prettyPrint(AstWalker::decodeRecursive(base64_decode($this->data)));
                        }
                        catch(Exception $e)
                        {
                            throw new OperationException(sprintf('Failed to decode component %s with data type %s because the component is corrupted: %s', $this->name, ComponentFlags::PHP_B64, $e->getMessage()), $e->getCode(), $e);
                        }
                    }

                    return base64_decode($this->data);

                case ComponentDataType::AST->value:
                    if(in_array(ComponentFlags::PHP_AST, $this->flags, true))
                    {
                        try
                        {
                            if(in_array(ComponentDecodeOptions::AS_FILE->value, $options, true))
                            {
                                return (new Standard())->prettyPrintFile(AstWalker::decodeRecursive(ZiProto::decode($this->data)));
                            }

                            return (new Standard())->prettyPrint(AstWalker::decodeRecursive(ZiProto::decode($this->data)));
                        }
                        catch(Exception $e)
                        {
                            throw new OperationException(sprintf('Failed to decode component %s with data type %s because the component is corrupted: %s', $this->name, ComponentFlags::PHP_AST, $e->getMessage()), $e->getCode(), $e);
                        }
                    }

                    throw new OperationException(sprintf('Cannot decode component %s with data type %s because the component does not have a flag to decode it properly. Got: %s', $this->name, implode(' ', $this->flags), 'AST'));

                default:
                    throw new InvalidArgumentException(sprintf('Unknown component data type "%s"', $this->data_type));
            }
        }

        /**
         * Sets the data of the component
         *
         * @param mixed $data
         * @param string $data_type
         */
        // TODO: $data_type can be a direct enum case
        public function setData(mixed $data, string $data_type=ComponentDataType::PLAIN->value): void
        {
            $data_type = strtolower($data_type);

            // TODO: Update this, not a proper use of the cases() method
            if(!in_array($data_type, ComponentDataType::cases(), true))
            {
                throw new InvalidArgumentException(sprintf('Unknown component data type "%s"', $data_type));
            }

            $this->data = $data;
            $this->data_type = $data_type;
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
            $data_type = Functions::array_bc($data, 'data_type') ?? ComponentDataType::PLAIN->value;

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

            return $object;
        }
    }