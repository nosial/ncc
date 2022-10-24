<?php

    namespace ncc\Abstracts;

    abstract class SpecialFormat
    {
        const AssemblyName = '%ASSEMBLY.NAME%';

        const AssemblyPackage = '%ASSEMBLY.PACKAGE%';

        const AssemblyDescription = '%ASSEMBLY.DESCRIPTION%';

        const AssemblyCompany = '%ASSEMBLY.COMPANY%';

        const AssemblyProduct = '%ASSEMBLY.PRODUCT%';

        const AssemblyCopyright = '%ASSEMBLY.COPYRIGHT%';

        const AssemblyTrademark = '%ASSEMBLY.TRADEMARK%';

        const AssemblyVersion = '%ASSEMBLY.VERSION%';

        const AssemblyUid = '%ASSEMBLY.UID%';

        const CompileTimestamp = '%COMPILE_TIMESTAMP%';

        const NccBuildVersion = '%NCC_BUILD_VERSION%';

        const NccBuildArgs = '%NCC_BUILD_FLAGS%';

        const NccBuildBranch = '%NCC_BUILD_BRANCH%';
    }