<?php

    require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php');

    print('Generating new random key' . PHP_EOL);
    $key = \ncc\Defuse\Crypto\Key::createNewRandomKey();
    $ascii_key = $key->saveToAsciiSafeString();

    print('Key: ' . $ascii_key . PHP_EOL);


    print('Encrypting message \'Hello NCC!\' as raw binary' . PHP_EOL);
    $encrypted_message = \ncc\Defuse\Crypto\Crypto::encrypt('Hello NCC!', $key, true);
    print('Encrypted Message: ' . $encrypted_message . PHP_EOL);


    print('Decrypting message' . PHP_EOL);
    $decrypted_message = \ncc\Defuse\Crypto\Crypto::decrypt($encrypted_message, $key, true);
    print('Decrypted Message: ' . $decrypted_message . PHP_EOL);

    if(hash('md5', 'Hello NCC!') == hash('md5', $decrypted_message))
    {
        print('Encryption/Decryption test successful' . PHP_EOL);
    }
    else
    {
        print('Encryption/Decryption test fail' . PHP_EOL);
    }
