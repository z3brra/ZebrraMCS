<?php

namespace App\Service\MailUser;

use App\Http\Error\ApiException;
use App\Service\Access\AccessControlService;

use Doctrine\ORM\EntityManagerInterface;
use PhpParser\ErrorHandler\Throwing;

final class DeleteMailUserAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessControlService $accessControl,
        private readonly MailUserLinkResolver $mailUserResolver,
        private readonly MailUserGatewayService $mailUserGateway,
    ) {}

    public function delete(string $userUuid): void
    {
        $link = $this->mailUserResolver->resolveLinkByUuid($userUuid);
        $mailUserId = $link->getMailUserId();

        $mailUserRow = $this->mailUserGateway->findById($mailUserId);
        if ($mailUserRow === null) {
            throw ApiException::notFound('User not found or does not exist.');
        }

        $this->mailUserGateway->deleteUserById($mailUserId);

        $this->entityManager->remove($link);
        $this->entityManager->flush();
    }
}

?>