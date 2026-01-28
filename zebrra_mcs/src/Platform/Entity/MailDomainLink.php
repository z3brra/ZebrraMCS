<?php

namespace App\Platform\Entity;

use App\Platform\Repository\MailDomainLinkRepository;

use Doctrine\ORM\Mapping as ORM;

use Ramsey\Uuid\Uuid;

use DateTimeImmutable;

#[ORM\Entity(repositoryClass: MailDomainLinkRepository::class)]
#[ORM\Table(name: 'mail_domain_links')]
#[ORM\UniqueConstraint(name: 'UNIQ_MAIL_DOMAIN_LINKS_UUID', fields: ['uuid'])]
#[ORM\UniqueConstraint(name: 'UNIQ_MAIL_DOMAIN_LINKS_MAIL_DOMAIN_ID', fields: ['mailDomainId'])]
#[ORM\Index(name: 'IDX_MAIL_DOMAIN_LINKGS_MAIL_DOMAIN_ID', columns: ['mailDomainId'])]
#[ORM\HasLifecycleCallbacks]
class MailDomainLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column]
    private int $mailDomainId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(int $mailDomainId)
    {
        $this->uuid = Uuid::uuid7()->toString();
        $this->mailDomainId = $mailDomainId;
        $this->createdAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMailDomainId(): int
    {
        return $this->mailDomainId;
    }

    public function setMailDomainId(int $mailDomainId): static
    {
        $this->mailDomainId = $mailDomainId;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

?>