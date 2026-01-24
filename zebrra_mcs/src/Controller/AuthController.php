<?php

namespace App\Controller;

use App\Service\Auth\AdminMeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
}


?>
