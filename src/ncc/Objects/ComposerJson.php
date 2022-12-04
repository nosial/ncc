<?php

    /** @noinspection PhpMissingFieldTypeInspection */

    namespace ncc\Objects;

    use ncc\Abstracts\ComposerPackageTypes;
    use ncc\Abstracts\ComposerStabilityTypes;
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
        public $Name;

        /**
         * A short description of the package. Usually
         * this is one line long
         *
         * @var string
         */
        public $Description;

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
        public $Version;

        /**
         * The type of package, it defaults to library
         *
         * @var string
         */
        public $Type;

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
        public $Keywords;

        /**
         * A URL to the website of the project
         *
         * @var string|null
         */
        public $Homepage;

        /**
         * A relative path to the readme document
         *
         * @var string|null
         */
        public $Readme;

        /**
         * Release date of the version
         *
         * YYY-MM-DD format or YYY-MM-DD HH:MM:SS
         *
         * @var string|null
         */
        public $Time;

        /**
         * The license of the package. This can either be a string or
         * an array of strings
         *
         * @var string|string[]|null
         */
        public $License;

        /**
         * @var Author[]|null
         */
        public $Authors;

        /**
         * @var Support|null
         */
        public $Support;

        /**
         * Map of packages required by this package. The package
         * will not be installed unless those requirements can be met
         *
         * @var PackageLink[]|null
         */
        public $Require;

        /**
         * Map of packages required for developing this package, or running tests,
         * etc. The dev requirements of the root package are installed by default.
         * Both install or update support the --no-dev option that prevents dev
         * dependencies from being installed.
         *
         * @var PackageLink[]|null
         */
        public $RequireDev;

        /**
         * Map of packages that conflict with this version of this package. They will
         * not be allowed to be installed together with your package.
         *
         * @var PackageLink[]|null
         */
        public $Conflict;

        /**
         * Map of packages that are replaced by this package. This allows you to fork a
         * package, publish it under a different name with its own version numbers,
         * while packages requiring the original package continue to work with your fork
         * because it replaces the original package.
         *
         * @var PackageLink[]|null
         */
        public $Replace;

        /**
         * Map of packages that are provided by this package. This is mostly useful for
         * implementations of common interfaces. A package could depend on some virtual
         * package e.g. psr/logger-implementation, any library that implements this logger
         * interface would list it in provide. Implementors can then be found on Packagist.org.
         *
         * @var PackageLink[]|null
         */
        public $Provide;

        /**
         * Suggested packages that can enhance or work well with this package. These are
         * informational and are displayed after the package is installed, to give your
         * users a hint that they could add more packages, even though they are not strictly
         * required.
         *
         * @var Suggestion[]|null
         */
        public $Suggest;

        /**
         * Autoload mapping for a PHP autoloader.
         *
         * @var Autoloader|null
         */
        public $Autoload;

        /**
         * This section allows defining autoload rules for development purposes.
         *
         * @var Autoloader|null
         */
        public $AutoloadDev;

        /**
         * A list of paths which should get appended to PHP's include_path.
         *
         * @var string[]|null
         */
        public $IncludePath;

        /**
         * Defines the installation target.
         *
         * @var string|null
         */
        public $TargetDirectory;

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
        public $MinimumStability;

        /**
         * When this is enabled, Composer will prefer more stable packages over
         * unstable ones when finding compatible stable packages is possible.
         * If you require a dev version or only alphas are available for a package,
         * those will still be selected granted that the minimum-stability allows for it.
         *
         * @var bool
         */
        public $PreferStable;

        /**
         * Custom package repositories to use.
         *
         * @var array|null
         */
        public $Repositories;

        /**
         * A set of configuration options. It is only used for projects.
         *
         * @var array|null
         */
        public $Configuration;

        /**
         * Composer allows you to hook into various parts of the installation
         * process through the use of scripts.
         *
         * @var array|null
         */
        public $Scripts;

        /**
         * Arbitrary extra data for consumption by scripts.
         *
         * @var array|null
         */
        public $Extra;

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
        public $Archive;

        /**
         * Indicates whether this package has been abandoned.
         *
         * @var bool
         */
        public $Abandoned;

        /**
         * A list of regex patterns of branch names that are
         * non-numeric (e.g. "latest" or something), that will
         * NOT be handled as feature branches. This is an array
         * of strings.
         *
         * @var array|null
         */
        public $NonFeatureBranches;

        public function __construct()
        {
            $this->Type = ComposerPackageTypes::Library;
            $this->MinimumStability = ComposerStabilityTypes::Stable;
            $this->PreferStable = false;
            $this->Abandoned = false;
        }

        /**
         * Returns an array representation of the object
         *
         * @return array
         */
        public function toArray(): array
        {
            $_authors = null;
            if($this->Authors !== null && count($this->Authors) > 0)
            {
                $_authors = [];
                foreach($this->Authors as $author)
                {
                    $_authors[] = $author->toArray();
                }
            }

            $_require = null;
            if($this->Require !== null && count($this->Require) > 0)
            {
                $_require = [];
                foreach($this->Require as $require)
                {
                    $_require[$require->PackageName] = $require->Version;
                }
            }

            $_require_dev = null;
            if($this->RequireDev !== null && count($this->RequireDev) > 0)
            {
                $_require_dev = [];
                foreach($this->RequireDev as $require)
                {
                    $_require_dev[$require->PackageName] = $require->Version;
                }
            }

            $_conflict = null;
            if($this->Conflict !== null && count($this->Conflict) > 0)
            {
                $_conflict = [];
                foreach($this->Conflict as $require)
                {
                    $_conflict[$require->PackageName] = $require->Version;
                }
            }

            $_replace = null;
            if($this->Replace !== null && count($this->Replace) > 0)
            {
                $_replace = [];
                foreach($this->Replace as $require)
                {
                    $_replace[$require->PackageName] = $require->Version;
                }
            }

            $_provide = null;
            if($this->Provide !== null && count($this->Provide) > 0)
            {
                $_provide = [];
                foreach($this->Provide as $require)
                {
                    $_provide[$require->PackageName] = $require->Version;
                }
            }

            $_suggest = null;
            if($this->Suggest !== null && count($this->Suggest) > 0)
            {
                $_suggest = [];
                foreach($this->Suggest as $suggestion)
                {
                    $_suggest[$suggestion->PackageName] = $suggestion->Comment;
                }
            }

            return [
                'name' => $this->Name,
                'description' => $this->Description,
                'version' => $this->Version,
                'type' => $this->Type,
                'keywords' => $this->Keywords,
                'homepage' => $this->Homepage,
                'readme' => $this->Readme,
                'time' => $this->Time,
                'license' => $this->License,
                'authors' => $_authors,
                'support' => $this->Support?->toArray(),
                'require' => $_require,
                'require_dev' => $_require_dev,
                'conflict' => $_conflict,
                'replace' => $_replace,
                'provide' => $_provide,
                'suggest' => $_suggest,
                'autoload' => $this->Autoload?->toArray(),
                'autoload-dev' => $this->AutoloadDev?->toArray(),
                'include-path' => $this->IncludePath,
                'target-dir' => $this->TargetDirectory,
                'minimum-stability' => $this->MinimumStability,
                'repositories' => $this->Repositories,
                'config' => $this->Configuration,
                'scripts' => $this->Scripts,
                'extra' => $this->Extra,
                'bin' => $this->Bin,
                'archive' => $this->Archive,
                'abandoned' => $this->Abandoned,
                'non-feature-branches' => $this->NonFeatureBranches
            ];
        }

        public static function fromArray(array $data): self
        {
            $object = new self();

            if(isset($data['name']))
                $object->Name = $data['name'];

            if(isset($data['description']))
                $object->Description = $data['description'];

            if(isset($data['version']))
                $object->Version = $data['version'];

            if(isset($data['type']))
                $object->Type = $data['type'];

            if(isset($data['keywords']))
                $object->Keywords = $data['keywords'];

            if(isset($data['homepage']))
                $object->Homepage = $data['homepage'];

            if(isset($data['readme']))
                $object->Readme = $data['readme'];

            if(isset($data['time']))
                $object->Time = $data['time'];

            if(isset($data['license']))
                $object->License = $data['license'];

            if(isset($data['authors']))
            {
                $object->Authors = [];
                foreach($data['authors'] as $author)
                {
                    $object->Authors[] = Author::fromArray($author);
                }
            }

            if(isset($data['support']))
                $object->Support = Support::fromArray($data['support']);

            if(isset($data['require']))
            {
                $object->Require = [];
                foreach($data['require'] as $package => $version)
                {
                    $object->Require[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['require_dev']))
            {
                $object->RequireDev = [];
                foreach($data['require_dev'] as $package => $version)
                {
                    $object->RequireDev[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['conflict']))
            {
                $object->Conflict = [];
                foreach($data['conflict'] as $package => $version)
                {
                    $object->Conflict[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['replace']))
            {
                $object->Replace = [];
                foreach($data['replace'] as $package => $version)
                {
                    $object->Replace[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['provide']))
            {
                $object->Provide = [];
                foreach($data['provide'] as $package => $version)
                {
                    $object->Provide[] = new PackageLink($package, $version);
                }
            }

            if(isset($data['suggest']))
            {
                $object->Suggest = [];
                foreach($data['suggest'] as $package => $comment)
                {
                    $object->Suggest[] = new Suggestion($package, $comment);
                }
            }

            if(isset($data['autoload']))
                $object->Autoload = Autoloader::fromArray($data['autoload']);

            if(isset($data['autoload-dev']))
                $object->AutoloadDev = Autoloader::fromArray($data['autoload-dev']);

            if(isset($data['include-path']))
                $object->IncludePath = $data['include-path'];

            if(isset($data['target-dir']))
                $object->TargetDirectory = $data['target-dir'];

            if(isset($data['minimum-stability']))
                $object->MinimumStability = $data['minimum-stability'];

            if(isset($data['repositories']))
                $object->Repositories = $data['repositories'];

            if(isset($data['config']))
                $object->Configuration = $data['config'];

            if(isset($data['scripts']))
                $object->Scripts = $data['scripts'];

            if(isset($data['extra']))
                $object->Extra = $data['extra'];

            if(isset($data['bin']))
                $object->Bin = $data['bin'];

            if(isset($data['archive']))
                $object->Archive = $data['archive'];

            if(isset($data['abandoned']))
                $object->Abandoned = $data['abandoned'];

            if(isset($data['non-feature-branches']))
                $object->NonFeatureBranches = $data['non-feature-branches'];

            return $object;
        }
    }