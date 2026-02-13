<?php

namespace App\Service\Domain;

use App\DTO\Domain\{
    DomainReadDTO,
    DomainRenameDTO
};
use App\Http\Error\ApiException;
use App\Service\ValidationService;

use App\Audit\AdminMailAuditLogger;
use phpDocumentor\Reflection\Types\String_;
use PhpParser\ErrorHandler\Throwing;

use function PHPSTORM_META\map;

final class RenameDomainAdminService
{
    public function __construct(
        private readonly MailDomainLinkResolver $resolver,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly ValidationService $validationService,
        private readonly AdminMailAuditLogger $audit,
    ) {}

    public function rename(string $domainUuid, DomainRenameDTO $renameDTO): DomainReadDTO
    {
        $this->validationService->validate($renameDTO, ['domain:rename']);

        $mailDomainId = $this->resolver->resolveMailDomainId($domainUuid);

        $current = $this->mailDomainGateway->findById($mailDomainId);
        if ($current === null) {
            $this->audit->error(
                action: 'domain.rename',
                target: [
                    'type' => 'domain',
                    'domainUuid' => $domainUuid,
                    'mailDomainId' => $mailDomainId,
                    'name' => null,
                ],
                message: 'Domain not found or does not exist.',
            );

            throw ApiException::notFound("Domain not found or does not exist.");
        }

        $oldName = (string) $current['name'];
        $oldActive = ((int) $current['active']) === 1;

        $newName = mb_strtolower(trim((string) $renameDTO->name));

        if (strcasecmp($oldName, $newName) === 0) {
            $this->audit->success(
                action: 'domain.rename',
                target: [
                    'type' => 'domain',
                    'domainUuid' => $domainUuid,
                    'mailDomainId' => $mailDomainId,
                    'name' => $oldName,
                ],
                details: [
                    'noop' => true,
                    'before' => ['name' => $oldName],
                    'after' => ['name' => $oldName],
                ],
            );

            return new DomainReadDTO(
                uuid: $domainUuid,
                name: $current['name'],
                active: $current['active'] === 1
            );
        }

        if ($this->mailDomainGateway->existsByName($newName)) {
            $this->audit->error(
                action: 'domain.rename',
                target: [
                    'type' => 'domain',
                    'domainUuid' => $domainUuid,
                    'mailDomainId' => $mailDomainId,
                    'name' => $oldName,
                ],
                message: 'A domain with this name already exists.',
                details: [
                    'attemptedName' => $newName,
                ],
            );

            throw ApiException::conflict('A domain with this name already exists.');
        }

        $this->mailDomainGateway->rename($mailDomainId, $newName);

        $updated = $this->mailDomainGateway->findById($mailDomainId);
        if ($updated === null) {
            $this->audit->error(
                action: 'domain.rename',
                target: [
                    'type' => 'domain.rename',
                    'domainUuid' => $domainUuid,
                    'mailDomainId' => $mailDomainId,
                    'name' => $oldName,
                ],
                message: 'Domain rename failed unexpectedly',
                details: [
                    'attemptedName' => $newName,
                ],
            );
            throw ApiException::internal("Domain rename failed unexpectedly");
        }

        $newActive = ((int) $updated['active']) === 1;

        $this->audit->success(
            action: 'domain.rename',
            target: [
                'type' => 'domain',
                'domainUuid' => $domainUuid,
                'mailDomainId' => $mailDomainId,
                'name' => (string) $updated['name'],
            ],
            details: [
                'before' => [
                    'name' => $oldName,
                    'active' => $oldActive,
                ],
                'after' => [
                    'name' => (string) $updated['name'],
                    'active' => $newActive,
                ],
            ],
        );

        return new DomainReadDTO(
            uuid: $domainUuid,
            name: (string) $updated['name'],
            active: $newActive
        );
    }
}

?>