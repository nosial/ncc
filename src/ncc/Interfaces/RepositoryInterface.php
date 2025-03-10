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

    namespace ncc\Interfaces;

    use ncc\Enums\Types\AuthenticationType;
    use ncc\Enums\Versions;
    use ncc\Exceptions\AuthenticationException;
    use ncc\Exceptions\NetworkException;
    use ncc\Objects\RepositoryConfiguration;
    use ncc\Objects\RepositoryResult;

    interface RepositoryInterface
    {
        /**
         * Returns the archive URL for the source code of the specified group and project.
         * This is useful for building the project from source.
         *
         * @param RepositoryConfiguration $repository The remote repository to make the request to
         * @param string $vendor The vendor to get the source for (eg; "Nosial")
         * @param string $project The project to get the source for (eg; "ncc" or "libs/config")
         * @param string $version Optional. The version to get the source for. By default, it will get the latest version
         * @param AuthenticationType|null $authentication Optional. The authentication to use. If null, No authentication will be used.
         * @param array $options Optional. The options to use for the request
         * @return RepositoryResult The result of the request
         * @throws AuthenticationException If the authentication is invalid
         * @throws NetworkException If there was an error getting the source
         */
        public static function fetchSourceArchive(RepositoryConfiguration $repository, string $vendor, string $project, string $version=Versions::LATEST->value, ?AuthenticationType $authentication=null, array $options=[]): RepositoryResult;

        /**
         * Returns the archive URL for the ncc package of the specified group and project.
         * This is useful for downloading the package.
         *
         * @param RepositoryConfiguration $repository The remote repository to make the request to
         * @param string $vendor The vendor to get the package for (eg; "Nosial")
         * @param string $project The project to get the package for (eg; "ncc" or "libs/config")
         * @param string $version Optional. The version to get the package for. By default, it will get the latest version
         * @param AuthenticationType|null $authentication Optional. The authentication to use. If null, No authentication will be used.
         * @param array $options Optional. The options to use for the request
         * @return RepositoryResult The result of the request
         * @throws AuthenticationException If the authentication is invalid
         * @throws NetworkException If there was an error getting the package
         */
        public static function fetchPackage(RepositoryConfiguration $repository, string $vendor, string $project, string $version=Versions::LATEST->value, ?AuthenticationType $authentication=null, array $options=[]): RepositoryResult;
    }