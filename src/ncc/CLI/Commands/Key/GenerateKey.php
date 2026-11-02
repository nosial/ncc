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

    namespace ncc\CLI\Commands\Key;

    use Exception;
    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\Classes\KeyManager;
    use ncc\Enums\SignerType;
    use ncc\Objects\Signing\SigningKey;

    class GenerateKey extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $name = $argv['name'] ?? $argv['n'] ?? null;
            $type = $argv['type'] ?? $argv['t'] ?? null;
            $signer = $argv['signer'] ?? null;

            if(!is_string($name) || $name === '')
            {
                Console::error('A key name is required, use --name to specify it');
                return 1;
            }

            $signerType = is_string($type) ? SignerType::tryFrom(strtolower($type)) : null;
            if($signerType === null)
            {
                Console::error('A signer type is required, use --type=organization or --type=author');
                return 1;
            }

            if(!is_string($signer) || trim($signer) === '')
            {
                Console::error(sprintf('The %s name is required, use --signer to specify it (it must match the package assembly)', $signerType->value));
                return 1;
            }

            try
            {
                KeyManager::validateName($name);
                if(!isset($argv['overwrite']) && KeyManager::exists($name))
                {
                    Console::error(sprintf('The signing key "%s" already exists, use --overwrite to replace it', $name));
                    return 1;
                }

                $signingKey = SigningKey::generate($signerType, $signer);
                $passphrase = KeyManager::promptNewPassphrase();
                KeyManager::save($name, $signingKey->export($passphrase), isset($argv['overwrite']));
            }
            catch(Exception $e)
            {
                Console::error('Failed to generate the signing key: ' . $e->getMessage());
                return 1;
            }

            Console::out(sprintf('Generated the signing key "%s"', $name));
            Console::out(sprintf('  Signer:      %s', $signingKey->describeSigner()));
            Console::out(sprintf('  Fingerprint: %s', $signingKey->getFingerprint()));
            Console::out(sprintf('  Public key:  %s', base64_encode($signingKey->getPublicKey())));
            Console::out(PHP_EOL . 'Publish the public key or fingerprint so others can trust your packages, eg;');
            Console::out(sprintf('  ncc trust add --type=%s --signer="%s" --public-key=%s', $signerType->value, $signer, base64_encode($signingKey->getPublicKey())));
            Console::out(PHP_EOL . 'Back up the key with "ncc key export", a lost key cannot be recovered.');
            return 0;
        }
    }
