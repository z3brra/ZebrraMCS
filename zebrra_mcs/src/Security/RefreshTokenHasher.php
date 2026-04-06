<?php

namespace App\Security;

final class RefreshTokenHasher
{
    public function hashRefresh(string $plain): string
    {
        return hash('sha256', $plain);
    }
}

?>