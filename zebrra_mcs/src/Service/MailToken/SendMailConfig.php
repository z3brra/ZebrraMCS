<?php

namespace App\Service\MailToken;

final class SendMailConfig
{
    public function __construct(
        public readonly int $maxRecipients = 100,
    ) {}
}

?>