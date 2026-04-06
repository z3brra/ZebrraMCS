<?php

namespace App\DTO\Mail;

use App\DTO\Mail\MailAttachmentDTO;
use Symfony\Component\Validator\Constraints as Assert;

final class MailSendRequestDTO
{
    #[Assert\NotNull(groups: ['mail:send'])]
    #[Assert\Valid(groups: ['mail:send'])]
    public MailAddressDTO $from;

    #[Assert\NotNull(groups: ['mail:send'])]
    #[Assert\Count(min: 1, groups: ['mail:send'])]
    #[Assert\Valid(groups: ['mail:send'])]
    public array $to = [];
    
    #[Assert\Valid(groups: ['mail:send'])]
    public array $cc = [];

    #[Assert\Valid(groups: ['mail:send'])]
    public array $bcc = [];

    #[Assert\Valid(groups: ['mail:send'])]
    public array $replyTo = [];

    #[Assert\Length(max: 320, groups: ['mail:send'])]
    #[Assert\Email(groups: ['mail:send'])]
    public ?string $returnPath = null;

    #[Assert\NotBlank(groups: ['mail:send'])]
    #[Assert\Length(max: 255, groups: ['mail:send'])]
    public string $subject;

    #[Assert\Length(max: 100000, groups: ['mail:send'])]
    public ?string $textBody = null;

    #[Assert\Length(max: 200000, groups: ['mail:send'])]
    public ?string $htmlBody = null;

    /** @var list<MailAttachmentDTO> */
    #[Assert\Valid(groups: ['mail:send'])]
    #[Assert\Count(max: 10, groups: ['mail:send'])]
    public array $attachments = [];
}

?>