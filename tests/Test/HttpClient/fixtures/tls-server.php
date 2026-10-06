<?php

declare(strict_types=1);

/*
 * A TLS server answering "tls ok" to every request, with a self-signed certificate for 127.0.0.1.
 *
 * php tls-server.php <port> <certificate file>: the certificate is written to the file (for `cafile`),
 * then "ready" is printed.
 */

[, $port, $certificateFile] = $argv;

$config = tempnam(sys_get_temp_dir(), 'nucleon-openssl');
file_put_contents($config, "[req]\ndistinguished_name = dn\n[dn]\n[ext]\nsubjectAltName = IP:127.0.0.1\nbasicConstraints = CA:TRUE\n");

$options = ['config' => $config, 'x509_extensions' => 'ext', 'digest_alg' => 'sha256'];
$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA] + $options);
$csr = openssl_csr_new(['commonName' => '127.0.0.1'], $key, $options);
$certificate = openssl_csr_sign($csr, null, $key, 1, $options);

openssl_x509_export($certificate, $pem);
openssl_pkey_export($key, $keyPem, null, $options);
file_put_contents($certificateFile, $pem);
file_put_contents($certificateFile . '.pem', $pem . $keyPem);
unlink($config);

$server = stream_socket_server('tls://127.0.0.1:' . $port, $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, stream_context_create([
    'ssl' => ['local_cert' => $certificateFile . '.pem', 'verify_peer' => false],
]));

if ($server === false) {
    fwrite(STDERR, $error);
    exit(1);
}

echo "ready\n";

while (true) {
    // A client that refuses the certificate fails the handshake: the connection is dropped.
    $client = @stream_socket_accept($server, 30);

    if ($client === false) {
        continue;
    }

    // Reads the head of the request.
    do {
        $line = fgets($client);
    } while ($line !== false && rtrim($line) !== '');

    fwrite($client, "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 6\r\nConnection: close\r\n\r\ntls ok");
    fclose($client);
}
