<?php

namespace App\Service;

class Encryptor
{
    private string $secret;

    public function __construct(string $appEncryptionKey)
    {
        $this->secret = $appEncryptionKey;
    }

    public function encrypt(string $data): string
    {
        return base64_encode(openssl_encrypt($data, 'aes-256-cbc', $this->secret, 0, substr($this->secret, 0, 16)));
    }

    public function decrypt(string $data): string
    {
        return openssl_decrypt(base64_decode($data), 'aes-256-cbc', $this->secret, 0, substr($this->secret, 0, 16));
    }
}
