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

    namespace ncc\CLI\Commands;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\CLI\Commands\Project\ApplyTemplate;
    use ncc\CLI\Commands\Project\ConvertProject;
    use ncc\CLI\Commands\Project\CreateProject;
    use ncc\CLI\Commands\Project\GenerateStubs;
    use ncc\CLI\Commands\Project\InstallDependencies;
    use ncc\CLI\Commands\Project\ValidateProject;

    class ProjectCommand extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            if(isset($argv['create']))
            {
                return CreateProject::handle($argv);
            }
            elseif(isset($argv['validate']))
            {
                return ValidateProject::handle($argv);
            }
            elseif(isset($argv['generate']))
            {
                return ApplyTemplate::handle($argv);
            }
            elseif(isset($argv['convert']))
            {
                return ConvertProject::handle($argv);
            }
            elseif(isset($argv['install']))
            {
                return InstallDependencies::handle($argv);
            }
            elseif(isset($argv['stubs']))
            {
                return GenerateStubs::handle($argv);
            }
            elseif(isset($argv['help']) || isset($argv['h']))
            {
                $helpCommand = $argv['help'] ?? $argv['h'] ?? null;
                // If help is just a boolean flag (true), treat as general help
                if($helpCommand === true)
                {
                    $helpCommand = null;
                }
                self::help($helpCommand);
                return 0;
            }

            self::help();
            return 0;
        }

        /**
         * Prints out the help menu for the project manager
         *
         * @param string|true|null $command The command to get help information about
         * @return void
         */
        public static function help(string|true|null $command=null): void
        {
            if($command === null || $command === true)
            {
                Console::out('Usage: ncc project [command] [options]' . PHP_EOL);
                Console::out('Commands:');
                Console::out('  create                    Create a new ncc project');
                Console::out('  validate                  Validates a given ncc project and gives inspection results');
                Console::out('  --generate=<name>         Apply automatic templates to your existing project');
                Console::out('  convert                   Convert an existing project to an ncc project');
                Console::out('  install                   Install all project dependencies');
                Console::out('  stubs                     Generate stubs for all project dependencies (like vendor/autoload.php)');
                Console::out(PHP_EOL . 'Use "ncc project [command] --help" for more information about a command.');
                Console::out('Use "ncc project generate --help" for template options.');
                return;
            }

            switch($command)
            {
                case 'create':
                    Console::out('Usage: ncc project create --name=<name> --package=<package>' . PHP_EOL);
                    Console::out('Creates a new PHP project with an ncc project configuration file.');
                    Console::out('Once created, you can apply templates to automatically generate a');
                    Console::out('build system for your project.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name            (Required) The name of the project/product');
                    Console::out('  --package         (Required) The package name (e.g., com.example.project)');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc project create --name=MyProject --package=com.example.myproject');
                    break;

                case 'validate':
                    Console::out('Usage: ncc project validate [--path=<path>]' . PHP_EOL);
                    Console::out('Validates an ncc project configuration and displays inspection results.');
                    Console::out('Checks for errors, missing dependencies, and configuration issues.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --path            (Optional) Path to the project directory');
                    Console::out('                    Defaults to current working directory');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc project validate');
                    Console::out('  ncc project validate --path=/path/to/project');
                    break;

                case 'generate':
                    Console::out('Usage: ncc project --generate=<template_name> [--path=<path>]' . PHP_EOL);
                    Console::out('Applies an automatic template to your existing project.');
                    Console::out('Templates can generate build configurations, testing frameworks,');
                    Console::out('documentation, CI/CD pipelines, and other project scaffolding automatically.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --generate, -g    (Required) The name of the template to apply');
                    Console::out('  --path, -p        (Optional) Path to the project directory');
                    Console::out('                    Defaults to current working directory' . PHP_EOL);
                    Console::out('Available templates:');
                    Console::out('  phpunit, test, tests     Generate PHPUnit test configuration');
                    Console::out('  phpdoc, doc, docs        Generate PHPDoc documentation configuration');
                    Console::out('  makefile, make           Generate Makefile for build automation');
                    Console::out('  github-ci, github        Generate GitHub Actions CI/CD workflow');
                    Console::out('  dockerfile, docker       Generate Dockerfile for containerization for the project\'s web entry point');
                    Console::out('  web                      Generate web project template with web entry point and build configuration');
                    Console::out('  commandline, cli         Generate command-line project template with main execution unit');
                    Console::out(PHP_EOL . 'Examples:');
                    Console::out('  ncc project --generate=phpunit');
                    Console::out('  ncc project --generate=makefile --path=/path/to/project');
                    Console::out('  ncc project -g=github-ci');
                    break;

                case 'convert':
                    Console::out('Usage: ncc project convert [--path=<path>] [--format=<format>]' . PHP_EOL);
                    Console::out('Converts an existing project from another format to ncc v3+ format.');
                    Console::out('Currently supported formats: composer, legacy' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --path            (Optional) Path to the project directory');
                    Console::out('                    Defaults to current working directory');
                    Console::out('  --format          (Optional) Source format to convert from');
                    Console::out('                    Auto-detected if not specified');
                    Console::out('                    Supported: composer, legacy');
                    Console::out(PHP_EOL . 'Examples:');
                    Console::out('  ncc project convert');
                    Console::out('  ncc project convert --path=/path/to/project --format=composer');
                    break;

                case 'install':
                    Console::out('Usage: ncc project install [options]' . PHP_EOL);
                    Console::out('Installs all dependencies defined in the project configuration.');
                    Console::out('This includes dependencies from the project itself and all build');
                    Console::out('configurations. The project itself is not installed, only its');
                    Console::out('dependencies.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --path            (Optional) Path to the project directory');
                    Console::out('                    Defaults to current working directory');
                    Console::out('  --build, -b       (Optional) Install dependencies only for a specific build configuration');
                    Console::out('                    If not specified, installs dependencies from all build configurations');
                    Console::out('  --yes, -y         Automatically confirm all prompts');
                    Console::out('  --reinstall, -r   Force reinstall packages even if they are already installed');
                    Console::out('  --skip-repositories, --skip-repos, --sr');
                    Console::out('                    Skip adding package repositories');
                    Console::out('  --<repository>-auth=<entry>');
                    Console::out('                    Authenticate using the specified vault entry for a repository');
                    Console::out(PHP_EOL . 'Examples:');
                    Console::out('  ncc project install');
                    Console::out('  ncc project install --path=/path/to/project');
                    Console::out('  ncc project install --build=debug');
                    Console::out('  ncc project install --yes');
                    Console::out('  ncc project install --reinstall');
                    Console::out('  ncc project install --github-auth=mytoken');
                    break;

                case 'stubs':
                    Console::out('Usage: ncc project stubs [options]' . PHP_EOL);
                    Console::out('Extracts all project dependencies to create a vendor/autoload.php');
                    Console::out('environment for development purposes. This allows IDEs and development tools to');
                    Console::out('have access to package source code for autocompletion and navigation.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --path            (Optional) Path to the project directory');
                    Console::out('                    Defaults to current working directory');
                    Console::out('  --output, -o      (Optional) Output directory for extracted stubs');
                    Console::out('                    Defaults to ./vendor in the current directory');
                    Console::out('  --build, -b       (Optional) Generate stubs only for a specific build configuration');
                    Console::out('                    If not specified, generates stubs from all build configurations');
                    Console::out(PHP_EOL . 'Examples:');
                    Console::out('  ncc project stubs');
                    Console::out('  ncc project stubs --path=/path/to/project');
                    Console::out('  ncc project stubs --output=/custom/path');
                    Console::out('  ncc project stubs --build=debug');
                    Console::out(PHP_EOL . 'Note:');
                    Console::out('  All dependencies must be installed before generating stubs.');
                    Console::out('  If any packages are missing, run: ncc project install');
                    break;

                default:
                    Console::error('Unknown command ' . $command);
                    break;
            }
        }
    }