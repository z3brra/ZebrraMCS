<?php

namespace App\Service\MailUser;

use App\Http\Error\ApiException;

use App\Audit\AdminMailAuditLogger;

use Doctrine\ORM\EntityManagerInterface;

final class DeleteMailUserAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailUserLinkResolver $mailUserResolver,
        private readonly MailUserGatewayService $mailUserGateway,

        private readonly AdminMailAuditLogger $audit,
    ) {}

    public function delete(string $userUuid): void
    {
        $link = $this->mailUserResolver->resolveLinkByUuid($userUuid);
        $mailUserId = $link->getMailUserId();

        $mailUserRow = $this->mailUserGateway->findById($mailUserId);
        if ($mailUserRow === null) {
            $this->audit->error(
                action: 'mail_user.delete',
                target: $this->audit->auditTargetMailUser(
                    userUuid: $link->getUuid(),
                    mailUserId: $mailUserId,
                    email: $link->getEmail(),
                    domainUuid: null,
                    mailDomainId: $link->getMailDomainId()
                ),
                message: 'Mail user row not found while link exists.',
                details: [
                    'reason' => 'mail_user.missing'
                ]
            );
            throw ApiException::notFound('User not found or does not exist.');
        }
-
        $this->mailUserGateway->deleteUserById($mailUserId);

        $this->entityManager->remove($link);
        $this->entityManager->flush();

        $this->audit->success(
            action: 'mail_user.delete',
            target: $this->audit->auditTargetMailUser(
                userUuid: $link->getUuid(),
                mailUserId: $mailUserId,
                email: $link->getEmail(),
                domainUuid: null,
                mailDomainId: $link->getMailDomainId()
            ),
            details: [
                'deletedEmail' => (string) ($mailUserRow['email'] ?? $link->getEmail())
            ]
        );
    }
}

?>