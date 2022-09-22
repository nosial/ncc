<?php

    namespace ncc\Classes;

    use ncc\Objects\ProjectConfiguration;

    class MakefileGenerator
    {
        /**
         * @var ProjectConfiguration
         */
        private ProjectConfiguration $project;

        /**
         * MakefileGenerator constructor.
         * @param ProjectConfiguration $project
         */
        public function __construct(ProjectConfiguration $project)
        {
            $this->project = $project;
        }



    }