<?php

namespace App\Service\SuperAdmin;

use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\AdminStatusPatchDTO;
use App\Enum\AdminStatusAction;
use App\Service\MailUser\MailUserGatewayService;

use App\Service\ValidationService;

use App\Http\Error\ApiException;

use Doctrine\ORM\EntityManagerInterface;

final class PatchAdminUserStatusService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidationService $validationService,

        private readonly AdminUserRepository $adminUserRepository,
        private readonly MailUserGatewayService $mailUserGateway,
    ) {}

    public function patch(string $adminUuid, AdminStatusPatchDTO $patchAdminStatusDTO): void
    {
        $this->validationService->validate($patchAdminStatusDTO, ['admin:status']);

        $admin = $this->adminUserRepository->findOneByUuid($adminUuid);
        if (!$admin) {
            throw ApiException::notFound('Admin user not found or does not exist.');
        }

        $action = $patchAdminStatusDTO->toEnum();
        $targetActive = match($action) {
            AdminStatusAction::ENABLE => true,
            AdminStatusAction::DISABLE => false,
        };

        $currentActive = $admin->isActive();

        if ($currentActive === $targetActive) {
            throw ApiException::conflict(
                $targetActive ? 'Admin user is already enabled' : 'Admin user is already disabled.'
            );
        }

        $admin->setActive($targetActive);
        $this->entityManager->flush();

        $email = (string) $admin->getEmail();
        $mailUserRow = $this->mailUserGateway->findByEmail($email);

        if ($mailUserRow !== null) {
            $this->mailUserGateway->setActive((int) $mailUserRow['id'], $targetActive);
        }
    }
}

?>