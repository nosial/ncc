<?php

    namespace LibraryWithDeps;

    class DataProcessor
    {
        /**
         * Processes an array of data
         *
         * @param array $data
         * @return array
         */
        public static function process(array $data): array
        {
            return array_map(function($item) {
                if (is_string($item)) {
                    return strtoupper($item);
                }
                if (is_numeric($item)) {
                    return $item * 2;
                }
                return $item;
            }, $data);
        }

        /**
         * Filters array by value
         *
         * @param array $data
         * @param mixed $value
         * @return array
         */
        public static function filter(array $data, mixed $value): array
        {
            return array_filter($data, fn($item) => $item !== $value);
        }

        /**
         * Gets package info including assembly constants
         *
         * @return array
         */
        public static function getPackageInfo(): array
        {
            return [
                'package' => defined('ASSEMBLY_PACKAGE') ? ASSEMBLY_PACKAGE : 'unknown',
                'version' => defined('ASSEMBLY_VERSION') ? ASSEMBLY_VERSION : 'unknown',
                'uid' => defined('ASSEMBLY_UID') ? ASSEMBLY_UID : 'unknown',
            ];
        }
    }
