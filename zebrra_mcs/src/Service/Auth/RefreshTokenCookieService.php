<?php

namespace App\Service\Auth;

use Symfony\Component\HttpFoundation\Cookie;

final class RefreshTokenCookieService
{
    public function __construct(
        private readonly RefreshTokenConfig $refreshTokenConfig,
    ) {}

    public function createCookie(string $plainRefreshToken): Cookie
    {
        $expires = time() + $this->refreshTokenConfig->ttlSeconds;

        return Cookie::create(
            name: $this->refreshTokenConfig->cookieName,
            value: $plainRefreshToken,
            expire: $expires,
            path: $this->refreshTokenConfig->path,
            domain: null,
            secure: $this->refreshTokenConfig->secure,
            httpOnly: $this->refreshTokenConfig->httpOnly,
            raw: false,
            sameSite: $this->refreshTokenConfig->sameSite,
        );
    }

    public function clearCookie(): Cookie
    {
        return Cookie::create(
            name: $this->refreshTokenConfig->cookieName,
            value: '',
            expire: 1,
            path: $this->refreshTokenConfig->path,
            domain: null,
            secure: $this->refreshTokenConfig->secure,
            httpOnly: $this->refreshTokenConfig->httpOnly,
            raw: false,
            sameSite: $this->refreshTokenConfig->sameSite,
        );
    }
}

?>