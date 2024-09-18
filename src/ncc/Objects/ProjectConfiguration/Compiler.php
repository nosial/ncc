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

    /** @noinspection PhpUnused */
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
     * @copyright Copyright (C) 2022-2023. Nosial - All Rights Reserved.
     */
    class Compiler implements BytecodeObjectInterface
    {
        /**
         * The compiler extension that the project uses
         *
         * @var CompilerExtensions
         */
        private CompilerExtensions $extension;

        /**
         * The minimum version that is supported
         *
         * @var string|null
         */
        private ?string $minimum_version;

        /**
         * The maximum version that is supported
         *
         * @var string|null
         */
        private ?string $maximum_version;

        /**
         * Compiler constructor.
         *
         * @param CompilerExtensions $extension
         * @param string|null $minimum_version
         * @param string|null $maximum_version
         * @throws NotSupportedException
         */
        public function __construct(CompilerExtensions $extension, ?string $minimum_version=null, ?string $maximum_version=null)
        {
            /** @noinspection DegradedSwitchInspection */
            switch($extension)
            {
                case CompilerExtensions::PHP:

                    if($minimum_version === null)
                    {
                        $minimum_version = CompilerExtensionSupportedVersions::PHP[0];
                    }

                    if($maximum_version === null)
                    {
                        $maximum_version = CompilerExtensionSupportedVersions::PHP[count(CompilerExtensionSupportedVersions::PHP) - 1];
                    }

                    break;

                default:
                    throw new NotSupportedException(sprintf('The compiler extension \'%s\' is not supported in ncc', $extension->value));
            }

            $this->extension = $extension;
            $this->minimum_version = $minimum_version;
            $this->maximum_version = $maximum_version;
        }

        /**
         * @return CompilerExtensions
         */
        public function getExtension(): CompilerExtensions
        {
            // TODO: Update usages from here
            return $this->extension;
        }

        /**
         * @param CompilerExtensions $extension
         */
        public function setExtension(CompilerExtensions $extension): void
        {
            $this->extension = $extension;
        }

        /**
         * @return string
         */
        public function getMinimumVersion(): string
        {
            return $this->minimum_version;
        }

        /**
         * @param string $minimum_version
         */
        public function setMinimumVersion(string $minimum_version): void
        {
            $this->minimum_version = $minimum_version;
        }

        /**
         * @return string
         */
        public function getMaximumVersion(): string
        {
            return $this->maximum_version;
        }

        /**
         * @param string $maximum_version
         */
        public function setMaximumVersion(string $maximum_version): void
        {
            $this->maximum_version = $maximum_version;
        }

        /**
         * Validates the compiler object
         *
         * @return bool
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public function validate(): bool
        {
            if($this->minimum_version === null)
            {
                throw new ConfigurationException('The property \'minimum_version\' must not be null.');
            }

            if($this->maximum_version === null)
            {
                throw new ConfigurationException('The property \'maximum_version\' must not be null.');
            }

            try
            {
                if(VersionComparator::compareVersion($this->minimum_version, $this->maximum_version) === 1)
                {
                    throw new ConfigurationException('The minimum version cannot be greater version number than the maximum version');
                }
            }
            catch (Exception $e)
            {
                throw new ConfigurationException('Version comparison failed: ' . $e->getMessage());
            }

            if(!in_array($this->extension, CompilerExtensions::cases(), true))
            {
                throw new NotSupportedException('The compiler extension \'' . $this->extension->value . '\' is not supported');
            }

            /** @noinspection DegradedSwitchInspection */
            switch($this->extension)
            {
                case CompilerExtensions::PHP:

                    if(!in_array($this->maximum_version, CompilerExtensionSupportedVersions::PHP, true))
                    {
                        throw new NotSupportedException('The property "project.compiler.maximum_version" does not support version ' . $this->maximum_version . ' for the extension ' . $this->extension->value);

                    }

                    if(!in_array($this->minimum_version, CompilerExtensionSupportedVersions::PHP, true))
                    {
                        throw new NotSupportedException('The property "project.compiler.minimum_version" does not support version ' . $this->minimum_version . ' for the extension ' . $this->extension->value);
                    }

                    break;

                default:
                    throw new NotSupportedException('The compiler extension "' . $this->extension->value . '" is not supported by ncc');
            }

             return True;
        }

        /**
         * @inheritDoc
         */
        public function toArray(bool $bytecode = false): array
        {
            $results = [];
            $results[($bytecode ? Functions::cbc('extension') : 'extension')] = $this->extension->value;

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
         * @param array $data
         * @return Compiler
         * @throws ConfigurationException
         * @throws NotSupportedException
         */
        public static function fromArray(array $data): Compiler
        {
            if(Functions::array_bc($data, 'extension') === null)
            {
                throw new ConfigurationException('The property \'project.compiler.extension\' must not be null.');
            }

            return new self(Functions::array_bc($data, 'extension'),
                Functions::array_bc($data, 'maximum_version'),
                Functions::array_bc($data, 'minimum_version')
            );
        }
    }