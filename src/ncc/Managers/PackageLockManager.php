<?php

    /** @noinspection PhpPropertyOnlyWrittenInspection */
    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Managers;

    use Exception;
    use ncc\Abstracts\Scopes;
    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\IOException;
    use ncc\Exceptions\PackageLockException;
    use ncc\Objects\PackageLock;
    use ncc\Utilities\Console;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\Utilities\RuntimeCache;
    use ncc\ZiProto\ZiProto;

    class PackageLockManager
    {
        /**
         * @var PackageLock|null
         */
        private $PackageLock;

        /**
         * @var string
         */
        private $PackageLockPath;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->PackageLockPath = PathFinder::getPackageLock(Scopes::System);

            try
            {
                $this->load();
            }
            catch (PackageLockException $e)
            {
                unset($e);
            }
        }

        /**
         * Loads the PackageLock from the disk
         *
         * @return void
         * @throws PackageLockException
         */
        public function load(): void
        {
            Console::outDebug(sprintf('loading PackageLock from \'%s\'', $this->PackageLockPath));
            if(RuntimeCache::get($this->PackageLockPath) !== null)
            {
                Console::outDebug('package lock is cached, loading from cache');
                $this->PackageLock = RuntimeCache::get($this->PackageLockPath);
                return;
            }

            if(file_exists($this->PackageLockPath) && is_file($this->PackageLockPath))
            {
                try
                {
                    Console::outDebug('package lock exists, loading from disk');
                    $data = IO::fread($this->PackageLockPath);
                    if(strlen($data) > 0)
                    {
                        $this->PackageLock = PackageLock::fromArray(ZiProto::decode($data));
                    }
                    else
                    {
                        $this->PackageLock = new PackageLock();
                    }
                }
                catch(Exception $e)
                {
                    throw new PackageLockException('The PackageLock file cannot be parsed', $e);
                }
            }
            else
            {
                Console::outDebug('package lock file does not exist, creating new package lock');
                $this->PackageLock = new PackageLock();
            }

            Console::outDebug('caching PackageLock');
            RuntimeCache::set($this->PackageLockPath, $this->PackageLock);
        }

        /**
         * Saves the PackageLock to disk
         *
         * @return void
         * @throws AccessDeniedException
         * @throws PackageLockException
         */
        public function save(): void
        {
            Console::outDebug(sprintf('saving package lock to \'%s\'', $this->PackageLockPath));

            // Don't save something that isn't loaded lol
            if($this->PackageLock == null)
            {
                Console::outDebug('warning: PackageLock is null, not saving to disk');
                return;
            }

            if(Resolver::resolveScope() !== Scopes::System)
                throw new AccessDeniedException('Cannot write to PackageLock, insufficient permissions');

            try
            {
                Console::outDebug(sprintf('saving PackageLock to \'%s\' & caching', $this->PackageLockPath));
                IO::fwrite($this->PackageLockPath, ZiProto::encode($this->PackageLock->toArray(true)), 0755);
                RuntimeCache::set($this->PackageLockPath, $this->PackageLock);
            }
            catch(IOException $e)
            {
                throw new PackageLockException('Cannot save the package lock file to disk', $e);
            }
        }

        /**
         * Constructs the package lock file if it doesn't exist
         *
         * @return void
         * @throws AccessDeniedException
         * @throws PackageLockException
         */
        public function constructLockFile(): void
        {
            try
            {
                $this->load();
            }
            catch (PackageLockException $e)
            {
                unset($e);
                $this->PackageLock = new PackageLock();
            }

            $this->save();
        }

        /**
         * @return PackageLock|null
         * @throws PackageLockException
         */
        public function getPackageLock(): ?PackageLock
        {
            if($this->PackageLock == null)
                $this->load();
            return $this->PackageLock;
        }
    }