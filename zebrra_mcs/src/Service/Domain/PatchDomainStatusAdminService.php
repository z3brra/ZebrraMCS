<?php

namespace App\Service\Domain;

use App\DTO\Domain\DomainStatusPatchDTO;
use App\Enum\DomainStatusAction;
use App\Http\Error\ApiException;
use App\Service\ValidationService;

use App\Audit\AdminMailAuditLogger;

final class PatchDomainStatusAdminService
{
    public function __construct(
        private readonly MailDomainLinkResolver $resolver,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly ValidationService $validationService,
        private readonly AdminMailAuditLogger $audit,
    ) {}

    public function patch(string $domainUuid, DomainStatusPatchDTO $statusPatchDTO): void
    {
        $this->validationService->validate($statusPatchDTO, ['domain:status']);

        $mailDomainId = $this->resolver->resolveMailDomainId($domainUuid);

        $domainRow = $this->mailDomainGateway->findById($mailDomainId);
        if ($domainRow === null) {
            $this->audit->error(
                action: 'domain.status',
                target: $this->audit->auditTargetDomain(
                    domainUuid: $domainUuid,
                    mailDomainId: $mailDomainId,
                    name: null,
                ),
                message: 'Domain not found or does not exist.',
            );
            throw ApiException::notFound('Domain not found or does not exist.');
        }

        $domainName = (string) $domainRow['name'];
        $currentActive = ((int) $domainRow['active']) === 1;

        $action = $statusPatchDTO->toEnum();

        $desiredActive = match ($action) {
            DomainStatusAction::ENABLE => true,
            DomainStatusAction::DISABLE => false,
        };

        if ($currentActive === $desiredActive) {
            $this->audit->error(
                action: 'domain.status',
                target: $this->audit->auditTargetDomain(
                    domainUuid: $domainUuid,
                    mailDomainId: $mailDomainId,
                    name: $domainName,
                ),
                message: 'Domain is already in the requested status.',
                details: [
                    'requested' => $action->value,
                    'active' => $currentActive,
                ]
            );

            throw ApiException::conflict('Domain is already in the requested status.');
        }

        $this->mailDomainGateway->setActive($mailDomainId, $desiredActive);

        $this->audit->success(
            action: 'domain.status',
            target: $this->audit->auditTargetDomain(
                domainUuid: $domainUuid,
                mailDomainId: $mailDomainId,
                name: $domainName
            ),
            details: [
                'requested' => $action->value,
                'before' => ['active' => $currentActive],
                'after' => ['active' => $desiredActive],
            ]
        );
    }
}

?>