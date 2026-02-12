<?php

namespace App\Service\MailAlias;

use App\Platform\Repository\MailAliasLinkRepository;
use App\Http\Error\ApiException;

use Doctrine\ORM\EntityManagerInterface;

final class DeleteMailAliasAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailAliasLinkRepository $mailAliasLinkRepository,
        private readonly MailAliasGatewayService $mailAliasGateway,
    ) {}

    public function delete(string $aliasUuid): void
    {
        $link = $this->mailAliasLinkRepository->findOneByUuid($aliasUuid);
        if (!$link) {
            throw ApiException::notFound('Alias not found or does not exist.');
        }

        $mailAliasId = $link->getMailAliasId();

        $affected = $this->mailAliasGateway->deleteById($mailAliasId);
        if ($affected === 0) {
            throw ApiException::notFound('Alias not found or does not exist.');
        }

        $this->entityManager->remove($link);
        $this->entityManager->flush();
    }
}

?>