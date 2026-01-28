<?php

namespace App\Audit\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use DateTimeImmutable;

#[ODM\Document(collection: 'api_errors')]
#[ODM\Index(keys: ['createdAt' => 'desc'])]
#[ODM\Index(keys: ['requestId' => 'asc'])]
#[ODM\Index(keys: ['httpStatus' => 'asc'])]
#[ODM\Index(keys: ['errorCode' => 'asc'])]
#[ODM\Index(keys: ['actor.type' => 'asc'])]
#[ODM\Index(keys: ['actor.adminUuid' => 'asc'])]
#[ODM\Index(keys: ['actor.tokenUuid' => 'asc'])]
class ApiErrorAuditEvent
{
    #[ODM\Id]
    private string $id;

    #[ODM\Field(type: 'date_immutable')]
    public DateTimeImmutable $createdAt;

    #[ODM\Field(type: 'string')]
    public string $requestId;

    #[ODM\Field(type: 'int')]
    public int $httpStatus;

    #[ODM\Field(type: 'string')]
    public string $errorCode;

    #[ODM\Field(type: 'string')]
    public string $message;

    #[ODM\Field(type: 'hash')]
    public array $actor;

    #[ODM\Field(type: 'hash')]
    public array $context;

    #[ODM\Field(type: 'hash', nullable: true)]
    public ?array $details = null;

    /**
     * @param array<string, mixed> $actor
     * @param array<string, mixed> $context
     * @param array<string, mixed>|null $details
     */
    public function __construct(
        string $requestId,
        int $httpStatus,
        string $errorCode,
        string $message,
        array $actor,
        array $context,
        ?array $details = null,
    ) {
        $this->requestId = $requestId;
        $this->httpStatus = $httpStatus;
        $this->errorCode = $errorCode;
        $this->message = $message;
        $this->actor = $actor;
        $this->context = $context;
        $this->details = $details;
    }
}

?>