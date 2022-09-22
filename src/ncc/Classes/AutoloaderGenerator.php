<?php

    namespace ncc\Classes;

    use ncc\Objects\ProjectConfiguration;

    class AutoloaderGenerator
    {
        /**
         * @var ProjectConfiguration
         */
        private ProjectConfiguration $project;

        /**
         * @param ProjectConfiguration $project
         */
        public function  __construct(ProjectConfiguration $project)
        {
            $this->project = $project;
        }

        public function generateAutoload(string $src, string $output, bool $static=false)
        {

        }
    }