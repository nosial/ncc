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

    namespace ncc\Managers;

    use Exception;
    use ncc\Enums\Scopes;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\ThirdParty\Symfony\Yaml\Yaml;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\RuntimeCache;

    class ConfigurationManager
    {
        /**
         * The configuration contents parsed
         *
         * @var mixed
         */
        private $Configuration;

        /**
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws InvalidScopeException
         */
        public function __construct()
        {
            $this->load();
        }

        /**
         * Loads the configuration file if it exists
         *
         * @return void
         * @throws AccessDeniedException
         * @throws FileNotFoundException
         * @throws IOException
         * @throws InvalidScopeException
         */
        public function load(): void
        {
            Console::outDebug(sprintf('loading configuration file from %s', PathFinder::getConfigurationFile()));

            $this->Configuration = RuntimeCache::get('ncc.yaml');
            if($this->Configuration !== null)
                return;
            $configuration_contents = IO::fread(PathFinder::getConfigurationFile());
            $this->Configuration = Yaml::parse($configuration_contents);
            RuntimeCache::set('ncc.yaml', $this->Configuration);
        }

        /**
         * Saves the configuration file to disk
         *
         * @return void
         * @throws AccessDeniedException
         * @throws IOException
         * @throws InvalidScopeException
         */
        public function save(): void
        {
            Console::outDebug(sprintf('saving configuration file to %s', PathFinder::getConfigurationFile()));

            if(Resolver::resolveScope() !== Scopes::SYSTEM)
                throw new AccessDeniedException('Cannot save configuration file, insufficient permissions');

            if($this->Configuration == null)
                return;

            IO::fwrite(PathFinder::getConfigurationFile(), Yaml::dump($this->Configuration), 0755);
            RuntimeCache::set('ncc.yaml', $this->Configuration);
            RuntimeCache::set('config_cache', []);
        }

        /**
         * Returns the value of a property
         * Returns null even if the property value exists & it's value is null
         *
         * @param string $property
         * @return mixed|null
         * @noinspection PhpMissingReturnTypeInspection
         */
        public function getProperty(string $property)
        {
            Console::outDebug(sprintf('getting property %s', $property));

            Console::outDebug($property);
            $current_selection = $this->getConfiguration();
            foreach(explode('.', strtolower($property)) as $property)
            {
                $value_found = false;
                foreach($current_selection as $key => $value)
                {
                    if($key == $property)
                    {
                        $current_selection = $value;
                        $value_found = true;
                        break;
                    }
                }

                if(!$value_found)
                    return null;
            }

            return $current_selection;
        }

        /**
         * @param string $property
         * @param $value
         * @return bool
         * @throws AccessDeniedException
         * @throws IOException
         * @throws InvalidScopeException
         */
        public function updateProperty(string $property, $value): bool
        {
            Console::outDebug(sprintf('updating property %s', $property));

            $keys = explode('.', $property);
            $current = &$this->Configuration;
            foreach ($keys as $k)
            {
                if (!array_key_exists($k, $current))
                {
                    return false;
                }
                $current = &$current[$k];
            }
            $current = Functions::stringTypeCast($value);
            $this->save();

            return true;
        }

        /**
         * @return mixed
         */
        private function getConfiguration(): mixed
        {
            if($this->Configuration == null)
            {
                try
                {
                    $this->load();
                }
                catch(Exception $e)
                {
                    $this->Configuration = [];
                }
            }


            return $this->Configuration;
        }
    }