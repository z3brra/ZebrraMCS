<?php

namespace App\Service\Domain;

use App\DTO\Domain\{
    DomainReadDTO,
    DomainRenameDTO
};
use App\Http\Error\ApiException;
use App\Service\ValidationService;
use PhpParser\ErrorHandler\Throwing;

final class RenameDomainAdminService
{
    public function __construct(
        private readonly MailDomainLinkResolver $resolver,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly ValidationService $validationService,
    ) {}

    public function rename(string $domainUuid, DomainRenameDTO $renameDTO): DomainReadDTO
    {
        $this->validationService->validate($renameDTO, ['domain:rename']);

        $mailDomainId = $this->resolver->resolveMailDomainId($domainUuid);

        $current = $this->mailDomainGateway->findById($mailDomainId);
        if ($current === null) {
            throw ApiException::notFound("Domain not found or does not exist.");
        }

        $newName = trim($renameDTO->name);

        if (strcasecmp($current['name'], $newName) === 0) {
            return new DomainReadDTO(
                uuid: $domainUuid,
                name: $current['name'],
                active: $current['active'] === 1
            );
        }

        if ($this->mailDomainGateway->existsByName($newName)) {
            throw ApiException::conflict('A domain with this name already exists.');
        }

        $this->mailDomainGateway->rename($mailDomainId, $newName);

        $updated = $this->mailDomainGateway->findById($mailDomainId);
        if ($updated === null) {
            throw ApiException::internal("Domain rename failed unexpectedly");
        }

        return new DomainReadDTO(
            uuid: $domainUuid,
            name: $updated['name'],
            active: $updated['active'] === 1
        );
    }
}

?>