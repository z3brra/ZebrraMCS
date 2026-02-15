<?php

namespace App\Audit\Document;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'admin_mail_actions')]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[ODM\Index(keys: ['requestId' => 'asc'])]
#[ODM\Index(keys: ['actor.adminUuid' => 'asc'])]
#[ODM\Index(keys: ['action' => 'asc'])]
#[ODM\Index(keys: ['target.type' => 'asc'])]
#[ODM\Index(keys: ['target.domainUuid' => 'asc'])]
#[ODM\Index(keys: ['target.userUuid' => 'asc'])]
#[ODM\Index(keys: ['target.aliasUuid' => 'asc'])]
class AdminMailAuditEvent
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'date_immutable')]
    public DateTimeImmutable $createdAt;

    #[ODM\Field(type: 'string')]
    public string $requestId;

    #[ODM\Field(type: 'string')]
    public string $action;

    #[ODM\Field(type: 'hash')]
    public array $actor;

    #[ODM\Field(type: 'hash')]
    public array $context;

    #[ODM\Field(type: 'hash')]
    public array $target;

    #[ODM\Field(type: 'hash')]
    public array $result;

    #[ODM\Field(type: 'hash', nullable: true)]
    public ?array $details = null;

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $context
     * @param array<string, mixed> $target
     * @param array<string, mixed> $result
     * @param array<string, mixed>|null $details
     */
    public function __construct(
        string $requestId,
        string $action,
        array $actor,
        array $context,
        array $target,
        array $result,
        ?array $details = null
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->requestId = $requestId;
        $this->action = $action;
        $this->actor = $actor;
        $this->context = $context;
        $this->target = $target;
        $this->result = $result;
        $this->details = $details;
    }
}

?>
