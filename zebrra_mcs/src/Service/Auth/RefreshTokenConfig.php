<?php

namespace App\Service\Auth;

final class RefreshTokenConfig
{
    public function __construct(
        public readonly string $cookieName = 'refresh_token',
        public readonly int $ttlSeconds = 60 * 60 * 24 * 30,
        public readonly string $path = '/api/v1/auth',
        public readonly bool $secure = false,
        public readonly bool $httpOnly = true,
        public readonly string $sameSite = 'Lax',
    ) {}
}

?>