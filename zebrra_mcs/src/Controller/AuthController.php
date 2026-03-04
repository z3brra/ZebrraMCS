<?php

namespace App\Controller;

use App\Service\Auth\{
    AdminMeService,
    AdminRefreshTokenService,
    RefreshTokenCookieService,
};
use App\Http\Error\ApiException;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException
};

#[Route('/api/v1/auth', name: 'app_api_v1_auth_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    #[Route('/me', name: 'me', methods: 'GET')]
    public function me(
        AdminMeService $adminMeService
    ): JsonResponse {
        try {
            $adminReadDTO = $adminMeService->me();

            $responseData = $this->serializer->serialize(
                data: $adminReadDTO,
                format: 'json',
                context: ['groups' => ['admin:me']]
            );

            return new JsonResponse(
                data: $responseData,
                status: JsonResponse::HTTP_OK,
                json: true
            );

        } catch (AccessDeniedHttpException $e) {
            return new JsonResponse(
                data: [
                    "error" => [
                        "code" => "forbidden",
                        "message" => $e->getMessage(),
                        "details" => null
                    ],
                ],
                status: JsonResponse::HTTP_FORBIDDEN
            );
        }
    }

    #[Route('/logout', name: 'logout', methods: 'DELETE')]
    public function logout(
        Request $request,
        AdminRefreshTokenService $refreshTokenService,
        RefreshTokenCookieService $cookieService,
    ): JsonResponse {
        $refreshToken = (string) $request->cookies->get('refresh_token', '');

        if ($refreshToken === '') {
            $refreshToken = (string) $request->headers->get('X-REFRESH-TOKEN', '');
        }

        if (trim($refreshToken) !== '') {
            $refreshTokenService->revoke($refreshToken);
        }

        $response = new JsonResponse(
            data: [
                'data' => [
                    'status' => 'ok',
                ]
            ],
            status: JsonResponse::HTTP_OK,
        );

        $response->headers->setCookie($cookieService->clearCookie());

        return $response;
    }

    #[Route('/refresh', name: 'refresh', methods: 'POST')]
    public function refresh(
        Request $request,
        AdminRefreshTokenService $refreshTokenService,
        RefreshTokenCookieService $cookieService,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        $refreshToken = (string) $request->cookies->get('refresh_token', '');

        if ($refreshToken === '') {
            $refreshToken = (string) $request->headers->get('X-REFRESH-TOKEN', '');
        }

        if (trim($refreshToken) === '') {
            throw ApiException::authRequired('Refresh token required.');
        }

        $rotated = $refreshTokenService->rotateWithAdmin($refreshToken);

        $admin = $rotated['admin'];
        $newRefresh = $rotated['refreshToken'];

        $jwt = $jwtManager->create($admin);

        $response = new JsonResponse(
            data: [
                'data' => [
                    'token' => $jwt,
                    'tokenType' => 'Bearer',
                    'expiresIn' => 600,
                ],
            ],
            status: JsonResponse::HTTP_OK
        );

        $response->headers->setCookie($cookieService->createCookie($newRefresh));

        return $response;
    }
}


?>
