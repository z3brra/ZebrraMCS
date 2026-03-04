<?php

namespace App\Service\Auth;

use App\Platform\Entity\{
    AdminUser,
    AdminRefreshToken
};
use App\Platform\Repository\AdminRefreshTokenRepository;
use App\Security\RefreshTokenHasher;
use App\Http\Error\ApiException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\RequestStack;

final class AdminRefreshTokenService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminRefreshTokenRepository $adminRefreshTokenRepository,
        private readonly RefreshTokenHasher $refreshTokenHasher,
        private readonly RefreshTokenConfig $refreshTokenConfig,
        private readonly RequestStack $requestStack
    ) {}

    public function issue(AdminUser $admin): string
    {
        $plain = $this->generatePlainToken();
        $hash = $this->refreshTokenHasher->hashRefresh($plain);

        $expiresAt = (new DateTimeImmutable())->modify('+' . $this->refreshTokenConfig->ttlSeconds . ' seconds');

        $request = $this->requestStack->getCurrentRequest();

        $entity = new AdminRefreshToken(
            adminUser: $admin,
            tokenHash: $hash,
            expiresAt: $expiresAt,
            ip: $request?->getClientIp(),
            userAgent: $request?->headers->get('User-Agent')
        );

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $plain;
    }

    public function rotate(string $presentedRefreshToken): string
    {
        $presentedRefreshToken = trim($presentedRefreshToken);
        if ($presentedRefreshToken === '') {
            throw ApiException::authRequired('Refresh token required.');
        }

        $hash = $this->refreshTokenHasher->hashRefresh($presentedRefreshToken);

        $stored = $this->adminRefreshTokenRepository->findActiveByHash($hash);
        if (!$stored) {
            throw ApiException::authInvalid('Invalid refresh token.');
        }

        if (!$stored->isActive()) {
            throw ApiException::authInvalid('Refresh token expired or revoked');
        }

        $stored->revoke();

        $admin = $stored->getAdminUser();
        $newPlain = $this->generatePlainToken();
        $newHash = $this->refreshTokenHasher->hashRefresh($newPlain);

        $expiresAt = (new DateTimeImmutable())->modify('+' . $this->refreshTokenConfig->ttlSeconds . ' seconds');

        $request = $this->requestStack->getCurrentRequest();

        $newEntity = new AdminRefreshToken(
            adminUser: $admin,
            tokenHash: $newHash,
            expiresAt: $expiresAt,
            ip: $request?->getClientIp(),
            userAgent: $request?->headers->get('User-Agent')
        );

        $this->entityManager->persist($newEntity);
        $this->entityManager->flush();

        return $newPlain;
    }

    public function rotateWithAdmin(string $presentedRefreshToken): array
    {
        $presentedRefreshToken = trim($presentedRefreshToken);
        if ($presentedRefreshToken === '') {
            throw ApiException::authRequired('Refresh token required.');
        }

        $hash = $this->refreshTokenHasher->hashRefresh($presentedRefreshToken);

        $stored = $this->adminRefreshTokenRepository->findActiveByHash($hash);
        if (!$stored) {
            throw ApiException::authInvalid('Invalid refresh token.');
        }

        if (!$stored->isActive()) {
            throw ApiException::authInvalid('Refresh token expired or revoked.');
        }

        $stored->revoke();

        $admin = $stored->getAdminUser();

        $newPlain = $this->generatePlainToken();
        $newHash = $this->refreshTokenHasher->hashRefresh($newPlain);

        $expiresAt = (new \DateTimeImmutable())->modify('+' . $this->refreshTokenConfig->ttlSeconds . ' seconds');

        $request = $this->requestStack->getCurrentRequest();

        $newEntity = new AdminRefreshToken(
            adminUser: $admin,
            tokenHash: $newHash,
            expiresAt: $expiresAt,
            ip: $request?->getClientIp(),
            userAgent: $request?->headers->get('User-Agent'),
        );

        $this->entityManager->persist($newEntity);
        $this->entityManager->flush();

        return [
            'admin' => $admin,
            'refreshToken' => $newPlain,
        ];
    }

    public function revoke(string $presentedRefreshToken): void
    {
        $presentedRefreshToken = trim($presentedRefreshToken);
        if ($presentedRefreshToken === '') {
            return;
        }

        $hash = $this->refreshTokenHasher->hashRefresh($presentedRefreshToken);

        $stored = $this->adminRefreshTokenRepository->findActiveByHash($hash);
        if (!$stored) {
            return;
        }

        $stored->revoke();
        $this->entityManager->flush();
    }

    public function revokeAllForAdmin(AdminUser $admin): void
    {
        $tokens = $this->adminRefreshTokenRepository->findActiveByAdminId($admin->getId());
        if ($tokens === []) {
            return;
        }

        foreach ($tokens as $token) {
            $token->revoke();
        }

        $this->entityManager->flush();
    }

    private function generatePlainToken(): string
    {
        return 'zrt_' . bin2hex(random_bytes(32));
    }
}


?>