<?php

namespace App\Service\Domain;

use App\Platform\Repository\MailDomainLinkRepository;
use App\Http\Error\ApiException;

final class MailDomainLinkResolver
{
    public function __construct(
        private readonly MailDomainLinkRepository $mailDomainRepository
    ) {}

    public function resolveMailDomainId(string $domainUuid): int
    {
        $link = $this->mailDomainRepository->findOneByUuid($domainUuid);
        if (!$link) {
            throw ApiException::notFound('Domain not found or does not exist.');
        }
        return $link->getMailDomainId();
    }

    public function resolveMailDomainUuid(int $mailDomainId): string
    {
        $link = $this->mailDomainRepository->findOneByMailDomainId($mailDomainId);
        if (!$link) {
            throw ApiException::notFound('Domain mapping not found or does not exist.');
        }
        return $link->getUuid();
    }
}

?>