<?php

namespace App\Service\MailUser;

use App\DTO\MailUser\MailUserPasswordChangeDTO;
use App\Http\Error\ApiException;
use App\Service\ValidationService;

final class ChangeMailUserPasswordAdminService
{
    public function __construct(
        private readonly MailUserLinkResolver  $resolver,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailPasswordHasherService $passwordHasher,

        private readonly ValidationService $validationService,
    ) {}

    public function change(string $uuid, MailUserPasswordChangeDTO $changePasswordDTO): void
    {
        $this->validationService->validate($changePasswordDTO, ['user:password']);

        if ($changePasswordDTO->oldPassword === $changePasswordDTO->newPassword) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' => 'newPassword',
                        'message' => 'New password must be different from old password',
                        'code' => null,
                    ],
                ],
            );
        }

        $link = $this->resolver->resolveLinkByUuid($uuid);

        $storedHash = $this->mailUserGateway->getPasswordHashById($link->getMailUserId());
        if ($storedHash ===  null) {
            throw ApiException::notFound('User not found or does not exists.');
        }

        $storedHashForVerify = $storedHash;
        if (str_starts_with($storedHash, '{BLF-CRYPT}')) {
            $storedHashForVerify = substr($storedHashForVerify, strlen('{BLF-CRYPT}'));
        }

        if (!password_verify($changePasswordDTO->oldPassword, $storedHashForVerify)) {
            throw ApiException::authInvalid('Invalid credentials.');
        }

        $newHash = $this->passwordHasher->hashForDovecot($changePasswordDTO->newPassword);
        $this->mailUserGateway->updatePasswordHash($link->getMailUserId(), $newHash);
    }
}

?>