<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Objects;

    use JsonException;
    use ncc\Exceptions\OperationException;
    use ncc\Libraries\fslib\IO;
    use ncc\Libraries\fslib\IOException;
    use ncc\Objects\Package\PackageCacheData;

    class FrankenPhpBuildState
    {
        public const int STATE_VERSION = 1;
        public const string FILE_NAME = 'ncc_build_state.dat';

        private int $version;
        private string $baseFingerprint;
        private string $libphpFingerprint;
        private string $phpVersion;
        private string $frankenPhpVersion;

        /**
         * FrankenPhpBuildState constructor
         *
         * @param string $baseFingerprint The fingerprint of the inputs libphp.a was built from
         * @param string $libphpFingerprint The stat fingerprint of libphp.a after the build
         * @param string $phpVersion The PHP version libphp.a was built from, eg; 8.4.26
         * @param string $frankenPhpVersion The FrankenPHP version that was built, eg; 1.13.0
         */
        public function __construct(string $baseFingerprint, string $libphpFingerprint, string $phpVersion, string $frankenPhpVersion)
        {
            $this->version = self::STATE_VERSION;
            $this->baseFingerprint = $baseFingerprint;
            $this->libphpFingerprint = $libphpFingerprint;
            $this->phpVersion = $phpVersion;
            $this->frankenPhpVersion = $frankenPhpVersion;
        }

        /**
         * Returns the version of the state format
         *
         * @return int
         */
        public function getVersion(): int
        {
            return $this->version;
        }

        /**
         * Returns the fingerprint of the inputs libphp.a was built from
         *
         * @return string
         */
        public function getBaseFingerprint(): string
        {
            return $this->baseFingerprint;
        }

        /**
         * Returns the stat fingerprint of libphp.a after the build
         *
         * @return string
         */
        public function getLibphpFingerprint(): string
        {
            return $this->libphpFingerprint;
        }

        /**
         * Returns the PHP version libphp.a was built from
         *
         * @return string
         */
        public function getPhpVersion(): string
        {
            return $this->phpVersion;
        }

        /**
         * Returns the FrankenPHP version that was built
         *
         * @return string
         */
        public function getFrankenPhpVersion(): string
        {
            return $this->frankenPhpVersion;
        }

        /**
         * Checks if the state is valid for a build with the given base fingerprint, meaning libphp.a was built from
         * the same inputs and hasn't been modified since.
         *
         * @param string $baseFingerprint The base fingerprint of the build
         * @param string $libphpPath The path of libphp.a
         * @return bool True if the PHP build can be reused
         */
        public function isValidFor(string $baseFingerprint, string $libphpPath): bool
        {
            if($this->version !== self::STATE_VERSION || !hash_equals($this->baseFingerprint, $baseFingerprint))
            {
                return false;
            }

            $libphpFingerprint = PackageCacheData::computeFingerprint($libphpPath);
            return $libphpFingerprint !== null && $libphpFingerprint === $this->libphpFingerprint;
        }

        /**
         * Computes the fingerprint of the inputs that libphp.a is built from, the order of list values (eg; the
         * extensions) doesn't affect the fingerprint.
         *
         * @param array<string,mixed> $inputs The build inputs keyed by name
         * @return string The fingerprint
         * @throws OperationException Thrown if the inputs cannot be serialized
         */
        public static function computeBaseFingerprint(array $inputs): string
        {
            ksort($inputs);
            foreach($inputs as $key => $value)
            {
                if(is_array($value))
                {
                    $value = array_values(array_unique(array_map('strval', $value)));
                    sort($value);
                    $inputs[$key] = $value;
                }
            }

            try
            {
                return hash('sha1', json_encode($inputs, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            }
            catch (JsonException $e)
            {
                throw new OperationException('Failed to serialize inputs', $e->getCode(), $e);
            }
        }

        /**
         * Loads the state from the given file
         *
         * @param string $path The path of the state file
         * @return FrankenPhpBuildState|null The state, or null if the file doesn't exist, is corrupted or outdated
         */
        public static function load(string $path): ?FrankenPhpBuildState
        {
            if(!is_file($path))
            {
                return null;
            }

            try
            {
                $state = @unserialize(IO::readFile($path), ['allowed_classes' => [self::class]]);
            }
            catch(IOException)
            {
                return null;
            }

            if(!$state instanceof self || $state->getVersion() !== self::STATE_VERSION)
            {
                return null;
            }

            return $state;
        }

        /**
         * Saves the state to the given file
         *
         * @param string $path The path of the state file
         * @throws IOException Thrown if the file could not be written
         */
        public function save(string $path): void
        {
            IO::writeFile($path, serialize($this));
        }
    }
