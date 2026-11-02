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
    use ncc\Classes\KeyManager;
    use ncc\CLI\Commands\Key\DeleteKey;
    use ncc\CLI\Commands\Key\ExportKey;
    use ncc\CLI\Commands\Key\GenerateKey;
    use ncc\CLI\Commands\Key\ImportKey;
    use ncc\CLI\Commands\Key\ListKeys;

    class KeyCommand extends AbstractCommandHandler
    {
        /**
         * @inheritDoc
         */
        public static function handle(array $argv): int
        {
            $helpCommand = $argv['help'] ?? $argv['h'] ?? null;
            if($helpCommand !== null)
            {
                foreach(['generate', 'list', 'ls', 'export', 'import', 'delete', 'del'] as $command)
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

            if(isset($argv['generate']))
            {
                return GenerateKey::handle($argv);
            }
            if(isset($argv['list']) || isset($argv['ls']))
            {
                return ListKeys::handle($argv);
            }
            if(isset($argv['export']))
            {
                return ExportKey::handle($argv);
            }
            if(isset($argv['import']))
            {
                return ImportKey::handle($argv);
            }
            if(isset($argv['delete']) || isset($argv['del']))
            {
                return DeleteKey::handle($argv);
            }

            self::help();
            return 0;
        }

        /**
         * Prints out the help menu for the key command
         *
         * @param string|null $command The sub command to show help for
         * @return void
         */
        public static function help(?string $command=null): void
        {
            switch($command)
            {
                case 'generate':
                    Console::out('Usage: ncc key generate --name=<name> --type=<organization|author> --signer=<identity> [--overwrite]' . PHP_EOL);
                    Console::out('Generates a new signing key, encrypted with a passphrase you are prompted for.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name, -n        (Required) The name to store the key as');
                    Console::out('  --type, -t        (Required) Sign as the "organization" or the "author" of packages');
                    Console::out('  --signer          (Required) The organization or author name, it must match the assembly of');
                    Console::out('                    the packages signed with the key exactly');
                    Console::out('  --overwrite       Replace an existing key with the same name');
                    Console::out(PHP_EOL . 'Example:');
                    Console::out('  ncc key generate --name=nosial --type=organization --signer="Nosial"');
                    break;

                case 'list':
                case 'ls':
                    Console::out('Usage: ncc key list' . PHP_EOL);
                    Console::out('Lists the stored signing keys with their signer and fingerprint.');
                    break;

                case 'export':
                    Console::out('Usage: ncc key export --name=<name> [--public] [--output=<file>]' . PHP_EOL);
                    Console::out('Exports a signing key. Without --public the encrypted private key is exported, it can be');
                    Console::out('imported with "ncc key import" or used as the NCC_SIGNING_KEY secret of a CI pipeline (with');
                    Console::out('its passphrase as NCC_SIGNING_KEY_PASSPHRASE). With --public the public key is printed, publish');
                    Console::out('it (or its fingerprint) so others can verify your packages with "ncc trust add".' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name, -n        (Required) The name of the key');
                    Console::out('  --public          Export the public key instead of the encrypted private key');
                    Console::out('  --output, -o      Write the key to a file instead of printing it');
                    break;

                case 'import':
                    Console::out('Usage: ncc key import --name=<name> (--input=<file> | --value=<key>) [--overwrite]' . PHP_EOL);
                    Console::out('Imports an encrypted signing key exported with "ncc key export", the passphrase is verified');
                    Console::out('before the key is stored.' . PHP_EOL);
                    Console::out('Options:');
                    Console::out('  --name, -n        (Required) The name to store the key as');
                    Console::out('  --input, -i       The file containing the exported key');
                    Console::out('  --value           The exported key itself');
                    Console::out('  --overwrite       Replace an existing key with the same name');
                    break;

                case 'delete':
                case 'del':
                    Console::out('Usage: ncc key delete --name=<name> [--yes]' . PHP_EOL);
                    Console::out('Deletes a stored signing key, this cannot be undone unless the key was exported.');
                    break;

                default:
                    Console::out('Usage: ncc key [command] [options]' . PHP_EOL);
                    Console::out('Manage the signing keys used to sign ncc packages.' . PHP_EOL);
                    Console::out('Commands:');
                    Console::out('  generate          Generate a new signing key');
                    Console::out('  list, ls          List the stored signing keys');
                    Console::out('  export            Export a signing key (encrypted) or its public key');
                    Console::out('  import            Import an exported signing key');
                    Console::out('  delete, del       Delete a stored signing key');
                    Console::out(PHP_EOL . 'Keys are stored encrypted in ' . KeyManager::getKeysDirectory());
                    Console::out('Use "ncc key [command] --help" for more information about a command.');
                    break;
            }
        }
    }
