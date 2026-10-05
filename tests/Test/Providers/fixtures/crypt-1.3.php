<?php

/*
 * Values encrypted by Phalcon 3.4 (Nucleon 1.3), read by CryptCompatibilityTest (E7-S5).
 *
 *   docker compose run --rm -T legacy php tests/Test/Providers/fixtures/crypt-1.3.php > tests/Test/Providers/fixtures/crypt-1.3.json
 *
 * Nucleon 1.3 never enabled the signing: the `signing: true` values only show that signed data stays readable.
 */

$key = 'Nucleon-1.3-key-32-bytes-long!!!';
$plain = 'Nucleon 1.3 secret: été';
$values = [];

foreach (['aes-256-cfb', 'aes-256-cbc', 'aes-128-ctr'] as $cipher) {
    foreach ([false, true] as $signing) {
        $crypt = new Phalcon\Crypt();
        $crypt->setCipher($cipher);
        $crypt->setKey($key);
        $crypt->useSigning($signing);

        $values[] = [
            'cipher'      => $cipher,
            'signing'     => $signing,
            'plain'       => $plain,
            'base64'      => $crypt->encryptBase64($plain),
            'base64_safe' => $crypt->encryptBase64($plain, null, true),
        ];
    }
}

echo json_encode(
    ['phalcon' => Phalcon\Version::get(), 'key' => base64_encode($key), 'values' => $values],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
), "\n";
