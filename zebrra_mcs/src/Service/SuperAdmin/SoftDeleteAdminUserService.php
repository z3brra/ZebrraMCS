<?php

namespace App\Service\SuperAdmin;

use App\Platform\Repository\AdminUserRepository;
use App\Service\MailUser\MailUserGatewayService;
use App\Http\Error\ApiException;
use App\Platform\Entity\AdminUser;
use App\Service\Access\AccessControlService;

use Doctrine\ORM\EntityManagerInterface;

final class SoftDeleteAdminUserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessControlService $accessControl,

        private readonly AdminUserRepository $adminUserRepository,
        private readonly MailUserGatewayService $mailUserGateway,
    ) {}

    public function delete(string $adminUuid): void
    {
        /**
         * @var AdminUser $actor
         */
        $actor = $this->accessControl->getActor();

        $admin = $this->adminUserRepository->findOneByUuid($adminUuid);
        if (!$admin) {
            throw ApiException::notFound('Admin user not found or does not exist.');
        }

        if ($actor instanceof AdminUser && $actor->getUuid() === $admin->getUuid()) {
            throw ApiException::conflict('You cannot delete your own admin account');
        }

        if ($admin->isDeleted()) {
            throw ApiException::conflict('Admin user is already deleted.');
        }

        $oldEmail = (string) $admin->getEmail();

        if ($oldEmail !== '') {
            $mailUserRow = $this->mailUserGateway->findByEmail($oldEmail);
            if ($mailUserRow !== null) {
                $this->mailUserGateway->setActive((int) $mailUserRow['id'], false);
            }
        }

        $admin->anonymize();
        $admin->setActive(false);

        $this->entityManager->flush();
    }
}
?>