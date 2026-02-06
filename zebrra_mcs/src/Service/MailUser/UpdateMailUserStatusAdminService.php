<?php

namespace App\Service\MailUser;

use App\DTO\MailUser\MailUserStatusDTO;
use App\Enum\MailUserStatusAction;
use App\Http\Error\ApiException;
use App\Service\ValidationService;

final class UpdateMailUserStatusAdminService
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly MailUserLinkResolver $resolver,
        private readonly MailUserGatewayService $mailUserGateway
    ) {}

    public function update(string $uuid, MailUserStatusDTO $mailUserStatusDTO): void
    {
        $this->validationService->validate($mailUserStatusDTO, ['user:status']);

        $link = $this->resolver->resolveLinkByUuid($uuid);

        $row = $this->mailUserGateway->findById($link->getMailUserId());
        if ($row === null) {
            throw ApiException::notFound('User not found or does not exist.');
        }

        $currentActive = ((int) $row['active']) === 1;

        $action = $mailUserStatusDTO->toEnum();
        $targetActive = match ($action) {
            MailUserStatusAction::ENABLE => true,
            MailUserStatusAction::DISABLE => false,
        };

        if ($currentActive === $targetActive) {
            throw ApiException::conflict(
                $targetActive ? 'User is already enabled' : 'User is already disabled.'
            );
        }

        $this->mailUserGateway->setActive($link->getMailUserId(), $targetActive);
    }
}

?>