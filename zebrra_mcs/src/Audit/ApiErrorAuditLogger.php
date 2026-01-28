<?php

namespace App\Audit;

use App\Audit\Document\ApiErrorAuditEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

final class ApiErrorAuditLogger
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly AuditContextProvider $context,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed>|null $details
     */
    public function log(
        int $httpStatus,
        string $errorCode,
        string $message,
        ?array $details = null,
        ?array $actorOverride = null
    ): void {
        $event = new ApiErrorAuditEvent(
            requestId: $this->context->getRequestId(),
            httpStatus: $httpStatus,
            errorCode: $errorCode,
            message: $message,
            actor: $actorOverride ?? $this->context->getActor(),
            context: $this->context->getContext(),
            details: $details,
        );

        $this->persistSafely($event);
    }

    private function persistSafely(ApiErrorAuditEvent $event): void
    {
        try {
            $this->documentManager->persist($event);
            $this->documentManager->flush();
        } catch (\Throwable $e) {
            $this->logger->warning(
                message:'MongoDB audit write failed (api_errors).',
                context: [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'requestId' => $event->requestId ?? null,
                    'httpStatus' => $event->httpStatus ?? null,
                    'errorCode' => $event->errorCode ?? null,
                ],
            );

            try {
                $this->documentManager->clear();
            } catch (\Throwable) {}
        }
    }
}

?>