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

    namespace ncc\Objects;

    use ncc\Enums\Types\ComposerPackageTypes;
    use ncc\Enums\Types\ComposerStabilityTypes;
    use ncc\Interfaces\SerializableObjectInterface;
    use ncc\Objects\ComposerJson\Author;
    use ncc\Objects\ComposerJson\Autoloader;
    use ncc\Objects\ComposerJson\PackageLink;
    use ncc\Objects\ComposerJson\Suggestion;
    use ncc\Objects\ComposerJson\Support;

    class ComposerJson implements SerializableObjectInterface
    {
        /**
         * @var string
         */
        private $name;

        /**
         * @var string|null
         */
        private $description;

        /**
         * @var string|null
         */
        private $version;

        /**
         * @var string
         */
        private $type;

        /**
         * @var string[]
         */
        private $keywords;

        /**
         * @var string|null
         */
        private $homepage;

        /**
         * @var string|null
         */
        private $readme;

        /**
         * @var string|null
         */
        private $time;

        /**
         * @var string|string[]|null
         */
        private $license;

        /**
         * @var Author[]|null
         */
        private $authors;

        /**
         * @var Support|null
         */
        private $support;

        /**
         * @var PackageLink[]|null
         */
        private $require;

        /**
         * @var PackageLink[]|null
         */
        private $require_dev;

        /**
         * @var PackageLink[]|null
         */
        private $conflict;

        /**
         * @var PackageLink[]|null
         */
        private $replace;

        /**
         * @var PackageLink[]|null
         */
        private $provide;

        /**
         * @var Suggestion[]|null
         */
        private $suggest;

        /**
         * @var Autoloader|null
         */
        private $autoload;

        /**
         * @var Autoloader|null
         */
        private $autoload_dev;

        /**
         * @var string[]|null
         */
        private $include_path;

        /**
         * @var string|null
         */
        private $target_directory;

        /**
         * @var string|null
         * @see ComposerStabilityTypes
         */
        private $minimum_stability;

        /**
         * @var array|null
         */
        private $repositories;

        /**
         * @var array|null
         */
        private $configuration;

        /**
         * @var array|null
         */
        private $scripts;

        /**
         * @var array|null
         */
        private $extra;

        /**
         * @var array|null
         */
        private $bin;

        /**
         * @var array|null
         */
        private $archive;

        /**
         * @var bool
         */
        private $abandoned;

        /**
         * @var array|null
         */
        private $non_feature_branches;

        /**
         * Public Constructor.
         */
        public function __construct()
        {
            $this->type = ComposerPackageTypes::LIBRARY->value;
            $this->minimum_stability = ComposerStabilityTypes::STABLE->value;
            $this->abandoned = false;
        }

        /**
         * Returns the name of the package; it consists of
         * the vendor name and project name, seperated by `/`
         *
         * @return string
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Returns the description of the package
         *
         * @return ?string
         */
        public function getDescription(): ?string
        {
            return $this->description;
        }

        /**
         * Optional. Returns the version of the package, in most cases this is not
         * required and should be omitted.
         *
         * @return string|null
         */
        public function getVersion(): ?string
        {
            return $this->version;
        }

        /**
         * Returns the type of package, it defaults to library
         *
         * @return string
         */
        public function getType(): string
        {
            return $this->type;
        }

        /**
         * Returns an array of keywords that the package is related to.
         * These can be used for searching and filtering
         *
         * Examples
         *  - logging
         *  - events
         *  - database
         *  - redis
         *  - templating
         *
         * @return string[]
         */
        public function getKeywords(): array
        {
            return $this->keywords;
        }

        /**
         * Optional. Returns a URL to the website of the project
         *
         * @return string|null
         */
        public function getHomepage(): ?string
        {
            return $this->homepage;
        }

        /**
         * Optional. Returns a relative path to the readme document
         *
         * @return string|null
         */
        public function getReadme(): ?string
        {
            return $this->readme;
        }

        /**
         * Optional. Returns the release date of the version
         * YYY-MM-DD format or YYY-MM-DD HH:MM:SS
         *
         * @return string|null
         */
        public function getTime(): ?string
        {
            return $this->time;
        }

        /**
         * The license of the package. This can either be a string or
         * an array of strings
         *
         * @return string|string[]|null
         */
        public function getLicense(): array|string|null
        {
            return $this->license;
        }

        /**
         * Optional. Returns the authors of the package
         *
         * @return Author[]|null
         */
        public function getAuthors(): ?array
        {
            return $this->authors;
        }

        /**
         * Optional. Returns the support information of the package
         *
         * @return Support|null
         */
        public function getSupport(): ?Support
        {
            return $this->support;
        }

        /**
         * Optional. Returns the required packages of the package
         *
         * @return PackageLink[]|null
         */
        public function getRequire(): ?array
        {
            return $this->require;
        }

        /**
         * Optional. Returns the required development packages of the package
         *
         * @return PackageLink[]|null
         */
        public function getRequireDev(): ?array
        {
            return $this->require_dev;
        }

        /**
         * Optional. Returns the conflicting packages of the package
         *
         * @return PackageLink[]|null
         */
        public function getConflict(): ?array
        {
            return $this->conflict;
        }

        /**
         * Optional. Returns the replaced packages of the package
         *
         * @return PackageLink[]|null
         */
        public function getReplace(): ?array
        {
            return $this->replace;
        }

        /**
         * Optional. Returns the provided packages of the package
         *
         * @return PackageLink[]|null
         */
        public function getProvide(): ?array
        {
            return $this->provide;
        }

        /**
         * Optional. Returns the suggested packages of the package
         *
         * @return Suggestion[]|null
         */
        public function getSuggest(): ?array
        {
            return $this->suggest;
        }

        /**
         * Optional. Returns the autoload mapping for a PHP autoloader.
         *
         * @return Autoloader|null
         */
        public function getAutoload(): ?Autoloader
        {
            return $this->autoload;
        }

        /**
         * Optional. Returns the autoload mapping for a PHP autoloader.
         *
         * @return Autoloader|null
         */
        public function getAutoloadDev(): ?Autoloader
        {
            return $this->autoload_dev;
        }

        /**
         * Optional. Returns a list of paths which should get appended to PHP's include_path.
         *
         * @return string[]|null
         */
        public function getIncludePath(): ?array
        {
            return $this->include_path;
        }

        /**
         * Optional. Returns the installation target.
         *
         * @return string|null
         */
        public function getTargetDirectory(): ?string
        {
            return $this->target_directory;
        }

        /**
         * This defines the default behavior for filtering packages by
         * stability. This defaults to stable, so if you rely on a dev package,
         * you should specify it in your file to avoid surprises.
         *
         * All versions of each package are checked for stability, and those that
         * are less stable than the minimum-stability setting will be ignored when
         * resolving your project dependencies. (Note that you can also specify
         * stability requirements on a per-package basis using stability flags
         * in the version constraints that you specify in a require block
         *
         * @return string|null
         * @see ComposerStabilityTypes
         */
        public function getMinimumStability(): ?string
        {
            return $this->minimum_stability;
        }

        /**
         * Optional. Returns custom package repositories to use.
         *
         * @return array|null
         */
        public function getRepositories(): ?array
        {
            return $this->repositories;
        }

        /**
         * Optional. Returns a set of configuration options. It is only used for projects.
         *
         * @return array|null
         */
        public function getConfiguration(): ?array
        {
            return $this->configuration;
        }

        /**
         * Optional. Returns composer allows you to hook into various parts of the installation
         *
         * @return array|null
         */
        public function getScripts(): ?array
        {
            return $this->scripts;
        }

        /**
         * Optional. Returns arbitrary extra data for consumption by scripts.
         *
         * @return array|null
         */
        public function getExtra(): ?array
        {
            return $this->extra;
        }

        /**
         * Optional. Returns a set of files that should be treated as binaries and made available into the bin-dir (from config).
         *
         * @return array|null
         */
        public function getBin(): ?array
        {
            return $this->bin;
        }

        /**
         * Optional. Returns a set of options for creating package archives.
         *
         * @return array|null
         */
        public function getArchive(): ?array
        {
            return $this->archive;
        }

        /**
         * Returns whether this package has been abandoned.
         *
         * @return bool
         */
        public function isAbandoned(): bool
        {
            return $this->abandoned;
        }

        /**
         * Optional. Returns a list of regex patterns of branch names that are
         *
         * @return array|null
         */
        public function getNonFeatureBranches(): ?array
        {
            return $this->non_feature_branches;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $_authors = null;
            if($this->authors !== null && count($this->authors) > 0)
            {
                $_authors = [];
                foreach($this->authors as $author)
                {
                    $_authors[] = $author->toArray();
                }
            }

            $_require = null;
            if($this->require !== null && count($this->require) > 0)
            {
                $_require = [];
                foreach($this->require as $require)
                {
                    $_require[$require->getPackageName()] = $require->getVersion();
                }
            }

            $_require_dev = null;
            if($this->require_dev !== null && count($this->require_dev) > 0)
            {
                $_require_dev = [];
                foreach($this->require_dev as $require)
                {
                    $_require_dev[$require->getPackageName()] = $require->getVersion();
                }
            }

            $_conflict = null;
            if($this->conflict !== null && count($this->conflict) > 0)
            {
                $_conflict = [];
                foreach($this->conflict as $require)
                {
                    $_conflict[$require->getPackageName()] = $require->getVersion();
                }
            }

            $_replace = null;
            if($this->replace !== null && count($this->replace) > 0)
            {
                $_replace = [];
                foreach($this->replace as $require)
                {
                    $_replace[$require->getPackageName()] = $require->getVersion();
                }
            }

            $_provide = null;
            if($this->provide !== null && count($this->provide) > 0)
            {
                $_provide = [];
                foreach($this->provide as $require)
                {
                    $_provide[$require->getPackageName()] = $require->getVersion();
                }
            }

            $_suggest = null;
            if($this->suggest !== null && count($this->suggest) > 0)
            {
                $_suggest = [];
                foreach($this->suggest as $suggestion)
                {
                    $_suggest[$suggestion->getPackageName()] = $suggestion->getComment();
                }
            }

            return [
                'name' => $this->name,
                'description' => $this->description,
                'version' => $this->version,
                'type' => $this->type,
                'keywords' => $this->keywords,
                'homepage' => $this->homepage,
                'readme' => $this->readme,
                'time' => $this->time,
                'license' => $this->license,
                'authors' => $_authors,
                'support' => $this->support?->toArray(),
                'require' => $_require,
                'require_dev' => $_require_dev,
                'conflict' => $_conflict,
                'replace' => $_replace,
                'provide' => $_provide,
                'suggest' => $_suggest,
                'autoload' => $this->autoload?->toArray(),
                'autoload-dev' => $this->autoload_dev?->toArray(),
                'include-path' => $this->include_path,
                'target-dir' => $this->target_directory,
                'minimum-stability' => $this->minimum_stability,
                'repositories' => $this->repositories,
                'config' => $this->configuration,
                'scripts' => $this->scripts,
                'extra' => $this->extra,
                'bin' => $this->bin,
                'archive' => $this->archive,
                'abandoned' => $this->abandoned,
                'non-feature-branches' => $this->non_feature_branches
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['name']))
            {
                $object->name = $data['name'];
            }

            if(isset($data['description']))
            {
                $object->description = $data['description'];
            }

            if(isset($data['version']))
            {
                $object->version = $data['version'];
            }

            if(isset($data['type']))
            {
                $object->type = $data['type'];
            }

            if(isset($data['keywords']))
            {
                $object->keywords = $data['keywords'];
            }

            if(isset($data['homepage']))
            {
                $object->homepage = $data['homepage'];
            }

            if(isset($data['readme']))
            {
                $object->readme = $data['readme'];
            }

            if(isset($data['time']))
            {
                $object->time = $data['time'];
            }

            if(isset($data['license']))
            {
                $object->license = $data['license'];
            }

            if(isset($data['authors']))
            {
                $object->authors = [];
                foreach($data['authors'] as $author)
                {
                    $object->authors[] = Author::fromArray($author);
                }
            }

            if(isset($data['support']))
            {
                $object->support = Support::fromArray($data['support']);
            }

            if(isset($data['require']))
            {
                $object->require = [];
                foreach($data['require'] as $package => $version)
                {
                    $object->require[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['require_dev']))
            {
                $object->require_dev = [];
                foreach($data['require_dev'] as $package => $version)
                {
                    $object->require_dev[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['conflict']))
            {
                $object->conflict = [];
                foreach($data['conflict'] as $package => $version)
                {
                    $object->conflict[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['replace']))
            {
                $object->replace = [];
                foreach($data['replace'] as $package => $version)
                {
                    $object->replace[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['provide']))
            {
                $object->provide = [];
                foreach($data['provide'] as $package => $version)
                {
                    $object->provide[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['suggest']))
            {
                $object->suggest = [];
                foreach($data['suggest'] as $package => $comment)
                {
                    $object->suggest[] = new Suggestion($package, $comment);
                }
            }

            if(isset($data['autoload']))
            {
                $object->autoload = Autoloader::fromArray($data['autoload']);
            }

            if(isset($data['autoload-dev']))
            {
                $object->autoload_dev = Autoloader::fromArray($data['autoload-dev']);
            }

            if(isset($data['include-path']))
            {
                $object->include_path = $data['include-path'];
            }

            if(isset($data['target-dir']))
            {
                $object->target_directory = $data['target-dir'];
            }

            if(isset($data['minimum-stability']))
            {
                $object->minimum_stability = $data['minimum-stability'];
            }

            if(isset($data['repositories']))
            {
                $object->repositories = $data['repositories'];
            }

            if(isset($data['config']))
            {
                $object->configuration = $data['config'];
            }

            if(isset($data['scripts']))
            {
                $object->scripts = $data['scripts'];
            }

            if(isset($data['extra']))
            {
                $object->extra = $data['extra'];
            }

            if(isset($data['bin']))
            {
                $object->bin = $data['bin'];
            }

            if(isset($data['archive']))
            {
                $object->archive = $data['archive'];
            }

            if(isset($data['abandoned']))
            {
                $object->abandoned = $data['abandoned'];
            }

            if(isset($data['non-feature-branches']))
            {
                $object->non_feature_branches = $data['non-feature-branches'];
            }

            return $object;
        }
    }