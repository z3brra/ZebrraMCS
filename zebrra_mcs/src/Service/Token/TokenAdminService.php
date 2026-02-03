<?php

namespace App\Service\Token;

use App\DTO\Token\{
    TokenCreateDTO,
    TokenCreatedSecretDTO,
    TokenReadDTO
};
use App\Http\Error\ApiException;
use App\Platform\Entity\{
    ApiToken,
    AdminUser
};
use App\Platform\Repository\{
    ApiTokenRepository,
    MailDomainLinkRepository
};
use App\Security\ApiTokenHasher;
use App\Service\Access\AccessControlService;
use App\Service\ValidationService;

use App\Audit\AdminTokenAuditLogger;

use Doctrine\ORM\EntityManagerInterface;

use Ramsey\Uuid\Uuid;

final class TokenAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiTokenRepository $tokenRepository,
        private readonly MailDomainLinkRepository $mailLinkRepository,
        private readonly ApiTokenHasher $tokenHasher,
        private readonly AccessControlService $accessControl,
        private readonly ValidationService $validationService,

        private readonly AdminTokenAuditLogger $audit,
    ) {}

    public function create(TokenCreateDTO $tokenCreateDTO): array
    {
        /** @var AdminUser $admin */
        $admin = $this->accessControl->getActor();

        $this->validationService->validate($tokenCreateDTO, ['token:create']);

        $plainToken = 'zmt_' . Uuid::uuid7()->toString() . bin2hex(random_bytes(8));
        $tokenHash = $this->tokenHasher->hash($plainToken);

        $token = new ApiToken(
            name: $tokenCreateDTO->name,
            tokenHash: $tokenHash,
            createdByAdmin: $admin
        );
        $token->setExpiresAt($tokenCreateDTO->expiresAt);

        foreach ($tokenCreateDTO->permissions as $permission) {
            $token->addPermissionString((string) $permission);
        }

        $scopes = $this->validateAndNormalizeDomainScopes($tokenCreateDTO->scopedDomainUuids);
        foreach ($scopes as $domainUuid) {
            $token->addScopeUuid((string) $domainUuid);
        }

        $this->entityManager->persist($token);
        $this->entityManager->flush();
        $this->audit->success(
            action: 'token.create',
            token: $token
        );

        return [
            'data' => new TokenCreatedSecretDTO($token->getUuid(), $plainToken),
        ];
    }

    /**
     * @return list<TokenReadDTO>
     */
    public function read(string $uuid): array
    {
        $token = $this->tokenRepository->findOneByUuid($uuid);
        if (!$token) {
            throw ApiException::notFound('Token not found or does not exist.');
        }

        return [
            "data" => TokenReadDTO::fromEntity($token)
        ];
    }

    public function revoke(string $uuid): void
    {
        $token = $this->tokenRepository->findOneByUuid($uuid);
        if (!$token) {
            throw ApiException::notFound('Token not found or does not exist.');
        }

        if (!$token->isActive()) {
            $this->audit->error(
                action: 'token.revoke',
                token: $token,
                message: 'Token is not active.'
            );
            throw ApiException::conflict('Token is not active.');
        }

        $token->revoke();
        $this->entityManager->flush();
        $this->audit->success(
            action: 'token.revoke',
            token: $token,
        );
    }

    /**
     * @return list<TokenCreatedSecretDTO>
     */
    public function rotate(string $uuid): array
    {
        $admin = $this->accessControl->getActor();
        $oldToken = $this->tokenRepository->findOneByUuid($uuid);
        if (!$oldToken) {
            throw ApiException::notFound('Token not found or does not exist.');
        }

        if (!$oldToken->isActive()) {
            $this->audit->error(
                action: 'token.rotate',
                token: $oldToken,
                message: 'Token is not active.'
            );
            throw ApiException::conflict('Token is not active.');
        }

        $plainToken = 'zmt_' . Uuid::uuid7()->toString() . bin2hex(random_bytes(8));
        $tokenHash = $this->tokenHasher->hash($plainToken);

        $newToken = new ApiToken(
            name: $oldToken->getName(),
            tokenHash: $tokenHash,
            createdByAdmin: $admin
        );
        $newToken->setExpiresAt($oldToken->getExpiresAt());

        foreach ($oldToken->getPermissionStrings() as $permission) {
            $newToken->addPermissionString((string) $permission);
        }

        foreach ($oldToken->getScopedDomainUuids() as $domainUuid) {
            $newToken->addScopeUuid((string) $domainUuid);
        }

        $this->entityManager->persist($newToken);

        $oldToken->revoke();
        $oldToken->setReplacedByToken($newToken);

        $this->entityManager->flush();

        $this->audit->success(
            action: 'token.rotate',
            token: $newToken,
            details: [
                'oldTokenUuid' => $oldToken->getUuid(),
                'newTokenUuid' => $newToken->getUuid(),
                'copiedPermissionsCount' => count($newToken->getPermissionStrings()),
                'copiedScopesCount' => count($newToken->getScopedDomainUuids()),
            ],
        );

        return [
            "data" => new TokenCreatedSecretDTO($newToken->getUuid(), $plainToken)
        ];
    }

    /**
     * @param list<string>|null $domainUuids
     * @return list<string>
     */
    private function validateAndNormalizeDomainScopes(?array $domainUuids): array
    {
        if (!$domainUuids) {
            return [];
        }

        $clean = [];
        foreach ($domainUuids as $uuid) {
            if (!is_string($uuid)) {
                continue;
            }
            $uuid = trim($uuid);
            if ($uuid === '') {
                continue;
            }
            $clean[] = $uuid;
        }

        $clean = array_values(array_unique($clean));

        $invalid = [];
        foreach ($clean as $uuid) {
            if (!Uuid::isValid($uuid)) {
                $invalid[] = $uuid;
            }
        }
        if ($invalid !== []) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => array_map(
                        static fn(string $uuid) => [
                            'property' => 'scopedDomainUuids',
                            'message' => 'Invalid UUID format.',
                            'code' => null,
                            'value' => $uuid
                        ],
                        $invalid
                    ),
                ],
            );
        }

        $missing = [];
        foreach ($clean as $uuid) {
            if (!$this->mailLinkRepository->existsByUuid($uuid)) {
                $missing[] = $uuid;
            }
        }

        if ($missing !== []) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => array_map(
                        static fn(string $uuid) => [
                            'property' => 'scopedDomainUuids',
                            'message' => 'Domain does not exist.',
                            'code' => null,
                            'value' => $uuid,
                        ],
                        $missing
                    ),
                ],
            );
        }

        sort($clean);
        return $clean;
    }
}

?>