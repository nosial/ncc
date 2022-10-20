<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Classes;

    use ncc\Exceptions\FileNotFoundException;

    class PackageParser
    {
        /**
         * @var string
         */
        private $PackagePath;

        /**
         * Package Parser public constructor.
         *
         * @param string $path
         */
        public function __construct(string $path)
        {
            $this->PackagePath = $path;
            $this->parseFile();
        }

        private function parseFile()
        {
            if(file_exists($this->PackagePath) == false)
            {
                throw new FileNotFoundException('The given package path \'' . $this->PackagePath . '\' does not exist');
            }

            if(is_file($this->PackagePath) == false)
            {
                throw new FileNotFoundException('The given package path \'' . $this->PackagePath . '\' is not a file');
            }

            $file_handler = fopen($this->PackagePath, 'rb');
            $header = fread($file_handler, 14);
        }
    }