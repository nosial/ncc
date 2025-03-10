<?php

    namespace %ASSEMBLY.NAME%;

    class Program
    {
        /**
         * %ASSEMBLY.NAME% main entry point
         *
         * @param string[] $args Command-line arguments
         * @return int Exit code
         */
        public static function main(array $args): int
        {
            print("Hello World from %ASSEMBLY.PACKAGE%!" . PHP_EOL);
            return 0;
        }
    }