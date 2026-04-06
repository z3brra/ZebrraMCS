<?php

namespace App\Audit;

use App\Audit\Document\TokenMailAuditEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

final class TokenMailAuditLogger
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
        $event = new TokenMailAuditEvent(
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
        $event = new TokenMailAuditEvent(
            requestId: $this->context->getRequestId(),
            action: $action,
            actor: $this->context->getActor(),
            context: $this->context->getContext(),
            target: $target,
            result: [
                'status' => 'error',
                'message' => $message,
            ],
            details: $details,
        );

        $this->persistSafely($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function targetSendMail(
        string $fromEmail,
        string $domainUuid
    ): array {
        return [
            'type' => 'mail_send',
            'fromEmail' => $fromEmail,
            'domainUuid' => $domainUuid,
        ];
    }

    private function persistSafely(TokenMailAuditEvent $event): void
    {
        try {
            $this->documentManager->persist($event);
            $this->documentManager->flush();
        } catch (\Throwable $e) {
            $this->logger->warning(
                'MongoDB audit write failed (token_mail_actions).',
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

}

?>