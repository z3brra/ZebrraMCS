<?php

namespace App\DTO\Mail;

use Symfony\Component\Serializer\Attribute\Groups;

final class MailSendResponseDTO
{
    #[Groups(['mail:send:response'])]
    public string $status;

    #[Groups(['mail:send:response'])]
    public ?string $messageId;

    public function __construct(
        string $status,
        ?string $messageId = null
    ) {
        $this->status = $status;
        $this->messageId = $messageId;
    }
}

?>