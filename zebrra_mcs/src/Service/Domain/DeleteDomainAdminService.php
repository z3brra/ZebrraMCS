<?php

namespace App\Service\Domain;

use App\Platform\Repository\MailDomainLinkRepository;
use App\Http\Error\ApiException;

use Doctrine\ORM\EntityManagerInterface;

final class DeleteDomainAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailDomainLinkResolver $resolver,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly MailDomainLinkRepository $domainLinkRepository,
    ) {}

    public function hardDelete(string $domainUuid): void
    {
        $mailDomainId = $this->resolver->resolveMailDomainId($domainUuid);

        $domainRow = $this->mailDomainGateway->findById($mailDomainId);
        if ($domainRow === null) {
            throw ApiException::notFound('Domain not found or does not exist.');
        }

        $domainName = (string) $domainRow['name'];

        $usersCount = $this->mailDomainGateway->countUsersByDomainId($mailDomainId);
        $aliasesCount = $this->mailDomainGateway->countAliasesByDomainName($mailDomainId);

        if ($usersCount > 0 || $aliasesCount > 0) {
            throw ApiException::conflict(
                message: 'Domain cannot be deleted because it still has related objects.',
                details: [
                    'blocking' => [
                        'users' => $usersCount,
                        'aliases' => $aliasesCount
                    ],
                ],
            );
        }

        $this->mailDomainGateway->deleteDomainById($mailDomainId);

        $link = $this->domainLinkRepository->findOneByUuid($domainUuid);
        if (!$link) {
            throw ApiException::notFound('Domain link not found or does not exist.');
        }
        $this->entityManager->remove($link);
        $this->entityManager->flush();
    }
}

?>