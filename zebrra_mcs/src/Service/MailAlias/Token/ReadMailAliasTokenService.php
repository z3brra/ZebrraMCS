<?php

namespace App\Service\MailAlias\Token;

use App\Platform\Repository\MailAliasLinkRepository;

use App\DTO\MailAlias\MailAliasReadDTO;
use App\Service\MailAlias\MailAliasGatewayService;
use App\Service\Domain\MailDomainGatewayService;
use App\Service\Domain\MailDomainLinkResolver;

use App\Service\Access\AccessControlService;

use App\Http\Error\ApiException;

final class ReadMailAliasTokenService
{
    public function __construct(
        private readonly AccessControlService $accessControl,
        private readonly MailAliasLinkRepository $aliasLinkRepository,
        private readonly MailAliasGatewayService $aliasGateway,
        private readonly MailDomainGatewayService $domainGateway,
        private readonly MailDomainLinkResolver $domainResolver,
    ) {}

    public function read(string $aliasUuid): MailAliasReadDTO
    {
        $link = $this->aliasLinkRepository->findOneByUuid($aliasUuid);
        if (!$link) {
            throw ApiException::notFound('Alias not found or does not exist.');
        }

        $aliasRow = $this->aliasGateway->findById($link->getMailAliasId());
        if ($aliasRow === null) {
            throw ApiException::notFound('Alias not found or does not exist.');
        }

        $sourceEmail = (string) $aliasRow['source'];
        $destinationEmail = (string) $aliasRow['destination'];

        $sourceDomain = $this->extractDomain($sourceEmail);
        if ($sourceDomain === null) {
            throw ApiException::internal('Invalid alias data.');
        }

        $domainRow = $this->domainGateway->findByName($sourceDomain);
        if ($domainRow === null) {
            throw ApiException::notFound('Source domain not found.');
        }

        $domainUuid = $this->domainResolver->resolveMailDomainUuid((int) $domainRow['id']);

        $this->accessControl->denyUnlessDomainScopeAllowed($domainUuid);

        return new MailAliasReadDTO(
            uuid: $link->getUuid(),
            sourceEmail: $sourceEmail,
            destinationEmail: $destinationEmail
        );
    }

    private function extractDomain(string $email): ?string
    {
        $at = strrpos($email, '@');
        if ($at === false) {
            return null;
        }

        $domain = substr($email, $at + 1);

        return $domain !== '' ? strtolower(trim($domain)) : null;
    }
}

?>