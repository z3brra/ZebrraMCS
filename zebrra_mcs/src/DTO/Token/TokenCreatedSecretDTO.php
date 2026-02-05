<?php

namespace App\DTO\Token;

use Symfony\Component\Serializer\Attribute\Groups;

final class TokenCreatedSecretDTO
{
    #[Groups(['token:secret'])]
    public string $uuid;

    #[Groups(['token:secret'])]
    public string $token;

    public function __construct(
        string $uuid,
        string $token
    ) {
        $this->uuid = $uuid;
        $this->token = $token;
    }
}

?>