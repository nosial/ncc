<?php

    namespace ncc\Classes\NccExtension;

    use ncc\Abstracts\SpecialConstants\BuildConstants;
    use ncc\Abstracts\SpecialConstants\DateTimeConstants;
    use ncc\Abstracts\SpecialConstants\InstallConstants;
    use ncc\Abstracts\SpecialConstants\AssemblyConstants;
    use ncc\Objects\InstallationPaths;
    use ncc\Objects\Package;
    use ncc\Objects\ProjectConfiguration;
    use ncc\Objects\ProjectConfiguration\Assembly;

    class ConstantCompiler
    {
        /**
         * Compiles assembly constants about the project (Usually used during compiling time)
         *
         * @param string|null $input
         * @param Assembly $assembly
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileAssemblyConstants(?string $input, Assembly $assembly): ?string
        {
            if($input == null)
                return null;

            $input = str_replace(AssemblyConstants::AssemblyName, $assembly->Name, $input);
            $input = str_replace(AssemblyConstants::AssemblyPackage, $assembly->Package, $input);
            $input = str_replace(AssemblyConstants::AssemblyDescription, $assembly->Description, $input);
            $input = str_replace(AssemblyConstants::AssemblyCompany, $assembly->Company, $input);
            $input = str_replace(AssemblyConstants::AssemblyProduct, $assembly->Product, $input);
            $input = str_replace(AssemblyConstants::AssemblyCopyright, $assembly->Copyright, $input);
            $input = str_replace(AssemblyConstants::AssemblyTrademark, $assembly->Trademark, $input);
            $input = str_replace(AssemblyConstants::AssemblyVersion, $assembly->Version, $input);
            $input = str_replace(AssemblyConstants::AssemblyUid, $assembly->UUID, $input);

            return $input;
        }

        /**
         * Compiles build constants about the NCC build (Usually used during compiling time)
         *
         * @param string|null $input
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileBuildConstants(?string $input): ?string
        {
            if($input == null)
                return null;

            $input = str_replace(BuildConstants::CompileTimestamp, time(), $input);
            $input = str_replace(BuildConstants::NccBuildVersion, NCC_VERSION_NUMBER, $input);
            $input = str_replace(BuildConstants::NccBuildFlags, implode(' ', NCC_VERSION_FLAGS), $input);
            $input = str_replace(BuildConstants::NccBuildBranch, NCC_VERSION_BRANCH, $input);

            return $input;
        }

        /**
         * Compiles installation constants (Usually used during compiling time)
         *
         * @param string|null $input
         * @param InstallationPaths $installationPaths
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileInstallConstants(?string $input, InstallationPaths $installationPaths): ?string
        {
            if($input == null)
                return null;

            $input = str_replace(InstallConstants::InstallationPath, $installationPaths->getInstallationPath(), $input);
            $input = str_replace(InstallConstants::BinPath, $installationPaths->getBinPath(), $input);
            $input = str_replace(InstallConstants::SourcePath, $installationPaths->getSourcePath(), $input);
            $input = str_replace(InstallConstants::DataPath, $installationPaths->getDataPath(), $input);

            return $input;
        }

        /**
         * Compiles DateTime constants from a Unix Timestamp
         *
         * @param string|null $input
         * @param int $timestamp
         * @return string|null
         * @noinspection PhpUnnecessaryLocalVariableInspection
         */
        public static function compileDateTimeConstants(?string $input, int $timestamp): ?string
        {
            if($input == null)
                return null;

            $input = str_replace(DateTimeConstants::d, date('d', $timestamp), $input);
            $input = str_replace(DateTimeConstants::D, date('D', $timestamp), $input);
            $input = str_replace(DateTimeConstants::j, date('j', $timestamp), $input);
            $input = str_replace(DateTimeConstants::l, date('l', $timestamp), $input);
            $input = str_replace(DateTimeConstants::N, date('N', $timestamp), $input);
            $input = str_replace(DateTimeConstants::S, date('S', $timestamp), $input);
            $input = str_replace(DateTimeConstants::w, date('w', $timestamp), $input);
            $input = str_replace(DateTimeConstants::z, date('z', $timestamp), $input);
            $input = str_replace(DateTimeConstants::W, date('W', $timestamp), $input);
            $input = str_replace(DateTimeConstants::F, date('F', $timestamp), $input);
            $input = str_replace(DateTimeConstants::m, date('m', $timestamp), $input);
            $input = str_replace(DateTimeConstants::M, date('M', $timestamp), $input);
            $input = str_replace(DateTimeConstants::n, date('n', $timestamp), $input);
            $input = str_replace(DateTimeConstants::t, date('t', $timestamp), $input);
            $input = str_replace(DateTimeConstants::L, date('L', $timestamp), $input);
            $input = str_replace(DateTimeConstants::o, date('o', $timestamp), $input);
            $input = str_replace(DateTimeConstants::Y, date('Y', $timestamp), $input);
            $input = str_replace(DateTimeConstants::y, date('y', $timestamp), $input);
            $input = str_replace(DateTimeConstants::a, date('a', $timestamp), $input);
            $input = str_replace(DateTimeConstants::A, date('A', $timestamp), $input);
            $input = str_replace(DateTimeConstants::B, date('B', $timestamp), $input);
            $input = str_replace(DateTimeConstants::g, date('g', $timestamp), $input);
            $input = str_replace(DateTimeConstants::G, date('G', $timestamp), $input);
            $input = str_replace(DateTimeConstants::h, date('h', $timestamp), $input);
            $input = str_replace(DateTimeConstants::H, date('H', $timestamp), $input);
            $input = str_replace(DateTimeConstants::i, date('i', $timestamp), $input);
            $input = str_replace(DateTimeConstants::s, date('s', $timestamp), $input);
            $input = str_replace(DateTimeConstants::c, date('c', $timestamp), $input);
            $input = str_replace(DateTimeConstants::r, date('r', $timestamp), $input);
            $input = str_replace(DateTimeConstants::u, date('u', $timestamp), $input);

            return $input;
        }
    }