<?php

namespace App\Platform\Entity;

use App\Platform\Repository\MailUserLinkRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: MailUserLinkRepository::class)]
#[ORM\Table(name: 'mail_user_links')]
#[ORM\UniqueConstraint(name: 'UNIQ_MAIL_USER_LINKS_UUID', fields: ['uuid'])]
#[ORM\UniqueConstraint(name: 'UNIQ_MAIL_USER_LINKS_USER_ID', fields: ['mailUserId'])]
#[ORM\Index(name: 'IDX_MAIL_USER_LINKS_MAIL_DOMAIN_ID', columns: ['mailDomainId'])]
#[ORM\Index(name: 'IDX_MAIL_USER_LINKS_EMAIL', columns: ['email'])]
class MailUserLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column]
    private int $mailUserId;

    #[ORM\Column]
    private int $mailDomainId;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(int $mailUserId, int $mailDomainId, string $email)
    {
        $this->uuid = Uuid::uuid7()->toString();
        $this->createdAt = new DateTimeImmutable();

        $this->mailUserId = $mailUserId;
        $this->mailDomainId = $mailDomainId;
        $this->email = $email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMailUserId(): int
    {
        return $this->mailUserId;
    }

    public function getMailDomainId(): int
    {
        return $this->mailDomainId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

?>