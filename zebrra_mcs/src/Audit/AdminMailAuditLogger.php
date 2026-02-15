<?php

namespace App\Audit;

use App\Audit\Document\AdminMailAuditEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

final class AdminMailAuditLogger
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly AuditContextProvider $context,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed>|null $details
     */
    public function success(string $action, array $target, ?array $details = null): void
    {
        $event = new AdminMailAuditEvent(
            requestId: $this->context->getRequestId(),
            action: $action,
            actor: $this->context->getActor(),
            context: $this->context->getContext(),
            target: $target,
            result: ['status' => 'success'],
            details: $details
        );

        $this->persistSafely($event);
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed>|null $details
     */
    public function error(string $action, array $target, string $message, ?array $details = null): void
    {
        $event = new AdminMailAuditEvent(
            requestId: $this->context->getRequestId(),
            action: $action,
            actor: $this->context->getActor(),
            context: $this->context->getContext(),
            target: $target,
            result: [
                'status' => 'error',
                'message' => $message
            ],
            details: $details,
        );

        $this->persistSafely($event);
    }

    private function persistSafely(AdminMailAuditEvent $event): void
    {
        try {
            $this->documentManager->persist($event);
            $this->documentManager->flush();
        } catch (\Throwable $e) {
            $this->logger->warning(
                'MongoDB audit write failed (admin_mail_actions).',
                [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'action' => $event->action ?? null,
                    'requestId' => $event->requestId ?? null,
                ]
            );

            try {
                $this->documentManager->clear();
            } catch (\Throwable) {}
        }
    }

    public function auditTargetDomain(
        ?string $domainUuid,
        ?string $mailDomainId,
        ?string $name
    ): array {
        return [
            'type' => 'domain',
            'domainUuid' => $domainUuid,
            'mailDomainId' => $mailDomainId,
            'name' => $name
        ];
    }

    public function auditTargetMailUser(
        ?string $userUuid,
        ?int $mailUserId,
        ?string $email,
        ?string $domainUuid,
        ?int $mailDomainId,
    ): array {
        return [
            'type' => 'mail_user',
            'userUuid' => $userUuid,
            'mailUserId' => $mailUserId,
            'email' => $email,
            'domainUuid' => $domainUuid,
            'mailDomainId' => $mailDomainId
        ];
    }

    public function auditTargetMailAlias(
        ?string $aliasUuid,
        ?int $mailAliasId,
        string $sourceEmail,
        string $destinationEmail
    ): array {
        return [
            'type' => 'mail_alias',
            'aliasUuid' => $aliasUuid,
            'mailAliasId' => $mailAliasId,
            'sourceEmail' => $sourceEmail,
            'destinationEmail' => $destinationEmail,
        ];
    }
}

?>