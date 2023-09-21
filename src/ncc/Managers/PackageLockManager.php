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

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use ncc\Enums\Scopes;
    use ncc\Exceptions\ConfigurationException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\OperationException;
    use ncc\Exceptions\PathNotFoundException;
    use ncc\Objects\PackageLock;
    use ncc\Utilities\Console;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\Extensions\ZiProto\ZiProto;

    class PackageLockManager
    {
        /**
         * @var PackageLock
         */
        private $package_lock;

        /**
         * Public Constructor
         *
         * @throws ConfigurationException
         * @throws IOException
         * @throws PathNotFoundException
         */
        public function __construct()
        {
            if(!is_file(PathFinder::getPackageLock()))
            {
                $this->package_lock = new PackageLock();
                return;
            }

            $this->package_lock = PackageLock::fromArray(ZiProto::decode(IO::fread(PathFinder::getPackageLock())));
        }

        /**
         * Returns the PackageLock object
         *
         * @return PackageLock
         */
        public function getPackageLock(): PackageLock
        {
            return $this->package_lock;
        }

        /**
         * Saves the PackageLock to disk
         *
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public function save(): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new OperationException('You must be running as root to update the system package lock');
            }

            Console::outDebug(sprintf('Saving package lock to \'%s\'', PathFinder::getPackageLock()));
            IO::fwrite(PathFinder::getPackageLock(), ZiProto::encode($this->package_lock->toArray(true)), 0644);
        }

        /**
         * Initializes the package lock file if it doesn't exist
         *
         * @return void
         * @throws IOException
         * @throws OperationException
         */
        public static function initializePackageLock(): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new OperationException('You must be running as root to update the system package lock');
            }

            if(is_file(PathFinder::getPackageLock()))
            {
                Console::outVerbose('Skipping package lock initialization, package lock already exists');
                return;
            }

            Console::outVerbose(sprintf('Initializing package lock at \'%s\'', PathFinder::getPackageLock()));
            IO::fwrite(PathFinder::getPackageLock(), ZiProto::encode((new PackageLock())->toArray(true)), 0644);
        }
    }