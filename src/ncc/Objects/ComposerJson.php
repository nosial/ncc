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

    use ncc\Enums\ComposerPackageTypes;
    use ncc\Enums\ComposerStabilityTypes;
    use ncc\Objects\ComposerJson\Author;
    use ncc\Objects\ComposerJson\Autoloader;
    use ncc\Objects\ComposerJson\PackageLink;
    use ncc\Objects\ComposerJson\Suggestion;
    use ncc\Objects\ComposerJson\Support;

    class ComposerJson
    {
        /**
         * The name of the package, it consists of
         * the vendor name and project name, seperated by `/`
         *
         * @var string
         */
        public $name;

        /**
         * A short description of the package. Usually
         * this is one line long
         *
         * @var string
         */
        public $description;

        /**
         * The version of the package, in most cases this is not
         * required and should be omitted.
         *
         * If the package repository can infer the version from
         * somewhere. such as the VCS tag name in the VCS repository.
         * In the case it is also recommended to omit it
         *
         * @var string|null
         */
        public $version;

        /**
         * The type of package, it defaults to library
         *
         * @var string
         */
        public $type;

        /**
         * An array of keywords that the package is related to.
         * These can be used for searching and filtering
         *
         * Examples
         *  - logging
         *  - events
         *  - database
         *  - redis
         *  - templating
         *
         * @var string[]
         */
        public $keywords;

        /**
         * A URL to the website of the project
         *
         * @var string|null
         */
        public $homepage;

        /**
         * A relative path to the readme document
         *
         * @var string|null
         */
        public $readme;

        /**
         * Release date of the version
         *
         * YYY-MM-DD format or YYY-MM-DD HH:MM:SS
         *
         * @var string|null
         */
        public $time;

        /**
         * The license of the package. This can either be a string or
         * an array of strings
         *
         * @var string|string[]|null
         */
        public $license;

        /**
         * @var Author[]|null
         */
        public $authors;

        /**
         * @var Support|null
         */
        public $support;

        /**
         * Map of packages required by this package. The package
         * will not be installed unless those requirements can be met
         *
         * @var PackageLink[]|null
         */
        public $require;

        /**
         * Map of packages required for developing this package, or running tests,
         * etc. The dev requirements of the root package are installed by default.
         * Both install or update support the --no-dev option that prevents dev
         * dependencies from being installed.
         *
         * @var PackageLink[]|null
         */
        public $require_dev;

        /**
         * Map of packages that conflict with this version of this package. They will
         * not be allowed to be installed together with your package.
         *
         * @var PackageLink[]|null
         */
        public $conflict;

        /**
         * Map of packages that are replaced by this package. This allows you to fork a
         * package, publish it under a different name with its own version numbers,
         * while packages requiring the original package continue to work with your fork
         * because it replaces the original package.
         *
         * @var PackageLink[]|null
         */
        public $replace;

        /**
         * Map of packages that are provided by this package. This is mostly useful for
         * implementations of common interfaces. A package could depend on some virtual
         * package e.g. psr/logger-implementation, any library that implements this logger
         * interface would list it in provide. Implementors can then be found on Packagist.org.
         *
         * @var PackageLink[]|null
         */
        public $provide;

        /**
         * Suggested packages that can enhance or work well with this package. These are
         * informational and are displayed after the package is installed, to give your
         * users a hint that they could add more packages, even though they are not strictly
         * required.
         *
         * @var Suggestion[]|null
         */
        public $suggest;

        /**
         * Autoload mapping for a PHP autoloader.
         *
         * @var Autoloader|null
         */
        public $autoload;

        /**
         * This section allows defining autoload rules for development purposes.
         *
         * @var Autoloader|null
         */
        public $autoload_dev;

        /**
         * A list of paths which should get appended to PHP's include_path.
         *
         * @var string[]|null
         */
        public $include_path;

        /**
         * Defines the installation target.
         *
         * @var string|null
         */
        public $target_directory;

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
         * @var ComposerPackageTypes|null
         */
        public $minimum_stability;

        /**
         * Custom package repositories to use.
         *
         * @var array|null
         */
        public $repositories;

        /**
         * A set of configuration options. It is only used for projects.
         *
         * @var array|null
         */
        public $configuration;

        /**
         * Composer allows you to hook into various parts of the installation
         * process through the use of scripts.
         *
         * @var array|null
         */
        public $scripts;

        /**
         * Arbitrary extra data for consumption by scripts.
         *
         * @var array|null
         */
        public $extra;

        /**
         * A set of files that should be treated as binaries and made available into the bin-dir (from config).
         *
         * @var array|null
         */
        public $Bin;

        /**
         * A set of options for creating package archives.
         *
         * @var array|null
         */
        public $archive;

        /**
         * Indicates whether this package has been abandoned.
         *
         * @var bool
         */
        public $abandoned;

        /**
         * A list of regex patterns of branch names that are
         * non-numeric (e.g. "latest" or something), that will
         * NOT be handled as feature branches. This is an array
         * of strings.
         *
         * @var array|null
         */
        public $non_feature_branches;

        public function __construct()
        {
            $this->type = ComposerPackageTypes::LIBRARY;
            $this->minimum_stability = ComposerStabilityTypes::STABLE;
            $this->PreferStable = false;
            $this->abandoned = false;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
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
                'bin' => $this->Bin,
                'archive' => $this->archive,
                'abandoned' => $this->abandoned,
                'non-feature-branches' => $this->non_feature_branches
            ];
        }

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
                $object->Bin = $data['bin'];
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