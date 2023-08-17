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
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\IOException;
    use ncc\Objects\DefinedRemoteSource;
    use ncc\Utilities\IO;
    use ncc\Utilities\PathFinder;
    use ncc\ZiProto\ZiProto;

    class RemoteSourcesManager
    {
        /**
         * An array of all the defined remote sources
         *
         * @var DefinedRemoteSource[]
         */
        private $Sources;

        /**
         * The path to the remote sources file
         *
         * @var string
         */
        private $DefinedSourcesPath;

        /**
         * Public Constructor
         *
         * @throws InvalidScopeException
         */
        public function __construct()
        {
            $this->DefinedSourcesPath = PathFinder::getRemoteSources(Scopes::SYSTEM);

            $this->load();
        }

        /**
         * Loads an existing remote sources file, or creates a new one if it doesn't exist
         *
         * @return void
         */
        public function load(): void
        {
            $this->Sources = [];

            try
            {

                if(file_exists($this->DefinedSourcesPath))
                {
                    $sources = ZiProto::decode(IO::fread($this->DefinedSourcesPath));
                    $this->Sources = [];
                    foreach($sources as $source)
                        $this->Sources[] = DefinedRemoteSource::fromArray($source);
                }
            }
            catch(Exception $e)
            {
                unset($e);
            }
        }

        /**
         * Saves the remote sources file to disk
         *
         * @return void
         * @throws IOException
         */
        public function save(): void
        {
            $sources = [];
            foreach($this->Sources as $source)
                $sources[] = $source->toArray(true);

            IO::fwrite($this->DefinedSourcesPath, ZiProto::encode($sources));
        }

        /**
         * Adds a new remote source to the list
         *
         * @param DefinedRemoteSource $source
         * @return bool
         */
        public function addRemoteSource(DefinedRemoteSource $source): bool
        {
            foreach($this->Sources as $existingSource)
            {
                if($existingSource->Name === $source->Name)
                    return false;
            }

            $this->Sources[] = $source;
            return true;
        }

        /**
         * Gets a remote source by its name
         *
         * @param string $name
         * @return DefinedRemoteSource|null
         */
        public function getRemoteSource(string $name): ?DefinedRemoteSource
        {
            foreach($this->Sources as $source)
            {
                if($source->Name === $name)
                {
                    return $source;
                }
            }

            return null;
        }

        /**
         * Deletes an existing remote source
         *
         * @param string $name
         * @return bool
         */
        public function deleteRemoteSource(string $name): bool
        {
            foreach($this->Sources as $index => $source)
            {
                if($source->Name === $name)
                {
                    unset($this->Sources[$index]);
                    return true;
                }
            }

            return false;
        }

        /**
         * Returns an array of all the defined remote sources
         *
         * @return DefinedRemoteSource[]
         */
        public function getSources(): array
        {
            if($this->Sources == null)
                $this->load();
            return $this->Sources;
        }
    }