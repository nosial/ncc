<?php

    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
     *
     *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
     *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
     *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
     *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
     *  conditions:
     *
     *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
     *  of the Software.
     *
     *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
     *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
     *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
     *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
     *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
     *  DEALINGS IN THE SOFTWARE.
     */

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use InvalidArgumentException;
    use ncc\Enums\Types\ProjectType;

    class ProjectDetectionResults
    {
        /**
         * @var string
         */
        private $project_file_path;

        /**
         * @see ProjectType
         * @var string
         */
        private $project_type;

        /**
         * ProjectDetectionResults Constructor
         *
         * @param string $project_file_path
         * @param string $project_type
         */
        public function __construct(string $project_file_path, string $project_type)
        {
            if(!in_array($project_type, ProjectType::ALL))
            {
                throw new InvalidArgumentException(sprintf('Invalid project type "%s"', $project_type));
            }

            $this->project_file_path = $project_file_path;
            $this->project_type = $project_type;
        }

        /**
         * Returns the project file path that was detected
         *
         * @return string
         */
        public function getProjectFilePath(): string
        {
            return $this->project_file_path;
        }

        /**
         * Returns the detected project type
         *
         * @return string
         */
        public function getProjectType(): string
        {
            return $this->project_type;
        }
    }