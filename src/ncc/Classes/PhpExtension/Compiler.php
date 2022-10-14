<?php

    namespace ncc\Classes\PhpExtension;

    use FilesystemIterator;
    use ncc\Abstracts\CompilerOptions;
    use ncc\Interfaces\CompilerInterface;
    use ncc\ncc;
    use ncc\Objects\ProjectConfiguration;
    use ncc\ThirdParty\theseer\DirectoryScanner\DirectoryScanner;
    use ncc\Utilities\Console;

    class Compiler implements CompilerInterface
    {
        /**
         * @var ProjectConfiguration
         */
        private ProjectConfiguration $project;

        /**
         * @param ProjectConfiguration $project
         */
        public function __construct(ProjectConfiguration $project)
        {
            $this->project = $project;
        }

        public function prepare(array $options)
        {
            if(ncc::cliMode())
            {
                Console::out('Building autoloader');
                Console::out('theseer\DirectoryScanner - Copyright (c) 2009-2014 Arne Blankerts <arne@blankerts.de> All rights reserved.');
                Console::out('theseer\Autoload - Copyright (c) 2010-2016 Arne Blankerts <arne@blankerts.de> and Contributors All rights reserved.');
            }

            // First scan the project files and create a file struct.
            $DirectoryScanner = new DirectoryScanner();
            $DirectoryScanner->unsetFlag(FilesystemIterator::FOLLOW_SYMLINKS);
        }

        public function build(array $options)
        {
            // TODO: Implement build() method.
        }
    }