<?php

namespace App\Service\MailUser\Token;


use App\DTO\MailUser\MailUserStatusDTO;
use App\Platform\Enum\Permission;
use App\Enum\MailUserStatusAction;

use App\Service\Domain\MailDomainLinkResolver;
use App\Service\MailUser\{MailUserGatewayService, MailUserLinkResolver};

use App\Service\ValidationService;
use App\Service\Access\AccessControlService;

use App\Http\Error\ApiException;

final class UpdateMailUserStatusTokenService
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly AccessControlService $accessControl,

        private readonly MailUserLinkResolver $mailUserResolver,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailDomainLinkResolver $domainResolver,
    ) {}

    public function update(string $userUuid, MailUserStatusDTO $statusUserDTO): void
    {
        $this->validationService->validate($statusUserDTO, ['user:status']);

        $action = $statusUserDTO->toEnum();

        if ($action === MailUserStatusAction::ENABLE) {
            $this->accessControl->denyUnlessPermission(Permission::USERS_ENABLE);
        } else {
            $this->accessControl->denyUnlessPermission(Permission::USERS_DISABLE);
        }

        $link = $this->mailUserResolver->resolveLinkByUuid($userUuid);

        $domainUuid = $this->domainResolver->resolveMailDomainUuid($link->getMailDomainId());
        $this->accessControl->denyUnlessDomainScopeAllowed($domainUuid);

        $row = $this->mailUserGateway->findById($link->getMailUserId());
        if ($row === null) {
            throw ApiException::notFound('User not found or does not exist.');
        }

        $currentActive = ((int) $row['active']) === 1;
        $targetActive = match ($action) {
            MailUserStatusAction::ENABLE => true,
            MailUserStatusAction::DISABLE => false,
        };

        if ($currentActive === $targetActive) {
            throw ApiException::conflict($targetActive ? 'User is already enabled.' : 'User is already disabled.');
        }

        $this->mailUserGateway->setActive($link->getMailUserId(), $targetActive);
    }
}


?>