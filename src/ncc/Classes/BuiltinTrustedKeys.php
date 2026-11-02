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

    use ncc\Enums\SignerType;
    use ncc\Objects\Signing\TrustedKey;

    class BuiltinTrustedKeys
    {
        /**
         * @var array<int, array{0: SignerType, 1: string, 2: string}> [signer type, signer, base64 public key]
         */
        private const array KEYS = [
            [SignerType::ORGANIZATION, 'Nosial', 'OXDLC6gGMpdL6Ryj5lO2DQlt8m1beQm8lM0kqmEUVoM='],
        ];

        /**
         * Returns the built-in trusted keys
         *
         * @return TrustedKey[]
         */
        public static function getKeys(): array
        {
            $keys = [];
            foreach(self::KEYS as [$signerType, $signer, $publicKey])
            {
                $decoded = TrustedKey::decodePublicKey($publicKey);
                if($decoded !== null)
                {
                    $keys[] = new TrustedKey($signerType, $signer, $decoded, TrustedKey::SOURCE_BUILTIN, 0);
                }
            }

            return $keys;
        }
    }
