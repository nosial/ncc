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

    namespace ncc\Objects\ProjectConfiguration;

    use Exception;
    use ncc\Enums\CompilerExtensions;
    use ncc\Enums\CompilerExtensionSupportedVersions;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\NotSupportedException;
    use ncc\Interfaces\BytecodeObjectInterface;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Compiler implements BytecodeObjectInterface
    {
        /**
         * The compiler extension that the project uses
         *
         * @var string
         */
        public $extension;

        /**
         * The minimum version that is supported
         *
         * @var string
         */
        public $minimum_version;

        /**
         * The maximum version that is supported
         *
         * @var string
         */
        public $maximum_version;

        /**
         * Validates the compiler object
         *
         * @param bool $throw_exception
         * @return bool
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if($this->extension === null)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException('The property \'extension\' must not be null.');
                }

                return False;
            }

            if($this->minimum_version === null)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException('The property \'minimum_version\' must not be null.');
                }

                return False;
            }

            if($this->maximum_version === null)
            {
                if($throw_exception)
                {
                    throw new ConfigurationException('The property \'maximum_version\' must not be null.');
                }

                return False;
            }

            try
            {
                if(VersionComparator::compareVersion($this->minimum_version, $this->maximum_version) === 1)
                {
                    if($throw_exception)
                    {
                        throw new ConfigurationException('The minimum version cannot be greater version number than the maximum version');
                    }

                    return False;
                }
            }
            catch (Exception $e)
            {
                throw new ConfigurationException('Version comparison failed: ' . $e->getMessage());
            }

            if(!in_array($this->extension, CompilerExtensions::ALL))
             {
                if($throw_exception)
                {
                    throw new NotSupportedException('The compiler extension \'' . $this->extension . '\' is not supported');
                }

                return False;
             }

            switch($this->extension)
            {
                case CompilerExtensions::PHP:
                    if(!in_array($this->maximum_version, CompilerExtensionSupportedVersions::PHP))
                    {
                        if($throw_exception)
                        {
                            throw new NotSupportedException('The MaximumVersion does not support version ' . $this->maximum_version . ' for the extension ' . $this->extension);
                        }
                        return False;
                    }

                    if(!in_array($this->minimum_version, CompilerExtensionSupportedVersions::PHP))
                    {
                        if($throw_exception)
                        {
                            throw new NotSupportedException('The MinimumVersion does not support version ' . $this->minimum_version . ' for the extension ' . $this->extension);
                        }
                        return False;
                    }
                    break;

                default:
                    throw new NotSupportedException('The compiler extension \'' . $this->extension . '\' is not supported');
            }

             return True;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode = false): array
        {
            $results = [];
            if($this->extension !== null && $this->extension !== '')
            {
                $results[($bytecode ? Functions::cbc('extension') : 'extension')] = $this->extension;
            }

            if($this->minimum_version !== null && $this->minimum_version !== '')
            {
                $results[($bytecode ? Functions::cbc('minimum_version') : 'minimum_version')] = $this->minimum_version;
            }

            if($this->maximum_version !== null && $this->maximum_version !== '')
            {
                $results[($bytecode ? Functions::cbc('maximum_version') : 'maximum_version')] = $this->maximum_version;
            }

            return $results;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): Compiler
        {
            $object = new self();

            $object->maximum_version = Functions::array_bc($data, 'maximum_version');
            $object->extension = Functions::array_bc($data, 'extension');
            $object->minimum_version = Functions::array_bc($data, 'minimum_version');
            
            return $object;
        }
    }