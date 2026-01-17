<?php
    /*
 * Copyright (c) Nosial 2022-2026, all rights reserved.
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
 *
 */

    namespace ncc\CLI\Commands;

    use ncc\Libraries\fslib\IO;
    use ncc\Classes\Logger;
    use ncc\Classes\Utilities;

    class Helper
    {
        /**
         * Resolves the project.yml/project.yaml configuration path with the given project path input
         *
         * @param string|null $projectPath The project path input, if the input points to the project configuration then the same value would be returned
         * @return string|null The resolved project path, null if no such project configuration could be found
         */
        public static function resolveProjectConfigurationPath(?string $projectPath=null): ?string
        {
            if($projectPath === null)
            {
                Logger::getLogger()?->debug('No path provided, defaulting to cwd');
                $projectPath = getcwd();
            }
            else
            {
                $realProjectPath = realpath($projectPath);
                if($realProjectPath !== false)
                {
                    $projectPath = $realProjectPath;
                }
                else
                {
                    Logger::getLogger()?->debug('Failed to resolve the real path of ' . $projectPath);
                }
            }

            Logger::getLogger()?->debug("Resolving project configuration path: $projectPath");
            if(!IO::exists($projectPath))
            {
                Logger::getLogger()?->debug('Resolution failed, path does not exist');
                return null;
            }

            $projectConfigurationPath = Utilities::getProjectConfiguration($projectPath);
            if($projectConfigurationPath === null)
            {
                Logger::getLogger()?->debug("Resolution failed, project configuration not found");
                return null;
            }

            Logger::getLogger()?->debug("Resolution Success: $projectConfigurationPath");
            return $projectConfigurationPath;
        }
    }