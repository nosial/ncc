<?php

    namespace ncc\Abstracts;

    abstract class ComposerPackageTypes
    {
        /**
         * This is the default. It will copy the files to `vendor`
         */
        const Library = 'library';

        /**
         * This denotes a project rather than a library. For example
         * application shells like the Symfony standard edition, CMSs
         * like the SilverStripe instlaler or full-fledged applications
         * distributed as packages. This can for example be used by IDEs
         * to provide listings of projects to initialize when creating
         * a new workspace.
         */
        const Project = 'project';

        /**
         * An empty package that contains requirements and will trigger
         * their installation, but contains no files and will not write
         * anything to the filesystem. As such, it does not require a
         * a dist or source key to be installable
         */
        const MetaPackage = 'metapackage';

        /**
         * A package of type `composer-plugin` may provide an installer
         * for other packages that have a custom type.
         */
        const ComposerPlugin = 'composer-plugin';
    }