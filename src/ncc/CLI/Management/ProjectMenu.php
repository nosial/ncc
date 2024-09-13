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

    use Exception;
    use ncc\Enums\ProjectTemplates;
    use ncc\Enums\Scopes;
    use ncc\Managers\CredentialManager;
    use ncc\Managers\ProjectManager;
    use ncc\Objects\CliHelpSection;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\Security;

    class ProjectMenu
    {
        /**
         * Displays the main help menu
         *
         * @param array $args
         * @return int
         * @throws Exception
         */
        public static function start(array $args): int
        {
            if(isset($args['create']))
            {
                return self::initializeProject($args);
            }

            if(isset($args['template']))
            {
                return self::applyTemplate($args);
            }

            if(isset($args['install']))
            {
                return self::installProject($args);
            }

            return self::displayOptions();
        }

        /**
         * Initializes a new project
         *
         * @param array $args
         * @return int
         */
        private static function initializeProject(array $args): int
        {
            if(isset($args['name']) || isset($args['n']))
            {
                $project_name = $args['name'] ?? $args['n'];
            }
            else
            {
                Console::outError('Missing required option: --name|-n, please specify the name of the project', true, 1);
                return 1;
            }

            if(isset($args['path']) || isset($args['p']))
            {
                $project_path = $args['path'] ?? $args['p'];
            }
            else
            {
                $project_path = Security::sanitizeFilename($project_name, false);
                Console::out(sprintf('No path specified, using \'%s\'', $project_path));
            }

            if(isset($args['package']) || isset($args['pkg']))
            {
                $package_name = $args['package'] ?? $args['pkg'];
            }
            else
            {
                Console::outError('Missing required option: --package|--pkg, please specify the package name of the project', true, 1);
                return 1;
            }

            if(isset($args['ext']))
            {
                $compiler_extension = $args['ext'];
            }
            else
            {
                Console::outError('Missing required option: --ext, please specify the compiler extension of the project', true, 1);
                return 1;
            }

            try
            {
                $project_manager = ProjectManager::initializeProject($project_path, $project_name, $package_name, $compiler_extension);
            }
            catch(Exception $e)
            {
                Console::outException('There was an error while trying to initialize the project', $e, 1);
                return 1;
            }

            Console::out(sprintf('Project successfully created in \'%s\'', $project_manager->getProjectPath()));
            Console::out(sprintf('Modify the project configuration in \'%s\'', $project_manager->getProjectPath() . DIRECTORY_SEPARATOR . 'project.json'));
            return 0;
        }

        /**
         * Installs the project dependencies
         *
         * @param array $args
         * @return int
         * @throws Exception
         */
        private static function installProject(array $args): int
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM->value)
            {
                Console::outError('You cannot install packages in a user scope, please run this command as root', true, 1);
                return 1;
            }

            if(isset($args['path']) || isset($args['p']))
            {
                $project_path = $args['path'] ?? $args['p'];
            }
            elseif(is_file(getcwd() . DIRECTORY_SEPARATOR . 'project.json'))
            {
                $project_path = getcwd();
            }
            else
            {
                Console::outError('Missing option: --path|-p, please specify the path to the project', true, 1);
                return 1;
            }

            $authentication = $args['authentication'] ?? $args['a'] ?? null;
            $authentication_entry = null;

            if($authentication !== null)
            {
                $entry = (new CredentialManager())->getVault()?->getEntry($authentication);

                if($entry->isEncrypted())
                {
                    $tries = 0;
                    while(true)
                    {
                        if (!$entry->unlock(Console::passwordInput('Password/Secret: ')))
                        {
                            $tries++;
                            if ($tries >= 3)
                            {
                                Console::outError('Too many failed attempts.', true, 1);
                                return 1;
                            }

                            Console::outError(sprintf('Incorrect password/secret, %d attempts remaining', 3 - $tries));
                        }
                        else
                        {
                            Console::out('Authentication successful.');
                            return 1;
                        }
                    }
                }

                $authentication_entry = $entry->getPassword();
            }

            try
            {
                $project_manager = new ProjectManager($project_path);
            }
            catch(Exception $e)
            {
                Console::outException('There was an error while trying to load the project', $e, 1);
                return 1;
            }

            try
            {
                Console::out(sprintf('Installed %s packages', count($project_manager->installDependencies($authentication_entry))));
            }
            catch(Exception $e)
            {
                Console::outException('There was an error while trying to install the project dependencies', $e, 1);
                return 1;
            }

            Console::out(sprintf('Project dependencies successfully installed in \'%s\'', $project_manager->getProjectPath()));
            return 0;
        }

        /**
         * Applies a template to the project
         *
         * @param array $args
         * @return int
         */
        private static function applyTemplate(array $args): int
        {
            if(isset($args['path']) || isset($args['p']))
            {
                $project_path = $args['path'] ?? $args['p'];
            }
            elseif(is_file(getcwd() . DIRECTORY_SEPARATOR . 'project.json'))
            {
                $project_path = getcwd();
            }
            else
            {
                Console::outError('Missing option: --path|-p, please specify the path to the project', true, 1);
                return 1;
            }

            if(isset($args['name']) || isset($args['n']))
            {
                $template_name = $args['name'] ?? $args['n'];
            }
            else
            {
                Console::outError('Missing required option: --name|-n, please specify the name of the template', true, 1);
                return 1;
            }

            try
            {
                $project_manager = new ProjectManager($project_path);
            }
            catch(Exception $e)
            {
                Console::outException('There was an error while trying to load the project', $e, 1);
                return 1;
            }

            try
            {
                $project_manager->applyTemplate($template_name);
            }
            catch(Exception $e)
            {
                Console::outException('There was an error while trying to apply the template', $e, 1);
                return 1;
            }

            Console::out(sprintf('Template successfully applied to project in \'%s\'', $project_manager->getProjectPath()));
            return 0;
        }

        /**
         * Displays the main options section
         *
         * @return int
         */
        private static function displayOptions(): int
        {
            $options = [
                new CliHelpSection(['help'], 'Displays this help menu about the value command'),
                new CliHelpSection(['create', '--path|-p', '--name|-n', '--package|--pkg', '--ext'], 'Creates a new ncc project'),
                new CliHelpSection(['template', '--path|-p', '--name|-n'], 'Applies a template to the project'),
                new CliHelpSection(['install', '--path|-p', '--authentication|-a'], 'Installs the project dependencies'),
            ];

            $options_padding = Functions::detectParametersPadding($options) + 4;

            Console::out('Usage: ncc project {command} [options]');
            Console::out('Options:');
            foreach($options as $option)
            {
                Console::out('   ' . $option->toString($options_padding));
            }

            Console::out(PHP_EOL . 'Available Templates:');
            foreach(ProjectTemplates::ALL as $template)
            {
                Console::out('   ' . $template);
            }

            return 0;
        }
    }