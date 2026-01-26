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
    ApiTokenPermission,
    ApiTokenScope,
    AdminUser
};
use App\Platform\Repository\{
    ApiTokenRepository,
};
use App\Security\ApiTokenHasher;
use App\Service\Access\AccessControlService;
use App\Service\ValidationService;

use Doctrine\ORM\EntityManagerInterface;

use Ramsey\Uuid\Uuid;

final class TokenAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ApiTokenRepository $tokenRepository,
        private readonly ApiTokenHasher $tokenHasher,
        private readonly AccessControlService $accessControl,
        private readonly ValidationService $validationService,
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
            $token->addPermission(new ApiTokenPermission($token, (string) $permission));
        }

        foreach ($tokenCreateDTO->scopedDomainIds as $domainId) {
            $token->addScope(new ApiTokenScope($token, (int) $domainId));
        }

        $this->entityManager->persist($token);
        $this->entityManager->flush();

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
        $token->revoke();
        $this->entityManager->flush();
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
            $newToken->addPermission(new ApiTokenPermission($newToken, $permission));
        }

        foreach ($oldToken->getScopedDomainIds() as $domainId) {
            $newToken->addScope(new ApiTokenScope($newToken, $domainId));
        }

        $this->entityManager->persist($newToken);

        $oldToken->revoke();
        $oldToken->setReplacedByToken($newToken);

        $this->entityManager->flush();

        return [
            "data" => new TokenCreatedSecretDTO($newToken->getUuid(), $plainToken)
        ];
    }
}

?>