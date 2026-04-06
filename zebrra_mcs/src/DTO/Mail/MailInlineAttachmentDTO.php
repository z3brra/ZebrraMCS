<?php

namespace App\DTO\Mail;

use Symfony\Component\Validator\Constraints as Assert;

final class MailInlineAttachmentDTO
{
    #[Assert\NotBlank(groups: ['mail:send'])]
    #[Assert\Length(max: 255, groups: ['mail:send'])]
    public string $filename;

    #[Assert\Length(max: 255, groups: ['mail:send'])]
    public ?string $contentType = null;

    #[Assert\NotBlank(groups: ['mail:send'])]
    public string $contentBase64;


    #[Assert\NotBlank(groups: ['mail:send'])]
    #[Assert\Length(max: 255, groups: ['mail:send'])]
    public string $contentId;

}

?>