<?php

    namespace ncc\Abstracts;

    use ncc\Exceptions\AccessDeniedException;
    use ncc\Exceptions\AutoloadGeneratorException;
    use ncc\Exceptions\BuildConfigurationNotFoundException;
    use ncc\Exceptions\BuildException;
    use ncc\Exceptions\ComponentVersionNotFoundException;
    use ncc\Exceptions\ConstantReadonlyException;
    use ncc\Exceptions\DirectoryNotFoundException;
    use ncc\Exceptions\FileNotFoundException;
    use ncc\Exceptions\InvalidConstantNameException;
    use ncc\Exceptions\InvalidCredentialsEntryException;
    use ncc\Exceptions\InvalidPackageException;
    use ncc\Exceptions\InvalidPackageNameException;
    use ncc\Exceptions\InvalidProjectBuildConfiguration;
    use ncc\Exceptions\InvalidProjectConfigurationException;
    use ncc\Exceptions\InvalidProjectNameException;
    use ncc\Exceptions\InvalidPropertyValueException;
    use ncc\Exceptions\InvalidScopeException;
    use ncc\Exceptions\InvalidVersionConfigurationException;
    use ncc\Exceptions\InvalidVersionNumberException;
    use ncc\Exceptions\MalformedJsonException;
    use ncc\Exceptions\NoUnitsFoundException;
    use ncc\Exceptions\ProjectAlreadyExistsException;
    use ncc\Exceptions\RuntimeException;
    use ncc\Exceptions\UnsupportedCompilerExtensionException;
    use ncc\Exceptions\UnsupportedPackageException;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    abstract class ExceptionCodes
    {
        /**
         * @see InvalidProjectConfigurationException
         */
        const InvalidProjectConfigurationException = -1700;

        /**
         * @see FileNotFoundException;
         */
        const FileNotFoundException = -1701;

        /**
         * @see DirectoryNotFoundException
         */
        const DirectoryNotFoundException = -1702;

        /**
         * @see InvalidScopeException
         */
        const InvalidScopeException = -1703;

        /**
         * @see AccessDeniedException
         */
        const AccessDeniedException = -1704;

        /**
         * @see MalformedJsonException
         */
        const MalformedJsonException = -1705;

        /**
         * @see RuntimeException
         */
        const RuntimeException = -1706;

        /**
         * @see InvalidCredentialsEntryException
         */
        const InvalidCredentialsEntryException = -1707;

        /**
         * @see ComponentVersionNotFoundException
         */
        const ComponentVersionNotFoundException = -1708;

        /**
         * @see ConstantReadonlyException
         */
        const ConstantReadonlyException = -1709;

        /**
         * @see InvalidPackageNameException
         */
        const InvalidPackageNameException = -1710;

        /**
         * @see InvalidVersionNumberException
         */
        const InvalidVersionNumberException = -1711;

        /**
         * @see InvalidProjectNameException
         */
        const InvalidProjectNameException = -1712;

        /**
         * @see ProjectAlreadyExistsException
         */
        const ProjectAlreadyExistsException = -1713;

        /**
         * @see AutoloadGeneratorException
         */
        const AutoloadGeneratorException = -1714;

        /**
         * @see NoUnitsFoundException
         */
        const NoUnitsFoundException = -1715;

        /**
         * @see UnsupportedPackageException
         */
        const UnsupportedPackageException = -1716;

        /**
         * @see NotImplementedException
         */
        const NotImplementedException = -1717;

        /**
         * @see InvalidPackageException
         */
        const InvalidPackageException = -1718;

        /**
         * @see InvalidConstantNameException
         */
        const InvalidConstantNameException = -1719;

        /**
         * @see PackagePreparationFailedException
         */
        const PackagePreparationFailedException = -1720;

        /**
         * @see BuildConfigurationNotFoundException
         */
        const BuildConfigurationNotFoundException = -1721;

        /**
         * @see InvalidProjectBuildConfiguration
         */
        const InvalidProjectBuildConfiguration = -1722;

        /**
         * @see UnsupportedCompilerExtensionException
         */
        const UnsupportedCompilerExtensionException = -1723;

        /**
         * @see InvalidPropertyValueException
         */
        const InvalidPropertyValueException = -1724;

        /**
         * @see InvalidVersionConfigurationException
         */
        const InvalidVersionConfigurationException = -1725;

        /**
         * @see UnsupportedExtensionVersionException
         */
        const UnsupportedExtensionVersionException = -1726;

        /**
         * @see BuildException
         */
        const BuildException = -1727;

        /**
         * All the exception codes from NCC
         */
        const All = [
            self::InvalidProjectConfigurationException,
            self::FileNotFoundException,
            self::DirectoryNotFoundException,
            self::InvalidScopeException,
            self::AccessDeniedException,
            self::MalformedJsonException,
            self::RuntimeException,
            self::InvalidCredentialsEntryException,
            self::ComponentVersionNotFoundException,
            self::ConstantReadonlyException,
            self::InvalidPackageNameException,
            self::InvalidVersionNumberException,
            self::InvalidProjectNameException,
            self::ProjectAlreadyExistsException,
            self::AutoloadGeneratorException,
            self::NoUnitsFoundException,
            self::UnsupportedPackageException,
            self::NotImplementedException,
            self::InvalidPackageException,
            self::InvalidConstantNameException,
            self::PackagePreparationFailedException,
            self::BuildConfigurationNotFoundException,
            self::InvalidProjectBuildConfiguration,
            self::UnsupportedCompilerExtensionException,
            self::InvalidPropertyValueException,
            self::InvalidVersionConfigurationException,
            self::UnsupportedExtensionVersionException,
            self::BuildException
        ];
    }