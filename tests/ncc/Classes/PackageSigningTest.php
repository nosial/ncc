<?php

    /*
     * Copyright (c) Nosial 2022-2026, all rights reserved.
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

    namespace ncc\Classes;

    use FilesystemIterator;
    use ncc\Enums\PackageStructure;
    use ncc\Enums\SignatureStatus;
    use ncc\Enums\SignerType;
    use ncc\Exceptions\OperationException;
    use ncc\Objects\Project;
    use ncc\Objects\Signing\SigningKey;
    use ncc\Objects\Signing\TrustedKey;
    use PHPUnit\Framework\TestCase;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;

    class PackageSigningTest extends TestCase
    {
        private const PACKAGE = 'com.example.package_signing_test';
        private const ORGANIZATION = 'Example Org';
        private const AUTHOR = 'Jane Doe';

        private static string $workDirectory;
        private static string $builtPackage;
        private static SigningKey $organizationKey;
        private static SigningKey $authorKey;
        private string $packagePath;
        private TrustStore $trustStore;

        /**
         * Builds a small package and generates the signing keys once for all tests
         */
        public static function setUpBeforeClass(): void
        {
            if(!SignatureVerifier::isSupported())
            {
                self::markTestSkipped('The sodium extension is required to sign packages');
            }

            self::$workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_package_signing_test_' . uniqid();
            $sourcePath = self::$workDirectory . DIRECTORY_SEPARATOR . 'src';
            mkdir($sourcePath, 0777, true);
            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'PackageSigningTestClass.php', '<?php class PackageSigningTestClass { public const VALUE = "original"; }');
            file_put_contents($sourcePath . DIRECTORY_SEPARATOR . 'data.txt', 'resource data');

            $project = Project::createNew('PackageSigningTest', self::PACKAGE);
            $project->getAssembly()->setOrganization(self::ORGANIZATION);
            $project->getAssembly()->setAuthor(self::AUTHOR);
            $projectFile = self::$workDirectory . DIRECTORY_SEPARATOR . 'project.yml';
            $project->save($projectFile);
            self::$builtPackage = Project::compilerFromFile($projectFile, 'release')->build();

            self::$organizationKey = SigningKey::generate(SignerType::ORGANIZATION, self::ORGANIZATION);
            self::$authorKey = SigningKey::generate(SignerType::AUTHOR, self::AUTHOR);
        }

        public static function tearDownAfterClass(): void
        {
            if(isset(self::$workDirectory))
            {
                self::recursiveDelete(self::$workDirectory);
            }
        }

        protected function setUp(): void
        {
            $testDirectory = self::$workDirectory . DIRECTORY_SEPARATOR . 'test_' . uniqid();
            mkdir($testDirectory, 0777, true);
            $this->packagePath = $testDirectory . DIRECTORY_SEPARATOR . 'package.ncc';
            copy(self::$builtPackage, $this->packagePath);
            $this->trustStore = new TrustStore($testDirectory);
        }

        private static function recursiveDelete(string $directory): void
        {
            if(!is_dir($directory))
            {
                return;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach($iterator as $file)
            {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($directory);
        }

        private function trust(SigningKey $key): void
        {
            $this->trustStore->add(new TrustedKey($key->getSignerType(), $key->getSigner(), $key->getPublicKey()));
        }

        private function verify(): SignatureStatus
        {
            return SignatureVerifier::verify(new PackageReader($this->packagePath), [$this->trustStore])->getStatus();
        }

        public function testUnsignedPackageHasNoTrailer(): void
        {
            $reader = new PackageReader($this->packagePath);

            $this->assertSame([], $reader->getSignatures());
            $this->assertSame($reader->getEndOffset(), $reader->getBodyEndOffset());
            $this->assertSame(filesize($this->packagePath), $reader->getEndOffset());
            $this->assertSame(SignatureStatus::UNSIGNED, $this->verify());
        }

        public function testSignedPackageIsUntrustedUntilKeyIsTrusted(): void
        {
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            $this->assertSame(SignatureStatus::UNTRUSTED, $this->verify());

            $this->trust(self::$organizationKey);
            $this->assertSame(SignatureStatus::VERIFIED, $this->verify());
        }

        public function testSigningDoesNotModifyThePackageBody(): void
        {
            $before = new PackageReader($this->packagePath);
            $bodyBefore = $before->computeBodyDigest();
            $referencesBefore = array_keys($before->getAllReferences());
            unset($before);

            PackageSigner::sign($this->packagePath, self::$organizationKey);
            $after = new PackageReader($this->packagePath);

            $this->assertSame($bodyBefore, $after->computeBodyDigest());
            $this->assertSame($referencesBefore, array_keys($after->getAllReferences()));
            $this->assertSame(filesize(self::$builtPackage), $after->getBodyEndOffset());
            $this->assertSame(filesize($this->packagePath), $after->getEndOffset());
            $this->assertCount(1, $after->getSignatures());
        }

        public function testModifiedPackageIsInvalid(): void
        {
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            $this->trust(self::$organizationKey);

            // Flip a byte inside the data of a component
            $reader = new PackageReader($this->packagePath);
            $references = $reader->getComponentReferences();
            $reference = reset($references);
            unset($reader);

            $handle = fopen($this->packagePath, 'r+b');
            fseek($handle, $reference->getOffset());
            $byte = fread($handle, 1);
            fseek($handle, $reference->getOffset());
            fwrite($handle, chr(ord($byte) ^ 0xFF));
            fclose($handle);

            $this->assertSame(SignatureStatus::INVALID, $this->verify());
        }

        public function testCorruptedTrailerIsInvalid(): void
        {
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            $handle = fopen($this->packagePath, 'r+b');
            ftruncate($handle, filesize($this->packagePath) - 10);
            fclose($handle);

            $reader = new PackageReader($this->packagePath);
            $this->assertNotNull($reader->getSignatureError());
            $this->assertSame(SignatureStatus::INVALID, SignatureVerifier::verify($reader, [$this->trustStore])->getStatus());
        }

        public function testLoneSignatureMarkerIsIgnored(): void
        {
            // Older versions of ncc leave the first byte of the trailer behind when exporting a signed package
            file_put_contents($this->packagePath, PackageStructure::SIGNATURES->value, FILE_APPEND);
            $reader = new PackageReader($this->packagePath);

            $this->assertNull($reader->getSignatureError());
            $this->assertSame(filesize(self::$builtPackage), $reader->getEndOffset());
            $this->assertSame(SignatureStatus::UNSIGNED, $this->verify());
        }

        public function testKeyMustMatchAssembly(): void
        {
            $this->expectException(OperationException::class);
            PackageSigner::sign($this->packagePath, SigningKey::generate(SignerType::ORGANIZATION, 'Someone Else'));
        }

        public function testAuthorAndOrganizationSignatures(): void
        {
            PackageSigner::sign($this->packagePath, self::$authorKey);
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            $this->trust(self::$authorKey);
            $this->trust(self::$organizationKey);

            $result = SignatureVerifier::verify(new PackageReader($this->packagePath), [$this->trustStore]);
            $this->assertSame(SignatureStatus::VERIFIED, $result->getStatus());
            $this->assertCount(2, $result->getEntriesWithStatus(SignatureStatus::VERIFIED));
        }

        public function testSigningAgainWithTheSameKeyReplacesTheSignature(): void
        {
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            PackageSigner::sign($this->packagePath, self::$organizationKey);

            $this->assertCount(1, (new PackageReader($this->packagePath))->getSignatures());
        }

        public function testDifferentKeyForTrustedSignerIsKeyMismatch(): void
        {
            $this->trust(SigningKey::generate(SignerType::ORGANIZATION, self::ORGANIZATION));
            PackageSigner::sign($this->packagePath, self::$organizationKey);

            $this->assertSame(SignatureStatus::KEY_MISMATCH, $this->verify());
        }

        public function testRevokedKeyIsInvalid(): void
        {
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            $this->trust(self::$organizationKey);
            $this->trustStore->revoke(self::$organizationKey->getKeyId());

            $this->assertSame(SignatureStatus::INVALID, $this->verify());
        }

        public function testTrustStorePersistsKeys(): void
        {
            $this->trust(self::$organizationKey);
            $this->assertTrue($this->trustStore->save());

            $reloaded = new TrustStore($this->trustStore->getDataDirectoryPath());
            $this->assertCount(1, $reloaded->getEntries());
            $this->assertSame(self::$organizationKey->getKeyId(), $reloaded->getEntries()[0]->getKeyId());
            $this->assertTrue($reloaded->getEntries()[0]->isFor(SignerType::ORGANIZATION, strtoupper(self::ORGANIZATION)));
        }

        public function testExportKeepsSignatures(): void
        {
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            $exportPath = dirname($this->packagePath) . DIRECTORY_SEPARATOR . 'exported.ncc';
            (new PackageReader($this->packagePath))->exportPackage($exportPath);

            $this->assertSame(file_get_contents($this->packagePath), file_get_contents($exportPath));
            $this->assertCount(1, (new PackageReader($exportPath))->getSignatures());
        }

        public function testCacheKeepsSignatures(): void
        {
            PackageSigner::sign($this->packagePath, self::$organizationKey);
            (new PackageReader($this->packagePath))->exportCache($this->packagePath . '.cache');

            $cached = new PackageReader($this->packagePath, true);
            $this->assertCount(1, $cached->getSignatures());
            $this->assertSame(SignatureStatus::UNTRUSTED, SignatureVerifier::verify($cached, [$this->trustStore])->getStatus());
        }

        public function testSigningKeyExportRoundTrip(): void
        {
            $exported = self::$authorKey->export('correct horse');
            $this->assertTrue(SigningKey::isExportedKey($exported));
            $this->assertSame(self::$authorKey->getPublicKey(), SigningKey::inspect($exported)['public_key']);

            $imported = SigningKey::import($exported, 'correct horse');
            $this->assertSame(self::$authorKey->getPublicKey(), $imported->getPublicKey());
            $this->assertSame(SignerType::AUTHOR, $imported->getSignerType());
            $this->assertSame(self::AUTHOR, $imported->getSigner());
        }

        public function testSigningKeyWrongPassphrase(): void
        {
            $this->expectException(OperationException::class);
            SigningKey::import(self::$authorKey->export('correct horse'), 'wrong passphrase');
        }
    }
