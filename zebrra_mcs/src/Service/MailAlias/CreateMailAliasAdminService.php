<?php

namespace App\Service\MailAlias;

use App\Platform\Entity\MailAliasLink;
use App\DTO\MailAlias\{
    MailAliasCreateDTO,
    MailAliasCreatedRowDTO,
    MailAliasCreateResponseDTO,
};
use App\Http\Error\ApiException;
use App\Service\ValidationService;
use App\Service\MailUser\MailUserGatewayService;
use App\Service\Domain\MailDomainGatewayService;

use App\Audit\AdminMailAuditLogger;

use Doctrine\ORM\EntityManagerInterface;

final class CreateMailAliasAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidationService $validationService,
        private readonly MailAliasGatewayService $mailAliasGateway,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly AdminMailAuditLogger $audit,
    ) {}

    public function create(MailAliasCreateDTO $aliasCreateDTO): MailAliasCreateResponseDTO
    {
        $this->validationService->validate($aliasCreateDTO, ['alias:create']);

        $source = mb_strtolower(trim($aliasCreateDTO->sourceEmail));
        $sourceDomain = $this->extractDomain($source);

        if ($sourceDomain === null) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' => 'sourceEmail',
                        'message' => 'sourceEmail must be a valid email.',
                        'code' => null,
                    ]
                ]
            );
        }

        if (!$this->mailDomainGateway->existsByName($sourceDomain)) {
            throw ApiException::notFound('Source domain not found or does not exists.');
        }

        $destinations = [];
        foreach ($aliasCreateDTO->destinations as $destination) {
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
                        'property' => 'destinations',
                        'message' => 'destinations must contain at least 1 email',
                        'code' => null
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
                            'code' => null
                        ]
                    ]
                );
            }

            if (!$this->mailDomainGateway->existsByName($destDomain)) {
                throw ApiException::notFound('Destination domain not found or does not exist.');
            }
        }

        $created = [];
        foreach ($destinations as $destination) {
            if ($this->mailAliasGateway->exists($source, $destination)) {
                $this->audit->error(
                    action: 'mail_alias.create',
                    target: $this->audit->auditTargetMailAlias(
                        aliasUuid: null,
                        mailAliasId: null,
                        sourceEmail: $source,
                        destinationEmail: $destination
                    ),
                    message: 'Alias already exists for this source / destination.',
                    details: null
                );
                throw ApiException::conflict('Alias already exists for this source / destination.');
            }

            $existDest = $this->mailUserGateway->findByEmail($destination);
            if (!$existDest) {
                $this->audit->error(
                    action: 'mail_alias.create',
                    target: $this->audit->auditTargetMailAlias(
                        aliasUuid: null,
                        mailAliasId: null,
                        sourceEmail: $source,
                        destinationEmail: $destination
                    ),
                    message: 'Destination user not found or does not exist.',
                    details: null,
                );
                throw ApiException::notFound('Destination not found or does not exist.');
            }

            $mailAliasId = $this->mailAliasGateway->insert($source, $destination);

            $link = new MailAliasLink($mailAliasId, $source, $destination);
            $this->entityManager->persist($link);

            $created[] = new MailAliasCreatedRowDTO(
                uuid: $link->getUuid(),
                sourceEmail: $source,
                destinationEmail: $destination
            );
        }
        $this->entityManager->flush();

        $this->audit->success(
            action: 'mail_alias.create',
            target: [
                'type' => 'mail_alias_batch',
                'sourceEmail' => $source,
                'createdCount' => count($created),
            ],
            details: [
                'created' => array_map(
                    static fn (MailAliasCreatedRowDTO $row) => [
                        'aliasUuid' => $row->uuid,
                        'sourceEmail' => $row->sourceEmail,
                        'destinationEmail' => $row->destinationEmail,
                    ],
                    $created
                ),
            ]
        );

        return new MailAliasCreateResponseDTO($created);
    }

    private function extractDomain(string $email): ?string
    {
        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return null;
        }
        $domain = trim(substr($email, $atPos + 1));
        return $domain !== '' ? $domain : null;
    }
}

?>