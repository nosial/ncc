<?php

    namespace ncc\Classes\Compilers;

    use ncc\Classes\PhpExtension\AutoloaderGenerator;
    use ncc\Interfaces\CompilerInterface;
    use ncc\Objects\ProjectConfiguration;

    class Php implements CompilerInterface
    {

        /**
         * @var ProjectConfiguration
         */
        private ProjectConfiguration $project;

        /**
         * @var AutoloaderGenerator
         */
        private AutoloaderGenerator $autoloader;

        /**
         * @param ProjectConfiguration $project
         */
        public function  __construct(ProjectConfiguration $project)
        {
            $this->project = $project;
            $this->autoloader = new AutoloaderGenerator($project);
        }

        public function prepare(array $options)
        {
            // TODO: Implement prepare() method.
        }

        public function build(array $options)
        {
            // TODO: Implement build() method.
        }
    }