<?php

namespace App\Security;

use App\Http\Error\{
    ApiErrorCode,
    ErrorResponseFactory
};
use App\Platform\Entity\{
    ApiToken,
    ApiTokenPermission,
    ApiTokenScope
};
use App\Platform\Repository\ApiTokenRepository;

use App\Service\RequestIdService;

use Doctrine\ORM\EntityManagerInterface;
use ReturnTypeWillChange;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiTokenHasher $tokenHasher,
        private readonly ApiTokenRepository $apiTokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ErrorResponseFactory $errorFactory,
        private readonly RequestIdService $requestIdService,
    ) {}

    public function supports(Request $request): ?bool
    {
        if (!str_starts_with($request->getPathInfo(), '/api/v1')) {
            return false;
        }

        $auth = (string) $request->headers->get('Authorization', '');
        if ($auth === '' || !str_starts_with($auth, 'Bearer ')) {
            return false;
        }

        $token = trim(substr($auth, 7));

        return str_starts_with($token, 'zmt_');
    }

    public function authenticate(Request $request): Passport
    {
        $auth = (string) $request->headers->get('Authorization', '');
        $plainToken = trim(substr($auth, 7));

        if ($plainToken === '') {
            throw new CustomUserMessageAuthenticationException('Authentication required.');
        }

        $tokenHash = $this->tokenHasher->hash($plainToken);

        /** @var ApiToken|null $apiToken */
        $apiToken = $this->apiTokenRepository->findOneByTokenHash($tokenHash);

        if (!$apiToken || !$apiToken->isActive()) {
            throw new CustomUserMessageAuthenticationException('Invalid token.');
        }

        $permissionRows = $this->entityManager->getRepository(ApiTokenPermission::class)
            ->findBy(['token' => $apiToken]);

        $scopeRows = $this->entityManager->getRepository(ApiTokenScope::class)
            ->findBy(['token' => $apiToken]);

        $permissions = array_values(array_map(
            static fn (ApiTokenPermission $permission) => $permission->getPermission(),
            $permissionRows
        ));

        $scopedDomainIds = array_values(array_map(
            static fn (ApiTokenScope $scope) => $scope->getDomainId(),
            $scopeRows
        ));

        $apiToken->touchLastUsedAt();
        $this->entityManager->flush();

        $user = New ApiTokenUser(
            tokenUuid: $apiToken->getUuid(),
            permissions: $permissions,
            scopedDomainIds: $scopedDomainIds
        );

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), static fn () => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $requestId = $this->requestIdService->get();

        return $this->errorFactory->create(
            code: ApiErrorCode::AUTH_INVALID,
            status: 401,
            message: $exception->getMessage() ?: 'Invalid token.',
            details: null,
            requestId: $requestId
        );
    }

}

?>