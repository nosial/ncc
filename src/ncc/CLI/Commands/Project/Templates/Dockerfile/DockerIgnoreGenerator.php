<?php

    namespace ncc\CLI\Commands\Project\Templates\Dockerfile;

    use ncc\Classes\Console;
    use ncc\Libraries\fslib\IO;

    class DockerIgnoreGenerator
    {
        public static function generate(string $projectDirectory): void
        {
            $path = $projectDirectory . DIRECTORY_SEPARATOR . '.dockerignore';
            if (IO::exists($path))
            {
                return;
            }

            IO::writeFile($path, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'dockerignore.tpl'));
            Console::out(sprintf('Generated File: %s', $path));
        }
    }
