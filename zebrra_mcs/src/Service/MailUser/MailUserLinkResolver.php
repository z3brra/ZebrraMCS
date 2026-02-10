<?php

namespace App\Service\MailUser;

use App\Http\Error\ApiException;
use App\Platform\Entity\MailUserLink;
use App\Platform\Repository\MailUserLinkRepository;

final class MailUserLinkResolver
{
    public function __construct(
        private readonly MailUserLinkRepository $mailUserLinkRepository
    ) {}

    public function resolveLinkByUuid(string $userUuid): MailUserLink
    {
        $link = $this->mailUserLinkRepository->findOneByUuid($userUuid);
        if (!$link) {
            throw ApiException::notFound("User not found or does not exist.");
        }
        return $link;
    }

    public function resolveMailUserId(string $userUuid): int
    {
        return $this->resolveLinkByUuid($userUuid)->getMailUserId();
    }

    public function resolveMailUserUuid(int $mailUserId): string
    {
        $link = $this->mailUserLinkRepository->findOneByMailUserId($mailUserId);
        if (!$link) {
            throw ApiException::notFound("User not found or does not exist.");
        }
        return $link->getUuid();
    }
}


?>