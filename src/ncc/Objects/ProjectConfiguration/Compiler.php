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
    use ncc\Exceptions\InvalidPropertyValueException;
    use ncc\Exceptions\InvalidVersionConfigurationException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedExtensionVersionException;
    use ncc\ThirdParty\jelix\Version\VersionComparator;
    use ncc\Utilities\Functions;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    class Compiler
    {
        /**
         * The compiler extension that the project uses
         *
         * @var string
         */
        public $Extension;

        /**
         * The minimum version that is supported
         *
         * @var string
         */
        public $MinimumVersion;

        /**
         * The maximum version that is supported
         *
         * @var string
         */
        public $MaximumVersion;

        /**
         * Validates the compiler object
         *
         * @param bool $throw_exception
         * @return bool
         * @throws InvalidPropertyValueException
         * @throws RuntimeException
         * @throws UnsupportedCompilerExtensionException
         * @throws UnsupportedExtensionVersionException
         */
        public function validate(bool $throw_exception=True): bool
        {
            if($this->Extension == null)
            {
                if($throw_exception)
                    throw new InvalidPropertyValueException('The property \'extension\' must not be null.');
                return False;
            }

            if($this->MinimumVersion == null)
            {
                if($throw_exception)
                    throw new InvalidPropertyValueException('The property \'minimum_version\' must not be null.');

                return False;
            }

            if($this->MaximumVersion == null)
            {
                if($throw_exception)
                    throw new InvalidPropertyValueException('The property \'maximum_version\' must not be null.');
                return False;
            }

            try
            {
                if(VersionComparator::compareVersion($this->MinimumVersion, $this->MaximumVersion) == 1)
                {
                    if($throw_exception)
                        throw new InvalidVersionConfigurationException('The minimum version cannot be greater version number than the maximum version');
                    return False;
                }
            }
            catch (Exception $e)
            {
                throw new RuntimeException('Version comparison failed: ' . $e->getMessage());
            }

            if(!in_array($this->Extension, CompilerExtensions::ALL))
             {
                if($throw_exception)
                    throw new UnsupportedCompilerExtensionException('The compiler extension \'' . $this->Extension . '\' is not supported');
                return False;
             }

            switch($this->Extension)
            {
                case CompilerExtensions::PHP:
                    if(!in_array($this->MaximumVersion, CompilerExtensionSupportedVersions::PHP))
                    {
                        if($throw_exception)
                            throw new UnsupportedExtensionVersionException('The MaximumVersion does not support version ' . $this->MaximumVersion . ' for the extension ' . $this->Extension);
                        return False;
                    }

                    if(!in_array($this->MinimumVersion, CompilerExtensionSupportedVersions::PHP))
                    {
                        if($throw_exception)
                            throw new UnsupportedExtensionVersionException('The MinimumVersion does not support version ' . $this->MinimumVersion . ' for the extension ' . $this->Extension);
                        return False;
                    }
                    break;

                default:
                    throw new UnsupportedCompilerExtensionException('The compiler extension \'' . $this->Extension . '\' is not supported');
            }

             return True;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $return_results = [];
            if($this->Extension !== null && strlen($this->Extension) > 0)
                $return_results['extension'] = $this->Extension;

            if($this->MinimumVersion !== null && strlen($this->MinimumVersion) > 0)
                $return_results['minimum_version'] = $this->MinimumVersion;

            if($this->MaximumVersion !== null && strlen($this->MaximumVersion) > 0)
                $return_results['maximum_version'] = $this->MaximumVersion;

            return $return_results;
        }

        /**
         * Constructs object from an array representation
         *
         * @param array $data
         * @return Compiler
         */
        public static function fromArray(array $data): Compiler
        {
            $CompilerObject = new Compiler();

            $CompilerObject->MaximumVersion = Functions::array_bc($data, 'maximum_version');
            $CompilerObject->Extension = Functions::array_bc($data, 'extension');
            $CompilerObject->MinimumVersion = Functions::array_bc($data, 'minimum_version');
            
            return $CompilerObject;
        }
    }