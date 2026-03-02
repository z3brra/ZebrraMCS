<?php

namespace App\Service\SuperAdmin;

use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\AdminPasswordResetResponseDTO;
use App\Service\MailUser\{
    MailUserGatewayService,
    MailPasswordHasherService
};

use App\Http\Error\ApiException;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ResetAdminUserPasswordService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUserRepository $adminUserRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,

        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailPasswordHasherService $mailPasswordHasher,
    ) {}

    public function reset(string $adminUuid): AdminPasswordResetResponseDTO
    {
        $admin = $this->adminUserRepository->findOneByUuid($adminUuid);
        if (!$admin) {
            throw ApiException::notFound('Admin user not found or does not exist.');
        }

        $email = (string) $admin->getEmail();
        if ($email === '') {
            throw ApiException::internal('Admin email is missing.');
        }

        $newPassword = $this->generatePassword(24);

        $adminHash = $this->passwordHasher->hashPassword($admin, $newPassword);
        $admin->setPassword($adminHash);

        $this->entityManager->flush();

        $mailUserRow = $this->mailUserGateway->findByEmail($email);
        if ($mailUserRow !== null) {
            $mailHash = $this->mailPasswordHasher->hashForDovecot($newPassword);
            $this->mailUserGateway->updatePasswordHash((int) $mailUserRow['id'], $mailHash);
        }

        return new AdminPasswordResetResponseDTO(
            adminUuid: $admin->getUuid(),
            email: $email,
            newPassword: $newPassword
        );
    }

    private function generatePassword(int $length): string
    {
        $raw = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
        return substr($raw, 0, $length);
    }
}

?>