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

namespace ncc\Abstracts;

    use ncc\Exceptions\SymlinkException;

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
         * @see PackageParsingException
         */
        const PackageParsingException = -1728;

        /**
         * @see PackageLockException
         */
        const PackageLockException = -1729;

        /**
         * @see InstallationException
         */
        const InstallationException = -1730;

        /**
         * @see UnsupportedComponentTypeException
         */
        const UnsupportedComponentTypeException = -1731;

        /**
         * @see ComponentDecodeException
         */
        const ComponentDecodeException = -1732;

        /**
         * @see ComponentChecksumException
         */
        const ComponentChecksumException = -1733;

        /**
         * @see ResourceChecksumException
         */
        const ResourceChecksumException = -1734;

        /**
         * @see IOException
         */
        const IOException = -1735;

        /**
         * @see UnsupportedRunnerException
         */
        const UnsupportedRunnerException = -1736;

        /**
         * @see VersionNotFoundException
         */
        const VersionNotFoundException = -1737;

        /**
         * @see UndefinedExecutionPolicyException
         */
        const UndefinedExecutionPolicyException = -1738;

        /**
         * @see InvalidExecutionPolicyName
         */
        const InvalidExecutionPolicyName = -1739;

        /**
         * @see ProjectConfigurationNotFoundException
         */
        const ProjectConfigurationNotFoundException = -1740;

        /**
         * @see RunnerExecutionException
         */
        const RunnerExecutionException = -1741;

        /**
         * @see NoAvailableUnitsException
         */
        const NoAvailableUnitsException = -1742;

        /**
         * @see ExecutionUnitNotFoundException
         */
        const ExecutionUnitNotFoundException = -1743;

        /**
         * @see PackageAlreadyInstalledException
         */
        const PackageAlreadyInstalledException = -1744;

        /**
         * @see PackageNotFoundException
         */
        const PackageNotFoundException = -1745;

        /**
         * @see ComposerDisabledException
         */
        const ComposerDisabledException = -1746;

        /**
         * @see InternalComposerNotAvailableException
         */
        const InternalComposerNotAvailable = -1747;

        /**
         * @see ComposerNotAvailableException
         */
        const ComposerNotAvailableException = -1748;

        /**
         * @see ComposerException
         */
        const ComposerException = -1749;

        /**
         * @see UserAbortedOperationException
         */
        const UserAbortedOperationException = -1750;

        /**
         * @see MissingDependencyException
         */
        const MissingDependencyException = -1751;

        /**
         * @see HttpException
         */
        const HttpException = -1752;

        /**
         * @see UnsupportedRemoteSourceTypeException
         */
        const UnsupportedRemoteSourceTypeException = -1753;

        /**
         * @see GitCloneException
         */
        const GitCloneException = -1754;

        /**
         * @see GitCheckoutException
         */
        const GitCheckoutException = -1755;

        /**
         * @see GitlabServiceException
         */
        const GitlabServiceException = -1756;

        /**
         * @see ImportException
         */
        const ImportException = -1757;

        /**
         * @see GitTagsException
         */
        const GitTagsException = -1758;

        /**
         * @see GithubServiceException
         */
        const GithubServiceException = -1759;

        /**
         * @see AuthenticationException
         */
        const AuthenticationException = -1760;

        /**
         * @see NotSupportedException
         */
        const NotSupportedException = -1761;

        /**
         * @see UnsupportedProjectTypeException
         */
        const UnsupportedProjectTypeException = -1762;

        /**
         * @see UnsupportedArchiveException
         */
        const UnsupportedArchiveException = -1763;

        /**
         * @see ArchiveException
         */
        const ArchiveException = -1764;

        /**
         * @see PackageFetchException
         */
        const PackageFetchException = -1765;

        /**
         * @see InvalidBuildConfigurationException
         */
        const InvalidBuildConfigurationException = -1766;

        /**
         * @see InvalidBuildConfigurationNameException
         */
        const InvalidDependencyConfiguration = -1767;

        /**
         * @see SymlinkException
         */
        const SymlinkException = -1768;

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
            self::BuildException,
            self::PackageParsingException,
            self::PackageLockException,
            self::InstallationException,
            self::UnsupportedComponentTypeException,
            self::ComponentDecodeException,
            self::ResourceChecksumException,
            self::IOException,
            self::UnsupportedRunnerException,
            self::VersionNotFoundException,
            self::UndefinedExecutionPolicyException,
            self::InvalidExecutionPolicyName,
            self::ProjectConfigurationNotFoundException,
            self::RunnerExecutionException,
            self::NoAvailableUnitsException,
            self::ExecutionUnitNotFoundException,
            self::PackageAlreadyInstalledException,
            self::PackageNotFoundException,
            self::ComposerDisabledException,
            self::InternalComposerNotAvailable,
            self::ComposerNotAvailableException,
            self::ComposerException,
            self::UserAbortedOperationException,
            self::MissingDependencyException,
            self::HttpException,
            self::UnsupportedRemoteSourceTypeException,
            self::GitCloneException,
            self::GitCheckoutException,
            self::GitlabServiceException,
            self::GitTagsException,
            self::AuthenticationException,
            self::NotSupportedException,
            self::UnsupportedProjectTypeException,
            self::UnsupportedArchiveException,
            self::ArchiveException,
            self::PackageFetchException,
            self::InvalidBuildConfigurationException,
            self::InvalidDependencyConfiguration,
            self::SymlinkException,
        ];
    }