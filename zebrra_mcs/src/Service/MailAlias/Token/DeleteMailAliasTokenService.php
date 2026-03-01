<?php

namespace App\Service\MailAlias\Token;

use App\Platform\Repository\MailAliasLinkRepository;

use App\Service\Domain\MailDomainGatewayService;
use App\Service\Domain\MailDomainLinkResolver;
use App\Service\MailAlias\MailAliasGatewayService;

use App\Http\Error\ApiException;
use App\Service\Access\AccessControlService;

use Doctrine\ORM\EntityManagerInterface;

final class DeleteMailAliasTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessControlService $accessControl,

        private readonly MailAliasLinkRepository $aliasLinkRepository,
        private readonly MailAliasGatewayService $aliasGateway,

        private readonly MailDomainGatewayService $domainGateway,
        private readonly MailDomainLinkResolver $domainResolver,
    ) {}

    public function delete(string $aliasUuid): void
    {
        $link = $this->aliasLinkRepository->findOneByUuid($aliasUuid);
        if (!$link) {
            throw ApiException::notFound('Alias not found or does not exist.');
        }

        $sourceEmail = mb_strtolower(trim($link->getSourceEmail()));
        $sourceDomain = $this->extractDomain($sourceEmail);

        if ($sourceDomain === null) {
            throw ApiException::internal('Alias link is corrupted (invalid sourceEmail).');
        }

        $sourceDomainRow = $this->domainGateway->findByName($sourceDomain);
        if (!$sourceDomainRow) {
            throw ApiException::notFound('Source domain not found or does not exist.');
        }

        $sourceDomainUuid = $this->domainResolver->resolveMailDomainUuid((int) $sourceDomainRow['id']);
        $this->accessControl->denyUnlessDomainScopeAllowed($sourceDomainUuid);

        $mailAliasId = $link->getMailAliasId();

        $affected = $this->aliasGateway->deleteById($mailAliasId);
        if ($affected === 0) {
            throw ApiException::notFound('Alias not found or does not exist.');
        }

        $this->entityManager->remove($link);
        $this->entityManager->flush();
    }

    private function extractDomain(string $email): ?string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return null;
        }

        $domain = trim(substr($email, $at + 1));
        return $domain !== '' ? mb_strtolower($domain) : null;
    }
}

?>