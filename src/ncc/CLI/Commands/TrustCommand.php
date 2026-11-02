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

    namespace ncc\CLI\Commands;

    use ncc\Abstracts\AbstractCommandHandler;
    use ncc\Classes\Console;
    use ncc\CLI\Commands\Trust\AddTrust;
    use ncc\CLI\Commands\Trust\ListTrust;
    use ncc\CLI\Commands\Trust\RemoveTrust;
    use ncc\CLI\Commands\Trust\RevokeTrust;

    class TrustCommand extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $helpCommand = $argv['help'] ?? $argv['h'] ?? null;
            if($helpCommand !== null)
            {
                foreach(['list', 'ls', 'add', 'remove', 'rm', 'revoke'] as $command)
                {
                    if(isset($argv[$command]))
                    {
                        $helpCommand = $command;
                        break;
                    }
                }

                self::help($helpCommand === true ? null : $helpCommand);
                return 0;
            }

            if(isset($argv['list']) || isset($argv['ls']))
            {
                return ListTrust::handle($argv);
            }
            if(isset($argv['add']))
            {
                return AddTrust::handle($argv);
            }
            if(isset($argv['remove']) || isset($argv['rm']))
            {
                return RemoveTrust::handle($argv);
            }
            if(isset($argv['revoke']))
            {
                return RevokeTrust::handle($argv);
            }

            self::help();
            return 0;
        }

        /**
         * Prints out the help menu for the trust command
         *
         * @param string|null $command The sub command to show help for
         * @return void
         */
        public static function help(?string $command=null): void
        {
            switch($command)
            {
                case 'list':
                case 'ls':
                    Console::out('Usage: ncc trust list' . PHP_EOL);
                    Console::out('Lists the trusted signing keys (built-in, system and user) and revoked keys.');
                    break;

                case 'add':
                    Console::out('Usage: ncc trust add --type=<organization|author> --signer=<identity> --public-key=<key> [--system]' . PHP_EOL);
                    Console::out('Trusts a public key to sign packages on behalf of an organization or author. Packages that');
                    Console::out('claim this signer but are signed by another key are refused afterwards.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --type, -t        (Required) "organization" or "author"');
                    Console::out('  --signer          (Required) The organization or author name');
                    Console::out('  --public-key      (Required) The public key, as printed by "ncc key export --public"');
                    Console::out('  --system          Add the key to the system trust store instead of the user trust store');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc trust add --type=organization --signer="Nosial" --public-key=<base64>');
                    break;

                case 'remove':
                case 'rm':
                    Console::out('Usage: ncc trust remove --key-id=<id|fingerprint> [--type=<type> --signer=<identity>]' . PHP_EOL);
                    Console::out('Removes a trusted key, optionally only for one signer. The next package signed by the key');
                    Console::out('asks for trust again.');
                    break;

                case 'revoke':
                    Console::out('Usage: ncc trust revoke --key-id=<id|fingerprint>' . PHP_EOL);
                    Console::out('       ncc trust revoke --type=<type> --signer=<identity> --public-key=<key>' . PHP_EOL);
                    Console::out('Revokes a key, packages signed by it are refused and it is never offered for trust again.');
                    Console::out('A key that is not in the trust store can be revoked by giving its signer and public key.');
                    break;

                default:
                    Console::out('Usage: ncc trust [command] [options]' . PHP_EOL);
                    Console::out('Manage the keys trusted to sign ncc packages. Keys are normally trusted while installing a');
                    Console::out('package (the trust prompt or "ncc install --trust"), these commands manage them directly.' . PHP_EOL);
                    Console::out('Commands:');
                    Console::out('  list, ls          List the trusted keys');
                    Console::out('  add               Trust a public key for a signer');
                    Console::out('  remove, rm        Remove a trusted key');
                    Console::out('  revoke            Revoke a key');
                    Console::out(PHP_EOL . 'Use "ncc trust [command] --help" for more information about a command.');
                    break;
            }
        }
    }
