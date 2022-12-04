<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use Exception;
    use ncc\Abstracts\LogLevel;
    use ncc\Abstracts\Scopes;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\ThirdParty\Symfony\Yaml\Yaml;
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
            if(Resolver::resolveScope() !== Scopes::System)
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
            // composer.options.quiet
            $result = match (strtolower(explode('.', $property)[0]))
            {
                'ncc' => $this->updateNccProperties($property, $value),
                'php' => $this->updatePhpProperties($property, $value),
                'git' => $this->updateGitProperties($property, $value),
                'runners' => $this->updateRunnerProperties($property, $value),
                'composer' => $this->updateComposerProperties($property, $value),
                default => false,
            };

            $this->save();
            return $result;
        }

        /**
         * Updates NCC configuration properties in the configuration
         *
         * @param string $property
         * @param $value
         * @return bool
         */
        private function updateNccProperties(string $property, $value): bool
        {
            $delete = false;
            if(is_null($value))
                $delete = true;

            switch(strtolower($property))
            {
                case 'ncc.cli.no_colors':
                    $this->Configuration['ncc']['cli']['no_colors'] = Functions::cbool($value);
                    break;
                case 'ncc.cli.basic_ascii':
                    $this->Configuration['ncc']['cli']['basic_ascii'] = Functions::cbool($value);
                    break;
                case 'ncc.cli.logging':
                    $this->Configuration['ncc']['cli']['logging'] = ($delete ? LogLevel::Info : (string)$value);
                    break;

                default:
                    return false;
            }

            return true;
        }

        /**
         * Updates PHP properties in the configuraiton
         *
         * @param string $property
         * @param $value
         * @return bool
         */
        private function updatePhpProperties(string $property, $value): bool
        {
            $delete = false;
            if(is_null($value))
                $delete = true;

            switch(strtolower($property))
            {
                case 'php.executable_path':
                    $this->Configuration['php']['executable_path'] = ($delete ? null : (string)$value);
                    break;
                case 'php.runtime.initialize_on_require':
                    $this->Configuration['php']['runtime']['initialize_on_require'] = Functions::cbool($value);
                    break;
                case 'php.runtime.handle_exceptions':
                    $this->Configuration['php']['runtime']['handle_exceptions'] = Functions::cbool($value);
                    break;

                default:
                    return false;
            }

            return true;
        }

        /**
         * Updated git properties
         *
         * @param string $property
         * @param $value
         * @return bool
         */
        private function updateGitProperties(string $property, $value): bool
        {
            $delete = false;
            if(is_null($value))
                $delete = true;

            switch(strtolower($property))
            {
                case 'git.enabled':
                    $this->Configuration['git']['enabled'] = Functions::cbool($value);
                    break;
                case 'git.executable_path':
                    $this->Configuration['git']['executable_path'] = ($delete? null : (string)$value);
                    break;
                default:
                    return false;
            }

            return true;
        }

        /**
         * Updaters runner properties
         *
         * @param string $property
         * @param $value
         * @return bool
         */
        private function updateRunnerProperties(string $property, $value): bool
        {
            $delete = false;
            if(is_null($value))
                $delete = true;

            switch(strtolower($property))
            {
                case 'runners.php':
                    $this->Configuration['runners']['php'] = ($delete? null : (string)$value);
                    break;
                case 'runners.bash':
                    $this->Configuration['runners']['bash'] = ($delete? null : (string)$value);
                    break;
                case 'runners.sh':
                    $this->Configuration['runners']['sh'] = ($delete? null : (string)$value);
                    break;
                case 'runners.python':
                    $this->Configuration['runners']['python'] = ($delete? null : (string)$value);
                    break;
                case 'runners.python3':
                    $this->Configuration['runners']['python3'] = ($delete? null : (string)$value);
                    break;
                case 'runners.python2':
                    $this->Configuration['runners']['python2'] = ($delete? null : (string)$value);
                    break;

                default:
                    return false;
            }

            return true;
        }

        /**
         * Updates a composer property value
         *
         * @param string $property
         * @param $value
         * @return bool
         */
        private function updateComposerProperties(string $property, $value): bool
        {
            $delete = false;
            if(is_null($value))
                $delete = true;

            switch(strtolower($property))
            {
                case 'composer.enabled':
                    $this->Configuration['composer']['enabled'] = Functions::cbool($value);
                    break;
                case 'composer.enable_internal_composer':
                    $this->Configuration['composer']['enable_internal_composer'] = Functions::cbool($value);
                    break;
                case 'composer.executable_path':
                    $this->Configuration['composer']['executable_path'] = ($delete? null : (string)$value);
                    break;
                case 'composer.options.quiet':
                    $this->Configuration['composer']['options']['quiet'] = Functions::cbool($value);
                    break;
                case 'composer.options.no_ansi':
                    $this->Configuration['composer']['options']['no_ansi'] = Functions::cbool($value);
                    break;
                case 'composer.options.no_interaction':
                    $this->Configuration['composer']['options']['no_interaction'] = Functions::cbool($value);
                    break;
                case 'composer.options.profile':
                    $this->Configuration['composer']['options']['profile'] = Functions::cbool($value);
                    break;
                case 'composer.options.no_scripts':
                    $this->Configuration['composer']['options']['no_scripts'] = Functions::cbool($value);
                    break;
                case 'composer.options.no_cache':
                    $this->Configuration['composer']['options']['no_cache'] = Functions::cbool($value);
                    break;
                case 'composer.options.logging':
                    $this->Configuration['composer']['options']['logging'] = ((int)$value > 0 ? (int)$value : 1);
                    break;
                default:
                    return false;
            }

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