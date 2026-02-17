<?php

namespace App\Service\MailUser\Token;

use App\DTO\MailUser\MailUserReadDTO;
use App\DTO\MailAlias\MailAliasReadDTO;

use App\Platform\Entity\MailAliasLink;
use App\Platform\Repository\MailAliasLinkRepository;

use App\Http\Error\ApiException;
use App\Platform\Enum\Permission;

use App\Service\Access\AccessControlService;
use App\Service\Domain\MailDomainLinkResolver;
use App\Service\MailAlias\MailAliasGatewayService;
use App\Service\MailUser\MailUserGatewayService;
use App\Service\MailUser\MailUserLinkResolver;

use Doctrine\ORM\EntityManagerInterface;

final class ReadMailUserTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessControlService $accessControl,
        private readonly MailUserLinkResolver $mailUserResolver,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailDomainLinkResolver $domainResolver,
        private readonly MailAliasGatewayService $mailAliasGateway,
        private readonly MailAliasLinkRepository $mailAliasLinkRepository,
    ) {}

    public function read(string $userUuid): MailUserReadDTO {
        $link = $this->mailUserResolver->resolveLinkByUuid($userUuid);

        $row = $this->mailUserGateway->findById($link->getMailUserId());
        if ($row === null) {
            throw ApiException::notFound('User not found or does not exist.');
        }

        $email = mb_strtolower(trim((string) $row['email']));
        $domainUuid = $this->domainResolver->resolveMailDomainUuid($link->getMailDomainId());
        $this->accessControl->denyUnlessDomainScopeAllowed($domainUuid);

        $aliasRows = $this->mailAliasGateway->findByDestinationEmail($email);

        $aliasIds = [];
        foreach ($aliasRows as $alias) {
            $aliasIds[] = (int) $alias['id'];
        }

        $uuidMap = [];
        if ($aliasIds !== []) {
            $uuidMap = $this->mailAliasLinkRepository->mapUuidsByMailAliasIds($aliasIds);
            $createdAny = false;

            foreach ($aliasRows as $alias) {
                $mailAliasId = (int) $alias['id'];

                if (isset($uuidMap[$mailAliasId])) {
                    continue;
                }

                $sourceEmail = mb_strtolower(trim((string) $alias['source']));
                $destinationEmail = mb_strtolower(trim((string) $alias['destination']));

                $aliasLink = new MailAliasLink(
                    mailAliasId: $mailAliasId,
                    sourceEmail: $sourceEmail,
                    destinationEmail: $destinationEmail,
                );

                $this->entityManager->persist($aliasLink);
                $createdAny = true;
            }

            if ($createdAny) {
                $this->entityManager->flush();
                $uuidMap = $this->mailAliasLinkRepository->mapUuidsByMailAliasIds($aliasIds);
            }
        }

        $aliases = [];
        foreach ($aliasRows as $alias) {
            $id = (int) $alias['id'];
            if (!isset($uuidMap[$id])) {
                continue;
            }
            $aliases[] = new MailAliasReadDTO(
                uuid: (string) $uuidMap[$id],
                sourceEmail: (string) $alias['source'],
                destinationEmail: (string) $alias['destination'],
            );
        }

        return new MailUserReadDTO(
            uuid: $link->getUuid(),
            email: $email,
            domainUuid: $domainUuid,
            active: ((int) $row['active']) === 1,
            plainPassword: null,
            aliases: $aliases,
        );
    }
}

?>