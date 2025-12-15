<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    use ncc\Exceptions\OperationException;
    use ncc\Objects\Authentication\AccessToken;
    use ncc\Objects\Authentication\UsernamePassword;
    use PHPUnit\Framework\TestCase;

    class AuthenticationManagerTest extends TestCase
    {
        private string $testDataDir;
        private AuthenticationManager $manager;

        /**
         * Set up test environment before each test
         */
        protected function setUp(): void
        {
            parent::setUp();
            
            // Create a temporary directory for testing
            $this->testDataDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ncc_auth_test_' . uniqid();
            
            if(!file_exists($this->testDataDir))
            {
                mkdir($this->testDataDir, 0777, true);
            }
            
            $this->manager = new AuthenticationManager($this->testDataDir);
        }

        /**
         * Clean up test environment after each test
         */
        protected function tearDown(): void
        {
            parent::tearDown();
            
            // Remove test directory and its contents
            if(file_exists($this->testDataDir))
            {
                $vaultPath = $this->testDataDir . DIRECTORY_SEPARATOR . '.vault';
                if(file_exists($vaultPath))
                {
                    unlink($vaultPath);
                }
                rmdir($this->testDataDir);
            }
        }

        /**
         * Test that the manager is properly initialized with the correct data directory
         */
        public function testConstructorAndGetDataDirectoryPath(): void
        {
            $this->assertEquals($this->testDataDir, $this->manager->getDataDirectoryPath());
        }

        /**
         * Test that a new manager starts in a locked state
         */
        public function testManagerStartsLocked(): void
        {
            $this->assertFalse($this->manager->isUnlocked());
        }

        /**
         * Test that vault does not exist initially
         */
        public function testVaultDoesNotExistInitially(): void
        {
            $this->assertFalse($this->manager->vaultExists());
        }

        /**
         * Test unlocking creates a new vault when one doesn't exist
         */
        public function testUnlockCreatesNewVault(): void
        {
            $this->manager->unlock('test-password');
            
            $this->assertTrue($this->manager->isUnlocked());
            $this->assertTrue($this->manager->vaultExists());
        }

        /**
         * Test that adding an entry requires an unlocked vault
         */
        public function testAddEntryRequiresUnlockedVault(): void
        {
            $this->expectException(OperationException::class);
            $this->expectExceptionMessage('Cannot add entry: Vault is locked');
            
            $entry = new UsernamePassword('testuser', 'testpass');
            $this->manager->addEntry('test', $entry);
        }

        /**
         * Test that getting an entry requires an unlocked vault
         */
        public function testGetEntryRequiresUnlockedVault(): void
        {
            $this->expectException(OperationException::class);
            $this->expectExceptionMessage('Cannot get entry: Vault is locked');
            
            $this->manager->getEntry('test');
        }

        /**
         * Test that removing an entry requires an unlocked vault
         */
        public function testRemoveEntryRequiresUnlockedVault(): void
        {
            $this->expectException(OperationException::class);
            $this->expectExceptionMessage('Cannot remove entry: Vault is locked');
            
            $this->manager->removeEntry('test');
        }

        /**
         * Test that getting all entries requires an unlocked vault
         */
        public function testGetAllEntriesRequiresUnlockedVault(): void
        {
            $this->expectException(OperationException::class);
            $this->expectExceptionMessage('Cannot get entries: Vault is locked');
            
            $this->manager->getAllEntries();
        }

        /**
         * Test that saving requires an unlocked vault
         */
        public function testSaveRequiresUnlockedVault(): void
        {
            $this->expectException(OperationException::class);
            $this->expectExceptionMessage('Cannot save vault: Vault is locked');
            
            $this->manager->save();
        }

        /**
         * Test adding and retrieving an entry
         */
        public function testAddAndGetEntry(): void
        {
            $this->manager->unlock('test-password');
            
            $entry = new UsernamePassword('testuser', 'testpass');
            $this->manager->addEntry('test-entry', $entry);
            
            $retrieved = $this->manager->getEntry('test-entry');
            
            $this->assertNotNull($retrieved);
            $this->assertInstanceOf(UsernamePassword::class, $retrieved);
            $this->assertEquals('testuser', $retrieved->getUsername());
            $this->assertEquals('testpass', $retrieved->getPassword());
        }

        /**
         * Test getting a non-existent entry returns null
         */
        public function testGetNonExistentEntryReturnsNull(): void
        {
            $this->manager->unlock('test-password');
            
            $retrieved = $this->manager->getEntry('non-existent');
            
            $this->assertNull($retrieved);
        }

        /**
         * Test adding multiple entries with different authentication types
         */
        public function testAddMultipleEntries(): void
        {
            $this->manager->unlock('test-password');
            
            $entry1 = new UsernamePassword('user1', 'pass1');
            $entry2 = new AccessToken('token123456');
            $entry3 = new UsernamePassword('user3', 'pass3');
            
            $this->manager->addEntry('entry1', $entry1);
            $this->manager->addEntry('entry2', $entry2);
            $this->manager->addEntry('entry3', $entry3);
            
            $allEntries = $this->manager->getAllEntries();
            
            $this->assertCount(3, $allEntries);
            $this->assertArrayHasKey('entry1', $allEntries);
            $this->assertArrayHasKey('entry2', $allEntries);
            $this->assertArrayHasKey('entry3', $allEntries);
        }

        /**
         * Test removing an entry
         */
        public function testRemoveEntry(): void
        {
            $this->manager->unlock('test-password');
            
            $entry = new UsernamePassword('testuser', 'testpass');
            $this->manager->addEntry('test-entry', $entry);
            
            $this->assertNotNull($this->manager->getEntry('test-entry'));
            
            $this->manager->removeEntry('test-entry');
            
            $this->assertNull($this->manager->getEntry('test-entry'));
        }

        /**
         * Test that entries persist after saving and reloading with both authentication types
         */
        public function testEntriesPersistAfterSaveAndReload(): void
        {
            // Create and save entries
            $this->manager->unlock('test-password');
            
            $entry1 = new UsernamePassword('user1', 'pass1');
            $entry2 = new AccessToken('token-xyz-789');
            
            $this->manager->addEntry('entry1', $entry1);
            $this->manager->addEntry('entry2', $entry2);
            $this->manager->save();
            
            // Create a new manager instance and unlock with the same password
            $newManager = new AuthenticationManager($this->testDataDir);
            $this->assertTrue($newManager->vaultExists());
            
            $newManager->unlock('test-password');
            
            // Verify entries are loaded
            $allEntries = $newManager->getAllEntries();
            $this->assertCount(2, $allEntries);
            $this->assertArrayHasKey('entry1', $allEntries);
            $this->assertArrayHasKey('entry2', $allEntries);
        }

        /**
         * Test that unlocking with wrong password throws an exception
         */
        public function testUnlockWithWrongPasswordThrowsException(): void
        {
            // Create vault with one password
            $this->manager->unlock('correct-password');
            $this->manager->save();
            
            // Try to unlock with wrong password
            $newManager = new AuthenticationManager($this->testDataDir);
            
            $this->expectException(OperationException::class);
            $this->expectExceptionMessage('Failed to unlock vault');
            
            $newManager->unlock('wrong-password');
        }

        /**
         * Test updating an existing entry by adding with the same name
         */
        public function testUpdateEntryByOverwriting(): void
        {
            $this->manager->unlock('test-password');
            
            $entry1 = new UsernamePassword('user1', 'pass1');
            $this->manager->addEntry('test-entry', $entry1);
            
            $retrieved1 = $this->manager->getEntry('test-entry');
            $this->assertEquals('user1', $retrieved1->getUsername());
            
            // Overwrite with new entry
            $entry2 = new UsernamePassword('user2', 'pass2');
            $this->manager->addEntry('test-entry', $entry2);
            
            $retrieved2 = $this->manager->getEntry('test-entry');
            $this->assertEquals('user2', $retrieved2->getUsername());
            $this->assertEquals('pass2', $retrieved2->getPassword());
        }

        /**
         * Test that getAllEntries returns an empty array for a new vault
         */
        public function testGetAllEntriesReturnsEmptyArrayForNewVault(): void
        {
            $this->manager->unlock('test-password');
            
            $entries = $this->manager->getAllEntries();
            
            $this->assertIsArray($entries);
            $this->assertEmpty($entries);
        }

        /**
         * Test that vault path is correctly computed
         */
        public function testVaultPathIsCorrect(): void
        {
            $this->manager->unlock('test-password');
            
            $expectedPath = $this->testDataDir . DIRECTORY_SEPARATOR . '.vault';
            $this->assertTrue(file_exists($expectedPath));
        }

        /**
         * Test removing a non-existent entry doesn't cause errors
         */
        public function testRemoveNonExistentEntryDoesNotThrowError(): void
        {
            $this->manager->unlock('test-password');
            
            // This should not throw an exception
            $this->manager->removeEntry('non-existent');
            
            // Verify vault is still functional
            $this->assertTrue($this->manager->isUnlocked());
        }

        /**
         * Test adding and retrieving AccessToken authentication
         */
        public function testAddAndGetAccessTokenEntry(): void
        {
            $this->manager->unlock('test-password');
            
            $entry = new AccessToken('my-secret-access-token-12345');
            $this->manager->addEntry('github-token', $entry);
            
            $retrieved = $this->manager->getEntry('github-token');
            
            $this->assertNotNull($retrieved);
            $this->assertInstanceOf(AccessToken::class, $retrieved);
        }

        /**
         * Test mixing both authentication types in the vault
         */
        public function testMixedAuthenticationTypes(): void
        {
            $this->manager->unlock('test-password');
            
            $userPassEntry = new UsernamePassword('admin', 'secret123');
            $tokenEntry1 = new AccessToken('github-token-abc');
            $tokenEntry2 = new AccessToken('gitlab-token-xyz');
            $userPassEntry2 = new UsernamePassword('developer', 'devpass456');
            
            $this->manager->addEntry('admin-credentials', $userPassEntry);
            $this->manager->addEntry('github-api', $tokenEntry1);
            $this->manager->addEntry('gitlab-api', $tokenEntry2);
            $this->manager->addEntry('dev-credentials', $userPassEntry2);
            
            $allEntries = $this->manager->getAllEntries();
            
            $this->assertCount(4, $allEntries);
            $this->assertInstanceOf(UsernamePassword::class, $allEntries['admin-credentials']);
            $this->assertInstanceOf(AccessToken::class, $allEntries['github-api']);
            $this->assertInstanceOf(AccessToken::class, $allEntries['gitlab-api']);
            $this->assertInstanceOf(UsernamePassword::class, $allEntries['dev-credentials']);
        }

        /**
         * Test that different authentication types persist correctly after save and reload
         */
        public function testMixedAuthenticationTypesPersistence(): void
        {
            $this->manager->unlock('test-password');
            
            $userPass = new UsernamePassword('testuser', 'testpass');
            $token = new AccessToken('api-token-secret');
            
            $this->manager->addEntry('user-auth', $userPass);
            $this->manager->addEntry('token-auth', $token);
            $this->manager->save();
            
            // Reload vault
            $newManager = new AuthenticationManager($this->testDataDir);
            $newManager->unlock('test-password');
            
            $retrievedUserPass = $newManager->getEntry('user-auth');
            $retrievedToken = $newManager->getEntry('token-auth');
            
            $this->assertInstanceOf(UsernamePassword::class, $retrievedUserPass);
            $this->assertInstanceOf(AccessToken::class, $retrievedToken);
            
            $this->assertEquals('testuser', $retrievedUserPass->getUsername());
            $this->assertEquals('testpass', $retrievedUserPass->getPassword());
        }

        /**
         * Test that multiple saves work correctly
         */
        public function testMultipleSaves(): void
        {
            $this->manager->unlock('test-password');
            
            $entry1 = new UsernamePassword('user1', 'pass1');
            $this->manager->addEntry('entry1', $entry1);
            $this->manager->save();
            
            $entry2 = new UsernamePassword('user2', 'pass2');
            $this->manager->addEntry('entry2', $entry2);
            $this->manager->save();
            
            // Reload and verify both entries exist
            $newManager = new AuthenticationManager($this->testDataDir);
            $newManager->unlock('test-password');
            
            $allEntries = $newManager->getAllEntries();
            $this->assertCount(2, $allEntries);
        }

        /**
         * Test that save persists removed entries
         */
        public function testSavePersistsRemovedEntries(): void
        {
            $this->manager->unlock('test-password');
            
            $entry1 = new UsernamePassword('user1', 'pass1');
            $entry2 = new UsernamePassword('user2', 'pass2');
            
            $this->manager->addEntry('entry1', $entry1);
            $this->manager->addEntry('entry2', $entry2);
            $this->manager->save();
            
            // Remove one entry and save
            $this->manager->removeEntry('entry1');
            $this->manager->save();
            
            // Reload and verify only one entry exists
            $newManager = new AuthenticationManager($this->testDataDir);
            $newManager->unlock('test-password');
            
            $allEntries = $newManager->getAllEntries();
            $this->assertCount(1, $allEntries);
            $this->assertArrayHasKey('entry2', $allEntries);
            $this->assertArrayNotHasKey('entry1', $allEntries);
        }
    }
