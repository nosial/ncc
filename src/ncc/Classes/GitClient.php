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
     *
     */

    namespace ncc\Classes;

    use ncc\Exceptions\GitException;
    use ncc\ThirdParty\Symfony\Process\Process;
    use ncc\Utilities\Console;
    use ncc\Utilities\Functions;

    class GitClient
    {
        /**
         * Clones a remote repository to a temporary directory.
         *
         * @param string $url
         * @return string
         * @throws GitException
         */
        public static function cloneRepository(string $url): string
        {
            Console::outVerbose('Cloning repository: ' . $url);
            $path = Functions::getTmpDir();
            $process = new Process(["git", "clone", "--recurse-submodules", $url, $path]);
            $process->setTimeout(3600); // 1 hour
            $process->run(function ($type, $buffer)
            {
                if(Process::ERR === $type)
                {
                    Console::outWarning($buffer);
                }
                {
                    Console::outVerbose($buffer);
                }
            });

            if (!$process->isSuccessful())
            {
                throw new GitException(sprintf('Failed to clone repository %s: %s', $url, $process->getErrorOutput()));
            }

            Console::outVerbose('Repository cloned to: ' . $path);

            return $path;
        }

        /**
         * Checks out a specific branch or tag.
         *
         * @param string $path
         * @param string $branch
         * @throws GitException
         */
        public static function checkout(string $path, string $branch): void
        {
            Console::outVerbose('Checking out branch' . $branch);
            $process = new Process(["git", "checkout", $branch], $path);
            $process->setTimeout(3600); // 1 hour
            $process->run(function ($type, $buffer)
            {
                if (Process::ERR === $type)
                {
                    Console::outWarning($buffer);
                }
                else
                {
                    Console::outVerbose($buffer);
                }
            });

            if (!$process->isSuccessful())
            {
                throw new GitException(sprintf('Failed to checkout branch %s in repository %s: %s', $branch, $path, $process->getErrorOutput()));
            }

            Console::outVerbose('Checked out branch: ' . $branch);

            Console::outVerbose('Updating submodules');
            $process = new Process(["git", "submodule", "update", "--init", "--recursive"], $path);
            $process->setTimeout(3600); // 1 hour
            $process->run(function ($type, $buffer)
            {
                if (Process::ERR === $type)
                {
                    Console::outWarning($buffer);
                }
                else
                {
                    Console::outVerbose($buffer);
                }
            });

            if (!$process->isSuccessful())
            {
                throw new GitException(sprintf('Failed to update submodules in repository %s: %s', $path, $process->getErrorOutput()));
            }

            Console::outVerbose('Submodules updated');
        }

        /**
         * Returns an array of tags that are available in the repository.
         *
         * @param string $path
         * @return array
         * @throws GitException
         */
        public static function getTags(string $path): array
        {
            Console::outVerbose('Getting tags for repository: ' . $path);
            $process = new Process(["git", "fetch", '--all', '--tags'] , $path);
            $process->setTimeout(3600); // 1 hour
            $process->run(function ($type, $buffer)
            {
                Console::outVerbose($buffer);
            });

            if (!$process->isSuccessful())
            {
                throw new GitException(sprintf('Failed to fetch tags in repository %s: %s', $path, $process->getErrorOutput()));
            }

            $process = new Process(['git', '--no-pager', 'tag', '-l'] , $path);
            $process->run(function ($type, $buffer)
            {
                Console::outVerbose($buffer);
            });

            if (!$process->isSuccessful())
            {
                throw new GitException(sprintf('Failed to get tags in repository %s: %s', $path, $process->getErrorOutput()));
            }

            $tags = explode(PHP_EOL, $process->getOutput());
            $tags = array_filter($tags, static function ($tag)
            {
                return !empty($tag);
            });

            Console::outDebug('found ' . count($tags) . ' tags');
            return array_filter($tags);
        }

    }