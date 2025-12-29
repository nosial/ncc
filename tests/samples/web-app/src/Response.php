<?php

    namespace WebApp;

    class Response
    {
        /**
         * Sends JSON response
         *
         * @param mixed $data
         * @param int $statusCode
         */
        public static function json(mixed $data, int $statusCode = 200): void
        {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode($data);
        }

        /**
         * Sends HTML response
         *
         * @param string $content
         * @param int $statusCode
         */
        public static function html(string $content, int $statusCode = 200): void
        {
            http_response_code($statusCode);
            header('Content-Type: text/html');
            echo $content;
        }

        /**
         * Gets package info
         *
         * @return array
         */
        public static function getPackageInfo(): array
        {
            return [
                'package' => defined('ASSEMBLY_PACKAGE') ? ASSEMBLY_PACKAGE : 'unknown',
                'version' => defined('ASSEMBLY_VERSION') ? ASSEMBLY_VERSION : 'unknown',
            ];
        }
    }
