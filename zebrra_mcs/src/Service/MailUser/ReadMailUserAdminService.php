<?php

namespace App\Service\MailUser;

use App\DTO\MailUser\MailUserReadDTO;

use App\Service\Domain\MailDomainLinkResolver;

use App\Http\Error\ApiException;


final class ReadMailUserAdminService
{
    public function __construct(
        private readonly MailUserLinkResolver $mailUserResolver,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailDomainLinkResolver $mailDomainResolver,
    ) {}

    public function read(string $uuid): MailUserReadDTO
    {
        $link = $this->mailUserResolver->resolveLinkByUuid($uuid);

        $row = $this->mailUserGateway->findById($link->getMailUserId());
        if ($row === null) {
            throw ApiException::notFound('User not found or does not exist');
        }

        $domainUuid = $this->mailDomainResolver->resolveMailDomainUuid($link->getMailDomainId());

        $mailUserReadDTO = new MailUserReadDTO(
            uuid: $link->getUuid(),
            email: (string) $row['email'],
            domainUuid: $domainUuid,
            active: ((int) $row['active']) === 1
        );

        return $mailUserReadDTO;
    }
}

?>