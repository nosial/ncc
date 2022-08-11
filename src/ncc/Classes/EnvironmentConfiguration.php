<?php

    namespace ncc\Classes;

    use ncc\Objects\PhpConfiguration;

    class EnvironmentConfiguration
    {
        /**
         * Returns an array of all the current configuration values set in this environment
         *
         * @return PhpConfiguration[]
         */
        public static function getCurrentConfiguration(): array
        {
            $results = [];

            foreach(ini_get_all() as $name => $config)
            {
                $results[$name] = PhpConfiguration::fromArray($config);
            }

            return $results;
        }

        /**
         * Returns an array of only the changed configuration values
         *
         * @return PhpConfiguration[]
         */
        public static function getChangedValues(): array
        {
            $results = [];

            foreach(ini_get_all() as $name => $config)
            {
                $config = PhpConfiguration::fromArray($config);
                if($config->LocalValue !== $config->GlobalValue)
                {
                    $results[$name] = $config;
                }
            }

            return $results;
        }

        /**
         * @param string $file_path
         * @return void
         */
        public static function export(string $file_path)
        {
            $configuration = [];
            foreach(self::getChangedValues() as $changedValue)
            {
                $configuration[$changedValue->getName()] = $changedValue->getValue();
            }

            // TODO: Implement ini writing process here
        }

        public static function import(string $file_path)
        {
            // TODO: Implement ini reading process here
            $configuration = [];
            foreach($configuration as $item => $value)
            {
                ini_set($item, $value);
            }
        }
    }