<?php

namespace App\Service\Domain\Token;

use App\DTO\Domain\DomainStatusPatchDTO;
use App\Enum\DomainStatusAction;
use App\Http\Error\ApiException;
use App\Platform\Enum\Permission;
use App\Service\Domain\MailDomainGatewayService;
use App\Service\Domain\MailDomainLinkResolver;
use App\Service\ValidationService;
use App\Service\Access\AccessControlService;

final class PatchDomainStatusTokenService
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly AccessControlService $accessControl,
        private readonly MailDomainLinkResolver $domainResolver,
        private readonly MailDomainGatewayService $domainGateway,
    ) {}

    public function patch(string $domainUuid, DomainStatusPatchDTO $domainPatchDTO): void
    {
        $this->validationService->validate($domainPatchDTO, ['domain:status']);

        $action = $domainPatchDTO->toEnum();

        if ($action === DomainStatusAction::ENABLE) {
            $this->accessControl->denyUnlessPermission(Permission::DOMAINS_ENABLE);
            $active = true;
        } else {
            $this->accessControl->denyUnlessPermission(Permission::DOMAINS_DISABLE);
            $active = false;
        }

        $mailDomainId = $this->domainResolver->resolveMailDomainId($domainUuid);

        $row = $this->domainGateway->findById($mailDomainId);
        if ($row === null ) {
            throw ApiException::notFound('Domain not found or does not exist.');
        }

        $current = ((int) $row['active']) === 1;
        if ($current === $active) {
            throw ApiException::conflict($active ? 'Domain is already enabled.' : 'Domain is already disabled.');
        }

        $this->domainGateway->setActive($mailDomainId, $active);
    }
}


?>