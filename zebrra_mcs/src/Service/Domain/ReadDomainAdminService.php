<?php

namespace App\Service\Domain;

use App\Platform\Repository\MailDomainLinkRepository;
use App\DTO\Domain\DomainReadDTO;
use App\Http\Error\ApiException;

final class ReadDomainAdminService
{
    public function __construct(
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly MailDomainLinkResolver $resolver,
    ) {}

    /**
     * @return array{data: DomainReadDTO}
     */
    public function read(string $domainUuid): array
    {
        $mailDomainId = $this->resolver->resolveMailDomainId($domainUuid);

        $row = $this->mailDomainGateway->findById($mailDomainId);
        if ($row === null) {
            throw ApiException::notFound("Domain not found or does not exist.");
        }

        $readDTO = new DomainReadDTO(
            uuid: $domainUuid,
            name: $row['name'],
            active: $row['active'] === 1,
        );

        return [
            'data' => $readDTO
        ];
    }
}


?>