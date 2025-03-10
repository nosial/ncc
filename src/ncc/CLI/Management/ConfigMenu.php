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

    namespace ncc\CLI\Management;

    use JsonException;
    use ncc\Enums\Scopes;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\IOException;
    use ncc\Managers\ConfigurationManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;

    class ConfigMenu
    {
        /**
         * Displays the main help menu
         *
         * @param $args
         * @return int
         * @throws IOException
         * @throws AuthenticationException
         */
        public static function start($args): int
        {
            if(isset($args['sample']))
            {
                $sample_file = __DIR__ . DIRECTORY_SEPARATOR . 'template_config.yaml';
                if(!is_file($sample_file))
                {
                    Console::outError('Cannot display sample, file template_config.yaml was not found', true, 1);
                    return 1;
                }

                $handle = fopen($sample_file, 'rb');

                if (!$handle)
                {
                    Console::outError('Cannot display sample, error reading template_config.yaml', true, 1);
                    return 1;
                }

                while (($line = fgets($handle)) !== false)
                {
                    Console::out($line, false);
                }

                fclose($handle);
                return 0;
            }

            if(isset($args['read']))
            {
                if(!is_file(PathFinder::getConfigurationFile()))
                {
                    Console::outError('Cannot read configuration file, path not found', true, 1);
                    return 1;
                }

                $handle = fopen(PathFinder::getConfigurationFile(), 'rb');
                if (!$handle)
                {
                    Console::outError('Cannot display configuration file, error reading file', true, 1);
                    return 1;
                }

                while (($line = fgets($handle)) !== false)
                {
                    Console::out($line, false);
                }

                fclose($handle);
                return 0;
            }

            if(isset($args['p']))
            {
                $configuration_manager = new ConfigurationManager();

                if(isset($args['v']))
                {
                    if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
                    {
                        Console::outError('Insufficient permissions, cannot modify configuration values', true, 1);
                        return 1;
                    }

                    if(strtolower($args['v']) === 'null')
                    {
                        $args['v'] = null;
                    }

                    if($configuration_manager->updateProperty($args['p'], $args['v']))
                    {
                        $configuration_manager->save();
                        return 0;
                    }

                    Console::outError(sprintf('Unknown property %s', $args['p']), true, 1);
                    return 1;
                }

                $value = $configuration_manager->getProperty($args['p']);
                if(!is_null($value))
                {
                    if(is_bool($value))
                    {
                        $value = ($value ? 'true' : 'false');
                    }

                    if(is_array($value))
                    {
                        try
                        {
                            $value = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                        }
                        catch(JsonException $e)
                        {
                            Console::outException(sprintf('Failed to encode array: %s', $e->getMessage()), $e, 1);
                        }
                    }

                    Console::out((string)$value);
                }

                return 0;
            }

            return self::displayOptions();
        }

        /**
         * Displays the main options section
         *
         * @return int
         */
        private static function displayOptions(): int
        {
            $options = [
                new CliHelpSection(['sample'], 'Displays a sample configuration file with documentation'),
                new CliHelpSection(['read'], 'Displays the current configuration file NCC is using'),
                new CliHelpSection(['-p'], 'Property value name (eg; composer.options.no_scripts)'),
                new CliHelpSection(['-v'], '(Optional) Value to set to property')
            ];

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc config -p <property_name> -v <value>');
            Console::out('Options:');
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }

            Console::out('If `v` is not specified the property will be displayed');
            Console::out('If `v` is specified but `null` is set, the default value or null will be used');
            Console::out('For documentation run `ncc config sample`');
            return 0;
        }
    }