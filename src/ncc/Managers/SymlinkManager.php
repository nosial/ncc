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

    use Exception;
    use ncc\Enums\Scopes;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\SymlinkDictionary\SymlinkEntry;
    use ncc\ThirdParty\Symfony\Filesystem\Filesystem;
    use ncc\Utilities\Console;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\Utilities\Resolver;
    use ncc\ZiProto\ZiProto;

    class SymlinkManager
    {
        /**
         * @var string
         */
        private static $BinPath = DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'bin';

        /**
         * The path to the symlink dictionary file
         *
         * @var string
         */
        private $SymlinkDictionaryPath;

        /**
         * An array of all the defined symlinks
         *
         * @var SymlinkEntry[]
         */
        private $SymlinkDictionary;

        /**
         * Public Constructor
         */
        public function __construct()
        {
            try
            {
                $this->SymlinkDictionaryPath = PathFinder::getSymlinkDictionary(Scopes::SYSTEM);
                $this->load();
            }
            catch(Exception $e)
            {
                Console::outWarning(sprintf('failed to load symlink dictionary from %s', $this->SymlinkDictionaryPath));
            }
            finally
            {
                if($this->SymlinkDictionary === null)
                    $this->SymlinkDictionary = [];

                unset($e);
            }
        }

        /**
         * Loads the symlink dictionary from the file
         *
         * @return void
         * @throws AuthenticationException
         */
        public function load(): void
        {
            if($this->SymlinkDictionary !== null)
                return;

            Console::outDebug(sprintf('loading symlink dictionary from %s', $this->SymlinkDictionaryPath));

            if(!file_exists($this->SymlinkDictionaryPath))
            {
                Console::outDebug('symlink dictionary does not exist, creating new dictionary');
                $this->SymlinkDictionary = [];
                $this->save(false);
                return;
            }

            try
            {
                $this->SymlinkDictionary = [];

                foreach(ZiProto::decode(IO::fread($this->SymlinkDictionaryPath)) as $entry)
                {
                    $this->SymlinkDictionary[] = SymlinkEntry::fromArray($entry);
                }
            }
            catch(Exception $e)
            {
                $this->SymlinkDictionary = [];

                Console::outDebug('symlink dictionary is corrupted, creating new dictionary');
                $this->save(false);
            }
            finally
            {
                unset($e);
            }
        }

        /**
         * Saves the symlink dictionary to the file
         *
         * @param bool $throw_exception
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         */
        private function save(bool $throw_exception=true): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Insufficient Permissions to write to the system symlink dictionary');
            }

            Console::outDebug(sprintf('saving symlink dictionary to %s', $this->SymlinkDictionaryPath));

            try
            {
                $dictionary = [];
                foreach($this->SymlinkDictionary as $entry)
                {
                    $dictionary[] = $entry->toArray(true);
                }

                IO::fwrite($this->SymlinkDictionaryPath, ZiProto::encode($dictionary));
            }
            catch(Exception $e)
            {
                if($throw_exception)
                {
                    throw new IOException(sprintf('failed to save symlink dictionary to %s', $this->SymlinkDictionaryPath), $e);
                }

                Console::outWarning(sprintf('failed to save symlink dictionary to %s', $this->SymlinkDictionaryPath));
            }
            finally
            {
                unset($e);
            }
        }

        /**
         * @return string
         */
        public function getSymlinkDictionaryPath(): string
        {
            return $this->SymlinkDictionaryPath;
        }

        /**
         * @return array
         */
        public function getSymlinkDictionary(): array
        {
            return $this->SymlinkDictionary;
        }

        /**
         * Checks if a package is defined in the symlink dictionary
         *
         * @param string $package
         * @return bool
         */
        public function exists(string $package): bool
        {
            foreach($this->SymlinkDictionary as $entry)
            {
                if($entry->Package === $package)
                    return true;
            }

            return false;
        }

        /**
         * Adds a new entry to the symlink dictionary
         *
         * @param string $package
         * @param string $unit
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         */
        public function add(string $package, string $unit='main'): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Insufficient Permissions to add to the system symlink dictionary');
            }

            if($this->exists($package))
            {
                $this->remove($package);
            }

            $entry = new SymlinkEntry();
            $entry->Package = $package;
            $entry->ExecutionPolicyName = $unit;

            $this->SymlinkDictionary[] = $entry;
            $this->save();
        }

        /**
         * Removes an entry from the symlink dictionary
         *
         * @param string $package
         * @return void
         * @throws AuthenticationException
         * @throws IOException
         */
        public function remove(string $package): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Insufficient Permissions to remove from the system symlink dictionary');
            }

            if(!$this->exists($package))
            {
                return;
            }

            foreach($this->SymlinkDictionary as $key => $entry)
            {
                if($entry->Package === $package)
                {
                    if($entry->Registered)
                    {
                        $filesystem = new Filesystem();

                        $symlink_name = explode('.', $entry->Package)[count(explode('.', $entry->Package)) - 1];
                        $symlink = self::$BinPath . DIRECTORY_SEPARATOR . $symlink_name;

                        if($filesystem->exists($symlink))
                        {
                            $filesystem->remove($symlink);
                        }
                    }

                    unset($this->SymlinkDictionary[$key]);
                    $this->save();
                    return;
                }
            }

            throw new IOException(sprintf('failed to remove package %s from the symlink dictionary', $package));
        }

        /**
         * Sets the package as registered
         *
         * @param string $package
         * @return void
         * @throws AuthenticationException
         */
        private function setAsRegistered(string $package): void
        {
            foreach($this->SymlinkDictionary as $key => $entry)
            {
                if($entry->Package === $package)
                {
                    $entry->Registered = true;
                    $this->SymlinkDictionary[$key] = $entry;
                    $this->save();
                    return;
                }
            }
        }

        /**
         * Syncs the symlink dictionary with the filesystem
         *
         * @return void
         * @throws AuthenticationException
         */
        public function sync(): void
        {
            if(Resolver::resolveScope() !== Scopes::SYSTEM)
            {
                throw new AuthenticationException('Insufficient Permissions to sync the system symlink dictionary');
            }

            $filesystem = new Filesystem();
            $execution_pointer_manager = new ExecutionPointerManager();
            $package_lock_manager = new PackageLockManager();

            foreach($this->SymlinkDictionary as $entry)
            {
                if($entry->Registered)
                    continue;

                $symlink_name = explode('.', $entry->Package)[count(explode('.', $entry->Package)) - 1];
                $symlink = self::$BinPath . DIRECTORY_SEPARATOR . $symlink_name;

                if($filesystem->exists($symlink))
                {
                    Console::outWarning(sprintf('Symlink %s already exists, skipping', $symlink));
                    continue;
                }

                try
                {
                    $package_entry = $package_lock_manager->getPackageLock()->getPackage($entry->Package);

                    if($package_entry === null)
                    {
                        Console::outWarning(sprintf('Package %s is not installed, skipping', $entry->Package));
                        continue;
                    }

                    $latest_version = $package_entry->getLatestVersion();

                }
                catch(Exception $e)
                {
                    $filesystem->remove($symlink);
                    Console::outWarning(sprintf('Failed to get package %s, skipping', $entry->Package));
                    continue;
                }

                try
                {
                    $entry_point_path = $execution_pointer_manager->getEntryPointPath($entry->Package, $latest_version, $entry->ExecutionPolicyName);
                    $filesystem->symlink($entry_point_path, $symlink);
                }
                catch(Exception $e)
                {
                    $filesystem->remove($symlink);
                    Console::outWarning(sprintf('Failed to create symlink %s, skipping', $symlink));
                    continue;
                }
                finally
                {
                    unset($e);
                }

                $this->setAsRegistered($entry->Package);

            }
        }
    }