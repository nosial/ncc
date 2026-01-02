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

    namespace ncc\Objects\Authentication;

    use ncc\Enums\AuthenticationType;
    use ncc\Objects\Authentication\UsernamePassword;
    use PHPUnit\Framework\TestCase;

    class UsernamePasswordTest extends TestCase
    {
        /**
         * Test creating a UsernamePassword object
         */
        public function testCreateUsernamePassword(): void
        {
            $username = 'testuser';
            $password = 'testpass123';
            
            $auth = new UsernamePassword($username, $password);

            $this->assertEquals($username, $auth->getUsername());
            $this->assertEquals($password, $auth->getPassword());
        }

        /**
         * Test getType returns correct AuthenticationType
         */
        public function testGetType(): void
        {
            $auth = new UsernamePassword('user', 'pass');
            
            $this->assertSame(AuthenticationType::USERNAME_PASSWORD, $auth->getType());
        }

        /**
         * Test toArray method
         */
        public function testToArray(): void
        {
            $username = 'myusername';
            $password = 'mypassword';
            $auth = new UsernamePassword($username, $password);

            $array = $auth->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('type', $array);
            $this->assertArrayHasKey('username', $array);
            $this->assertArrayHasKey('password', $array);
            $this->assertEquals(AuthenticationType::USERNAME_PASSWORD->value, $array['type']);
            $this->assertEquals($username, $array['username']);
            $this->assertEquals($password, $array['password']);
        }

        /**
         * Test fromArray static method
         */
        public function testFromArray(): void
        {
            $username = 'testuser';
            $password = 'testpass';
            $data = [
                'type' => AuthenticationType::USERNAME_PASSWORD->value,
                'username' => $username,
                'password' => $password
            ];

            $auth = UsernamePassword::fromArray($data);

            $this->assertInstanceOf(UsernamePassword::class, $auth);
            $this->assertEquals($username, $auth->getUsername());
            $this->assertEquals($password, $auth->getPassword());
        }

        /**
         * Test serialization and deserialization
         */
        public function testSerializationRoundTrip(): void
        {
            $originalUsername = 'original_user';
            $originalPassword = 'original_pass';
            $original = new UsernamePassword($originalUsername, $originalPassword);

            $array = $original->toArray();
            $restored = UsernamePassword::fromArray($array);

            $this->assertEquals($original->getUsername(), $restored->getUsername());
            $this->assertEquals($original->getPassword(), $restored->getPassword());
            $this->assertSame($original->getType(), $restored->getType());
        }

        /**
         * Test with empty username
         */
        public function testWithEmptyUsername(): void
        {
            $auth = new UsernamePassword('', 'password');
            
            $this->assertEquals('', $auth->getUsername());
            $this->assertEquals('password', $auth->getPassword());
        }

        /**
         * Test with empty password
         */
        public function testWithEmptyPassword(): void
        {
            $auth = new UsernamePassword('username', '');
            
            $this->assertEquals('username', $auth->getUsername());
            $this->assertEquals('', $auth->getPassword());
        }

        /**
         * Test with both empty
         */
        public function testWithBothEmpty(): void
        {
            $auth = new UsernamePassword('', '');
            
            $this->assertEquals('', $auth->getUsername());
            $this->assertEquals('', $auth->getPassword());
        }

        /**
         * Test with special characters
         */
        public function testWithSpecialCharacters(): void
        {
            $username = 'user@example.com';
            $password = 'p@ss!w0rd#123';
            $auth = new UsernamePassword($username, $password);

            $this->assertEquals($username, $auth->getUsername());
            $this->assertEquals($password, $auth->getPassword());
        }

        /**
         * Test with long username and password
         */
        public function testWithLongCredentials(): void
        {
            $longUsername = str_repeat('u', 500);
            $longPassword = str_repeat('p', 500);
            $auth = new UsernamePassword($longUsername, $longPassword);

            $this->assertEquals($longUsername, $auth->getUsername());
            $this->assertEquals($longPassword, $auth->getPassword());
        }

        /**
         * Test with unicode characters
         */
        public function testWithUnicodeCharacters(): void
        {
            $username = 'user_日本語';
            $password = 'пароль_123';
            $auth = new UsernamePassword($username, $password);

            $this->assertEquals($username, $auth->getUsername());
            $this->assertEquals($password, $auth->getPassword());
        }

        /**
         * Test multiple instances are independent
         */
        public function testMultipleInstances(): void
        {
            $auth1 = new UsernamePassword('user1', 'pass1');
            $auth2 = new UsernamePassword('user2', 'pass2');

            $this->assertEquals('user1', $auth1->getUsername());
            $this->assertEquals('pass1', $auth1->getPassword());
            $this->assertEquals('user2', $auth2->getUsername());
            $this->assertEquals('pass2', $auth2->getPassword());
        }
    }
