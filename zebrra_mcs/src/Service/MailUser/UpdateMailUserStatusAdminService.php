<?php

namespace App\Service\MailUser;

use App\DTO\MailUser\MailUserStatusDTO;
use App\Enum\MailUserStatusAction;
use App\Http\Error\ApiException;
use App\Service\ValidationService;
use App\Audit\AdminMailAuditLogger;

final class UpdateMailUserStatusAdminService
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly MailUserLinkResolver $resolver,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly AdminMailAuditLogger $audit,
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

        $actionName = $targetActive ? 'mail_user.enable' : 'mail_user.disable';

        if ($currentActive === $targetActive) {
            $this->audit->error(
                action: $actionName,
                target: $this->audit->auditTargetMailUser(
                    userUuid: $link->getUuid(),
                    mailUserId: $link->getMailUserId(),
                    email: $link->getEmail(),
                    domainUuid: null,
                    mailDomainId: $link->getMailDomainId(),
                ),
                message: $targetActive ? 'User already enabled.' : 'User already disabled.',
                details: [
                    'currentActive' => $currentActive,
                    'targetActive' => $targetActive,
                ],
            );

            throw ApiException::conflict(
                $targetActive ? 'User is already enabled' : 'User is already disabled.'
            );
        }

        $this->mailUserGateway->setActive($link->getMailUserId(), $targetActive);

        $this->audit->success(
            action: $actionName,
            target: $this->audit->auditTargetMailUser(
                userUuid: $link->getUuid(),
                mailUserId: $link->getMailUserId(),
                email: $link->getEmail(),
                domainUuid: null,
                mailDomainId: $link->getMailDomainId(),
            ),
            details: [
                'previousActive' => $currentActive,
                'newActive' => $targetActive,
            ]
        );
    }
}

?>