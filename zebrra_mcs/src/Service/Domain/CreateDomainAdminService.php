<?php

namespace App\Service\Domain;

use App\Platform\Entity\MailDomainLink;
use App\Platform\Repository\MailDomainLinkRepository;
use App\DTO\Domain\{
    DomainCreateDTO,
    DomainReadDTO,
};
use App\Http\Error\ApiException;
use App\Service\ValidationService;

use Doctrine\ORM\EntityManagerInterface;

final class CreateDomainAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailDomainLinkRepository $domainLinkRepository,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly ValidationService $validationService,
    ) {}

    /**
     * @return array{data: DomainReadDTO}
     */
    public function create(DomainCreateDTO $domainCreateDTO): array
    {
        $this->validationService->validate($domainCreateDTO, ['domain:create']);

        $name = strtolower(trim($domainCreateDTO->name));
        $active = $domainCreateDTO->active ?? true;

        $existing = $this->mailDomainGateway->findByName($name);
        if ($existing) {
            $link = $this->domainLinkRepository->findOneByMailDomainId((int) $existing['id']);
            if (!$link) {
                $link = new MailDomainLink((int) $existing['id']);
                $this->entityManager->persist($link);
                $this->entityManager->flush();
            }

            throw ApiException::conflict(
                message: 'Domain already exists.',
                details: [
                    'domainUuid' => $link->getUuid(),
                    'name' => $name,
                ],
            );
        }

        $mailDomainId = $this->mailDomainGateway->insert($name, $active);

        $link = new MailDomainLink($mailDomainId);
        $this->entityManager->persist($link);
        $this->entityManager->flush();

        return [
            'data' => new DomainReadDTO(
                uuid: $link->getUuid(),
                name: $name,
                active: $active
            )
        ];
    }
}

?>