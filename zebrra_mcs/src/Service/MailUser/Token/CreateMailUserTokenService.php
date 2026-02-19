<?php

namespace App\Service\MailUser\Token;

use App\Platform\Entity\MailUserLink;
use App\Platform\Repository\MailUserLinkRepository;
use App\Platform\Enum\Permission;

use App\DTO\MailUser\{MailUserCreateDTO, MailUserReadDTO};
use App\Http\Error\ApiException;

use App\Service\Access\AccessControlService;
use App\Service\Domain\{MailDomainGatewayService, MailDomainLinkResolver};
use App\Service\MailUser\{MailPasswordHasherService, MailUserGatewayService};
use App\Service\ValidationService;

use Doctrine\ORM\EntityManagerInterface;

final class CreateMailUserTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidationService $validationService,

        private readonly AccessControlService $accessControl,

        private readonly MailDomainLinkResolver $domainResolver,
        private readonly MailDomainGatewayService $domainGateway,

        private readonly MailUserLinkRepository $mailUserLinkRepository,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailPasswordHasherService $passwordHasher,
    ) {}

    public function create(MailUserCreateDTO $createUserDTO): MailUserReadDTO
    {
        $this->validationService->validate($createUserDTO, ['user:create']);

        $email = mb_strtolower(trim($createUserDTO->email));
        $domainUuid = trim($createUserDTO->domainUuid);

        $this->accessControl->denyUnlessDomainScopeAllowed($domainUuid);

        $mailDomainId = $this->domainResolver->resolveMailDomainId($domainUuid);

        $domainRow = $this->domainGateway->findById($mailDomainId);
        if ($domainRow === null) {
            throw ApiException::notFound('Domain not found or does not exist.');
        }

        $expectedDomainName = mb_strtolower((string) $domainRow['name']);
        $emailDomain = $this->extractDomain($email);

        if ($emailDomain === null || $emailDomain !== $expectedDomainName) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' => 'email',
                        'message' => 'Email domain must match the selected domain.',
                        'code' => null,
                    ],
                ],
            );
        }

        $existing = $this->mailUserGateway->findByEmail($email);
        if ($existing) {
            $link = $this->mailUserLinkRepository->findOneByMailUserId((int) $existing['id']);
            if (!$link) {
                $link = new MailUserLink(
                    mailUserId: (int) $existing['id'],
                    mailDomainId: (int) $existing['domain_id'],
                    email: $email,
                );
                $this->entityManager->persist($link);
                $this->entityManager->flush();
            }

            throw ApiException::conflict(
                message: 'User already exists.',
                details: [
                    'userUuid' => $link->getUuid(),
                    'email' => $email
                ]
            );
        }

        $hash = $this->passwordHasher->hashForDovecot($createUserDTO->plainPassword);

        $mailUserId = $this->mailUserGateway->create(
            mailDomainId: $mailDomainId,
            email: $email,
            passwordHash: $hash,
            active: (bool) $createUserDTO->active,
        );

        $link = new MailUserLink(
            mailUserId: $mailUserId,
            mailDomainId: $mailDomainId,
            email: $email,
        );

        $this->entityManager->persist($link);
        $this->entityManager->flush();

        return new MailUserReadDTO(
            uuid: $link->getUuid(),
            email: $email,
            domainUuid: $domainUuid,
            active: (bool) $createUserDTO->active,
            plainPassword: $createUserDTO->plainPassword,
        );
    }

    private function extractDomain(string $email): ?string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return null;
        }

        $domain = trim(substr($email, $at + 1));
        return $domain !== '' ? $domain : null;
    }
}


?>