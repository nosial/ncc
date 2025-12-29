<?php

    namespace ConsoleApp;

    class Application
    {
        /**
         * Runs the application
         *
         * @param array $arguments
         * @return int
         */
        public function run(array $arguments): int
        {
            echo "Console Application Started\n";
            echo "Package: " . (defined('ASSEMBLY_PACKAGE') ? ASSEMBLY_PACKAGE : 'unknown') . "\n";
            echo "Version: " . (defined('ASSEMBLY_VERSION') ? ASSEMBLY_VERSION : 'unknown') . "\n";
            
            if (count($arguments) > 0) {
                echo "Arguments received: " . implode(', ', $arguments) . "\n";
            } else {
                echo "No arguments provided\n";
            }
            
            return 0;
        }

        /**
         * Gets application info
         *
         * @return array
         */
        public function getInfo(): array
        {
            return [
                'name' => 'Console Application',
                'package' => defined('ASSEMBLY_PACKAGE') ? ASSEMBLY_PACKAGE : 'unknown',
                'version' => defined('ASSEMBLY_VERSION') ? ASSEMBLY_VERSION : 'unknown',
            ];
        }
    }
