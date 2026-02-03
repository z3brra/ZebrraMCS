<?php

namespace App\Service\Domain;

use App\DTO\Domain\DomainStatusPatchDTO;
use App\Enum\DomainStatusAction;
use App\Http\Error\ApiException;
use App\Service\ValidationService;

final class PatchDomainStatusAdminService
{
    public function __construct(
        private readonly MailDomainLinkResolver $resolver,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly ValidationService $validationService,
    ) {}

    public function patch(string $domainUuid, DomainStatusPatchDTO $statusPatchDTO): void
    {
        $this->validationService->validate($statusPatchDTO, ['domain:status']);

        $mailDomainId = $this->resolver->resolveMailDomainId($domainUuid);
        var_dump('ici');
        var_dump($mailDomainId);

        $action = $statusPatchDTO->toEnum();

        $active = match ($action) {
            DomainStatusAction::ENABLE => true,
            DomainStatusAction::DISABLE => false,
        };

        $this->mailDomainGateway->setActive($mailDomainId, $active);
    }
}

?>