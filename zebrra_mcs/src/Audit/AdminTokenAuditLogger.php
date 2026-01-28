<?php

namespace App\Audit;

use App\Audit\Document\AdminTokenAuditEvent;
use App\Platform\Entity\ApiToken;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

final class AdminTokenAuditLogger
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly AuditContextProvider $context,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed>|null $details
     */
    public function success(string $action, ?ApiToken $token = null, ?array $details = null): void
    {
        $event = new AdminTokenAuditEvent(
            requestId: $this->context->getRequestId(),
            action: $action,
            actor: $this->context->getActor(),
            context: $this->context->getContext(),
            target: $this->targetFromToken($token),
            result: [
                'status' => 'success'
            ],
            details: $details,
        );

        $this->persistSafely($event);
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public function error(string $action, ?ApiToken $token, string $message, ?array $details = null): void
    {
        $event = new AdminTokenAuditEvent(
            requestId: $this->context->getRequestId(),
            action: $action,
            actor: $this->context->getActor(),
            context: $this->context->getContext(),
            target: $this->targetFromToken($token),
            result: [
                'status' => 'error',
                'message' => $message,
            ],
            details: $details
        );

        $this->persistSafely($event);
    }

    private function persistSafely(AdminTokenAuditEvent $event): void
    {
        try {
            $this->documentManager->persist($event);
            $this->documentManager->flush();
        } catch (\Throwable $e) {
            $this->logger->warning(
                message: 'MongoDB audit write failed (admin_token_actions).',
                context: [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'action' => $event->action ?? null,
                    'requestId' => $event->requestId ?? null,
                ],
            );

            try {
                $this->documentManager->clear();
            } catch (\Throwable) {}
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function targetFromToken(?ApiToken $token): array
    {
        if (!$token) {
            return [
                'type' => 'token',
                'tokenUuid' => null,
                'name' => null
            ];
        }

        return [
            'type' => 'token',
            'tokenUuid' => $token->getUuid(),
            'name' => $token->getName(),
        ];
    }
}

?>