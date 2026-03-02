<?php

namespace App\Service\SuperAdmin;

use App\Platform\Entity\AdminUser;
use App\Platform\Repository\AdminUserRepository;
use App\DTO\Admin\{
    AdminCreateDTO,
    AdminReadDTO
};

use App\Service\Domain\MailDomainGatewayService;
use App\Service\MailUser\{
    MailUserGatewayService,
    MailPasswordHasherService
};
use App\Platform\Entity\MailUserLink;

use App\Http\Error\ApiException;
use App\Service\ValidationService;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateAdminUserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidationService $validationService,

        private readonly AdminUserRepository $adminUserRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,

        private readonly MailDomainGatewayService $domainGateway,
        private readonly MailUserGatewayService $userGateway,
        private readonly MailPasswordHasherService $mailPasswordHasher,
    ) {}

    public function create(AdminCreateDTO $createAdminDTO): AdminReadDTO
    {
        $this->validationService->validate($createAdminDTO, ['admin:create']);

        $email = mb_strtolower(trim($createAdminDTO->email));

        if ($this->adminUserRepository->findOneByEmail($email)) {
            throw ApiException::conflict('Admin user already exists.');
        }

        $admin = new AdminUser();
        $admin->setEmail($email);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $createAdminDTO->plainPassword);
        $admin->setPassword($hashedPassword);
        // $admin->setRoles($createAdminDTO->roles ?: ['ROLE_ADMIN']);
        if ($createAdminDTO->roles !== []) {
            $admin->setRoles($createAdminDTO->roles);
        }
        $admin->setActive($createAdminDTO->active);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        if ($createAdminDTO->createMailUser === true) {
            $this->createMailUser($email, $createAdminDTO->plainPassword);
        }

        return new AdminReadDTO(
            uuid: $admin->getUuid(),
            email: $admin->getEmail(),
            roles: $admin->getRoles(),
            active: $admin->isActive(),
            createdAt: $admin->getCreatedAt(),
            updatedAt: $admin->getUpdatedAt()
        );
    }

    private function createMailUser(string $email, string $plainPassword): void
    {
        $domain = substr(strrchr($email, '@'), 1);
        if (!$domain) {
            throw ApiException::validation('Invalid email format.');
        }

        $domainRow = $this->domainGateway->findByName($domain);
        if (!$domainRow) {
            throw ApiException::notFound('Mail domain not found or does not exist.');
        }

        if ($this->userGateway->findByEmail($email)) {
            throw ApiException::conflict('Mail user already exists.');
        }

        $hash = $this->mailPasswordHasher->hashForDovecot($plainPassword);

        $mailUserId = $this->userGateway->create(
            mailDomainId: (int) $domainRow['id'],
            email: $email,
            passwordHash: $hash,
            active: true
        );

        $link = new MailUserLink(
            mailUserId: $mailUserId,
            mailDomainId: (int) $domainRow['id'],
            email: $email
        );

        $this->entityManager->persist($link);
        $this->entityManager->flush();
    }
}

?>