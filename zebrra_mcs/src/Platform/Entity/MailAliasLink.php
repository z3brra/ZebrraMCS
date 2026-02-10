<?php

namespace App\Platform\Entity;

use App\Platform\Repository\MailAliasLinkRepository;
use DateTimeImmutable;

use Doctrine\ORM\Mapping as ORM;

use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: MailAliasLinkRepository::class)]
#[ORM\Table(name: 'mail_alias_links')]
#[ORM\UniqueConstraint(name: 'UNIQ_MAIL_ALIAS_LINK_UUID', fields: ['uuid'])]
#[ORM\UniqueConstraint(name: 'UNIQ_MAIL_ALIAS_LINK_MAIL_ALIAS_ID', fields: ['mailAliasId'])]
#[ORM\Index(name: 'IDX_MAIL_ALIAS_LINK_MAIL_ALIAS_ID', columns: ['mailAliasId'])]
class MailAliasLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column]
    private int $mailAliasId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(int $mailAliasId)
    {
        $this->uuid = Uuid::uuid7()->toString();
        $this->mailAliasId = $mailAliasId;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMailAliasId(): int
    {
        return $this->mailAliasId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}


?>