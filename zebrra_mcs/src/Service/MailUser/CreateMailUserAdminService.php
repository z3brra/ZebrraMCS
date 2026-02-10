<?php

namespace App\Service\MailUser;

use App\Platform\Entity\MailUserLink;
use App\DTO\MailUser\{
    MailUserCreateDTO,
    MailUserReadDTO,
};
use App\Platform\Repository\MailUserLinkRepository;
use App\Http\Error\ApiException;
use App\Service\Domain\MailDomainGatewayService;
use App\Service\Domain\MailDomainLinkResolver;
use App\Service\ValidationService;

use Doctrine\ORM\EntityManagerInterface;

final class CreateMailUserAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidationService $validationService,

        private readonly MailDomainLinkResolver $domainResolver,
        private readonly MailDomainGatewayService $mailDomainGateway,

        private readonly MailUserLinkRepository $mailUserLinkRepository,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailPasswordHasherService $passwordHasher,
    ) {}

    public function create(MailUserCreateDTO $mailUserCreateDTO): MailUserReadDTO
    {
        $this->validationService->validate($mailUserCreateDTO, ['user:create']);

        $mailDomainId = $this->domainResolver->resolveMailDomainId($mailUserCreateDTO->domainUuid);

        $domainRow = $this->mailDomainGateway->findById($mailDomainId);
        if ($domainRow === null) {
            throw ApiException::notFound('Domain not found or does not exist.');
        }

        $expectedDomain = mb_strtolower((string) $domainRow['name']);

        $atPos = strpos($mailUserCreateDTO->email, '@');
        $emailDomain = $atPos === false ? '' : mb_strtolower(trim(substr($mailUserCreateDTO->email, $atPos + 1)));

        if ($emailDomain === '' || $emailDomain !== $expectedDomain) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' => 'email',
                        'message' => 'Email domain must match the selected domain',
                        'code' => null,
                    ],
                ],
            );
        }


        $existing = $this->mailUserGateway->findByEmail($mailUserCreateDTO->email);
        if ($existing) {
            $link = $this->mailUserLinkRepository->findOneByMailUserId((int) $existing['id']);
            if (!$link) {
                $link = new MailUserLink(
                    mailUserId: (int) $existing['id'],
                    mailDomainId: (int) $existing['domain_id'],
                    email: strtolower($mailUserCreateDTO->email),
                );
                $this->entityManager->persist($link);
                $this->entityManager->flush();
            }
            throw ApiException::conflict('User already exists.');
        }

        $hash = $this->passwordHasher->hashForDovecot($mailUserCreateDTO->plainPassword);

        $mailUserId = $this->mailUserGateway->create(
            mailDomainId: $mailDomainId,
            email: strtolower($mailUserCreateDTO->email),
            passwordHash: $hash,
            active: $mailUserCreateDTO->active
        );

        $link = new MailUserLink(
            mailUserId: $mailUserId,
            mailDomainId: $mailDomainId,
            email: strtolower($mailUserCreateDTO->email)
        );

        $this->entityManager->persist($link);
        $this->entityManager->flush();

        return new MailUserReadDTO(
            uuid: $link->getUuid(),
            email: strtolower($mailUserCreateDTO->email),
            domainUuid: $mailUserCreateDTO->domainUuid,
            active: $mailUserCreateDTO->active,
            plainPassword: $mailUserCreateDTO->plainPassword,
        );
    }
}


?>