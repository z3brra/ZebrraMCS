<?php

namespace App\Security;

final class ApiTokenHasher
{
    public function __construct(
        private readonly string $appSecret,
    ) {}

    public function hash(string $plainToken): string
    {
        return hash_hmac('sha256', $plainToken, $this->appSecret);
    }
}

?>