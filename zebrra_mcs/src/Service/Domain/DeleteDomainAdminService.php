<?php

namespace App\Service\Domain;

use App\Platform\Repository\MailDomainLinkRepository;
use App\Http\Error\ApiException;

use App\Audit\AdminMailAuditLogger;

use Doctrine\ORM\EntityManagerInterface;

final class DeleteDomainAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailDomainLinkResolver $resolver,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly MailDomainLinkRepository $domainLinkRepository,
        private readonly AdminMailAuditLogger $audit,
    ) {}

    public function hardDelete(string $domainUuid): void
    {
        $mailDomainId = $this->resolver->resolveMailDomainId($domainUuid);

        $domainRow = $this->mailDomainGateway->findById($mailDomainId);
        if ($domainRow === null) {
            $this->audit->error(
                action: 'domain.delete',
                target: $this->audit->auditTargetDomain(
                    domainUuid: $domainUuid,
                    mailDomainId: $mailDomainId,
                    name: null,
                ),
                message: 'Domain not found or does not exists.',
            );

            throw ApiException::notFound('Domain not found or does not exist.');
        }

        $domainName = (string) $domainRow['name'];

        $usersCount = $this->mailDomainGateway->countUsersByDomainId($mailDomainId);
        $aliasesCount = $this->mailDomainGateway->countAliasesByDomainName($domainName);

        if ($usersCount > 0 || $aliasesCount > 0) {
            $this->audit->error(
                action: 'domain.delete',
                target: $this->audit->auditTargetDomain(
                    domainUuid: $domainUuid,
                    mailDomainId: $mailDomainId,
                    name: $domainName,
                ),
                message: 'Domain cannot be deleted because it still has related objects.',
                details: [
                    'blocking' => [
                        'users' => $usersCount,
                        'aliases' => $aliasesCount,
                    ],
                ],
            );

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
            $this->audit->error(
                action: 'domain.delete',
                target: $this->audit->auditTargetDomain(
                    domainUuid: $domainUuid,
                    mailDomainId: $mailDomainId,
                    name: $domainName,
                ),
                message: 'Domain link not found or does not exist.'
            );

            throw ApiException::notFound('Domain link not found or does not exist.');
        }
        $this->entityManager->remove($link);
        $this->entityManager->flush();

        $this->audit->success(
            action: 'domain.delete',
            target: $this->audit->auditTargetDomain(
                domainUuid: $domainUuid,
                mailDomainId: $mailDomainId,
                name: $domainName,
            ),
        );
    }
}

?>