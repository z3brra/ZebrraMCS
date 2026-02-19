<?php

namespace App\Service\MailUser\Token;

use App\DTO\MailUser\MailUserPasswordChangeDTO;
use App\Http\Error\ApiException;
use App\Platform\Enum\Permission;
use App\Service\Domain\MailDomainLinkResolver;
use App\Service\MailUser\{
    MailPasswordHasherService,
    MailUserGatewayService,
    MailUserLinkResolver,
};
use App\Service\Access\AccessControlService;
use App\Service\ValidationService;

final class ChangeMailUserPasswordTokenService
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly AccessControlService $accessControl,

        private readonly MailUserLinkResolver $mailUserResolver,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailPasswordHasherService $passwordHasher,
        private readonly MailDomainLinkResolver $domainResolver,
    ) {}

    public function change(string $userUuid, MailUserPasswordChangeDTO $changePasswordDTO): void
    {
        $this->validationService->validate($changePasswordDTO, ['user:password']);

        if ($changePasswordDTO->oldPassword === $changePasswordDTO->newPassword) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' => 'newPasword',
                        'message' => 'New password must be different from old password',
                        'code' => null,
                    ],
                ],
            );
        }

        $link = $this->mailUserResolver->resolveLinkByUuid($userUuid);
        $mailUserId = $link->getMailUserId();

        $domainUuid = $this->domainResolver->resolveMailDomainUuid($link->getMailDomainId());
        $this->accessControl->denyUnlessDomainScopeAllowed($domainUuid);

        $storedHash = $this->mailUserGateway->getPasswordHashById($mailUserId);
        if ($storedHash === null) {
            throw ApiException::notFound('User not found or does not exist.');
        }

        $verifyHash = $storedHash;
        if (str_starts_with($verifyHash, '{BLF-CRYPT}')) {
            $verifyHash = substr($verifyHash, strlen('{BLF-CRYPT}'));
        }

        if (!password_verify($changePasswordDTO->oldPassword, $verifyHash)) {
            throw ApiException::authInvalid('Invalid credentials.');
        }

        $newHash = $this->passwordHasher->hashForDovecot($changePasswordDTO->newPassword);
        $this->mailUserGateway->updatePasswordHash($mailUserId, $newHash);
    }
}

?>