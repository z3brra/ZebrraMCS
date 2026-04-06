<?php

namespace App\Service\Domain;

use App\DTO\Domain\DomainOptionDTO;
use App\Platform\Entity\MailDomainLink;
use App\Platform\Repository\MailDomainLinkRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ListDomainOptionsAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailDomainGatewayService $domainGateway,
        private readonly MailDomainLinkRepository $domainLinkRepository,
    ) {}

    public function listOptions(): array
    {
        $rows = $this->domainGateway->findAllForSelect();

        if ($rows === []) {
            return [];
        }

        $mailIds = [];
        foreach ($rows as $row) {
            $mailIds[] = (int) $row['id'];
        }

        $uuidMap = $this->domainLinkRepository->mapUuidsByMailDomainIds($mailIds);

        $missingIds = [];
        foreach ($mailIds as $mailId) {
            if (!isset($uuidMap[$mailId])) {
                $missingIds[] = $mailId;
            }
        }

        if ($missingIds !== []) {
            foreach ($missingIds as $mailId) {
                $link = new MailDomainLink($mailId);
                $this->entityManager->persist($link);
            }

            $this->entityManager->flush();
            $uuidMap = $this->domainLinkRepository->mapUuidsByMailDomainIds($mailIds);
        }

        $options = [];
        foreach ($rows as $row) {
            $mailId = (int) $row['id'];
            $uuid = $uuidMap[$mailId] ?? null;

            if ($uuid === null) {
                continue;
            }

            $options[] = new DomainOptionDTO(
                uuid: $uuid,
                name: (string) $row['name']
            );
        }

        return $options;
    }
}


?>