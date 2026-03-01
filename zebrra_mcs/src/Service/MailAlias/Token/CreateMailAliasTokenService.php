<?php

namespace App\Service\MailAlias\Token;

use App\Platform\Entity\MailAliasLink;
use App\Platform\Repository\MailAliasLinkRepository;
use App\DTO\MailAlias\{
    MailAliasCreateDTO,
    MailAliasCreatedRowDTO,
    MailAliasCreateResponseDTO,
};
use App\Platform\Enum\Permission;

use App\Service\MailAlias\MailAliasGatewayService;
use App\Service\Domain\MailDomainGatewayService;
use App\Service\Domain\MailDomainLinkResolver;
use App\Service\MailUser\MailUserGatewayService;

use App\Service\Access\AccessControlService;
use App\Service\ValidationService;
use App\Http\Error\ApiException;

use Doctrine\ORM\EntityManagerInterface;

final class CreateMailAliasTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidationService $validationService,
        private readonly AccessControlService $accessControl,

        private readonly MailAliasGatewayService $aliasGateway,
        private readonly MailUserGatewayService $userGateway,
        private readonly MailDomainGatewayService $domainGateway,

        private readonly MailDomainLinkResolver $domainResolver,
    ) {}

    public function create(MailAliasCreateDTO $createAliasDTO): MailAliasCreateResponseDTO
    {
        $this->validationService->validate($createAliasDTO, ['alias:create']);

        $source = mb_strtolower(trim($createAliasDTO->sourceEmail));
        $sourceDomain = $this->extractDomain($source);

        if ($sourceDomain === null) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' => 'sourceEmail',
                        'message' => 'sourceEmail must be a valid email.',
                        'code' => null,
                    ],
                ],
            );
        }

        $sourceDomainRow = $this->domainGateway->findByName($sourceDomain);
        if (!$sourceDomainRow) {
            throw ApiException::notFound('Source domain not found or does not exist.');
        }

        $sourceDomainUuid = $this->domainResolver->resolveMailDomainUuid((int) $sourceDomainRow['id']);
        $this->accessControl->denyUnlessDomainScopeAllowed($sourceDomainUuid);

        $destinations = [];
        foreach ($createAliasDTO->destinations as $destination) {
            $destination = mb_strtolower(trim((string) $destination));
            if ($destination === '') {
                continue;
            }
            if (!in_array($destination, $destinations, true)) {
                $destinations[] = $destination;
            }
        }

        if ($destinations === []) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' =>  'destinations',
                        'message' => 'destinations must contain at least 1 email.',
                        'code' => null,
                    ],
                ],
            );
        }

        foreach ($destinations as $destination) {
            $destDomain = $this->extractDomain($destination);

            if ($destDomain === null) {
                throw ApiException::validation(
                    message: 'Validation error',
                    details: [
                        'violations' => [
                            'property' => 'destinations',
                            'message' => 'All destination emails must be valid.',
                            'code' => null,
                        ],
                    ],
                );
            }

            $destDomainRow = $this->domainGateway->findByName($destDomain);
            if (!$destDomainRow) {
                throw ApiException::notFound('Destination domain not found or does not exist.');
            }

            $destDomainUuid = $this->domainResolver->resolveMailDomainUuid((int) $destDomainRow['id']);
            $this->accessControl->denyUnlessDomainScopeAllowed($destDomainUuid);

            $existsDest = $this->userGateway->findByEmail($destination);
            if (!$existsDest) {
                throw ApiException::notFound('Destination not found or does not exist.');
            }
        }

        $created = [];

        foreach ($destinations as $destination) {
            if ($this->aliasGateway->exists($source, $destination)) {
                throw ApiException::conflict('Alias already exists for this source / destination.');
            }

            $mailAliasId = $this->aliasGateway->insert($source, $destination);

            $link = new MailAliasLink(
                mailAliasId: $mailAliasId,
                sourceEmail: $source,
                destinationEmail: $destination
            );
            $this->entityManager->persist($link);

            $created[] = new MailAliasCreatedRowDTO(
                uuid: $link->getUuid(),
                sourceEmail: $source,
                destinationEmail: $destination
            );
        }

        $this->entityManager->flush();

        return new MailAliasCreateResponseDTO($created);
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