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
    use ncc\Objects\Authentication\AccessToken;
    use PHPUnit\Framework\TestCase;

    class AccessTokenTest extends TestCase
    {
        /**
         * Test creating an AccessToken object
         */
        public function testCreateAccessToken(): void
        {
            $token = 'ghp_1234567890abcdefghijklmnopqrstuvwxyz';
            $accessToken = new AccessToken($token);

            $this->assertEquals($token, $accessToken->getAccessToken());
        }

        /**
         * Test getType returns correct AuthenticationType
         */
        public function testGetType(): void
        {
            $accessToken = new AccessToken('test_token');
            
            $this->assertSame(AuthenticationType::ACCESS_TOKEN, $accessToken->getType());
        }

        /**
         * Test toArray method
         */
        public function testToArray(): void
        {
            $token = 'my_secret_token';
            $accessToken = new AccessToken($token);

            $array = $accessToken->toArray();

            $this->assertIsArray($array);
            $this->assertArrayHasKey('type', $array);
            $this->assertArrayHasKey('accessToken', $array);
            $this->assertEquals(AuthenticationType::ACCESS_TOKEN->value, $array['type']);
            $this->assertEquals($token, $array['accessToken']);
        }

        /**
         * Test fromArray static method
         */
        public function testFromArray(): void
        {
            $token = 'test_access_token';
            $data = [
                'type' => AuthenticationType::ACCESS_TOKEN->value,
                'accessToken' => $token
            ];

            $accessToken = AccessToken::fromArray($data);

            $this->assertInstanceOf(AccessToken::class, $accessToken);
            $this->assertEquals($token, $accessToken->getAccessToken());
        }

        /**
         * Test serialization and deserialization
         */
        public function testSerializationRoundTrip(): void
        {
            $originalToken = 'original_token_12345';
            $original = new AccessToken($originalToken);

            $array = $original->toArray();
            $restored = AccessToken::fromArray($array);

            $this->assertEquals($original->getAccessToken(), $restored->getAccessToken());
            $this->assertSame($original->getType(), $restored->getType());
        }

        /**
         * Test with empty token
         */
        public function testWithEmptyToken(): void
        {
            $accessToken = new AccessToken('');
            
            $this->assertEquals('', $accessToken->getAccessToken());
        }

        /**
         * Test with long token
         */
        public function testWithLongToken(): void
        {
            $longToken = str_repeat('a', 1000);
            $accessToken = new AccessToken($longToken);

            $this->assertEquals($longToken, $accessToken->getAccessToken());
            $this->assertEquals(1000, strlen($accessToken->getAccessToken()));
        }

        /**
         * Test with special characters in token
         */
        public function testWithSpecialCharacters(): void
        {
            $token = 'token_with-special.chars@123!';
            $accessToken = new AccessToken($token);

            $this->assertEquals($token, $accessToken->getAccessToken());
        }

        /**
         * Test multiple instances are independent
         */
        public function testMultipleInstances(): void
        {
            $token1 = 'token_one';
            $token2 = 'token_two';
            
            $accessToken1 = new AccessToken($token1);
            $accessToken2 = new AccessToken($token2);

            $this->assertEquals($token1, $accessToken1->getAccessToken());
            $this->assertEquals($token2, $accessToken2->getAccessToken());
            $this->assertNotEquals($accessToken1->getAccessToken(), $accessToken2->getAccessToken());
        }
    }
