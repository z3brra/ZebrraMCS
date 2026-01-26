<?php

namespace App\Controller;

use App\DTO\Token\TokenCreateDTO;
use App\Http\Error\ApiException;
use App\Service\Access\AccessControlService;
use App\Service\Token\TokenAdminService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('/api/v1/admin/tokens', name: 'app_api_v1_admin_tokens_')]
final class AdminTokenController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly AccessControlService $accessControl,
    ) {}

    #[Route('', name: 'create', methods: 'POST')]
    public function create(
        Request $request,
        TokenAdminService $tokenAdminService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $tokenCreateDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: TokenCreateDTO::class,
                format: 'json'
            );
        } catch (\Exception $e) {
            throw ApiException::badRequest('Invalid JSON format');
        }
        $tokenReadDTO = $tokenAdminService->create($tokenCreateDTO);

        $responseData = $this->serializer->serialize(
            data: $tokenReadDTO,
            format: 'json',
            context: ['groups' => ['token:secret']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_CREATED,
            json: true
        );
    }

    #[Route('/{uuid}', name: 'read', methods: 'GET')]
    public function read(
        string $uuid,
        TokenAdminService $tokenAdminService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $tokenReadDTO = $tokenAdminService->read($uuid);
        $responseData = $this->serializer->serialize(
            data: $tokenReadDTO,
            format: 'json',
            context: ['groups' => ['token:read']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('/{uuid}/revoke', name: 'revoke', methods: 'POST')]
    public function revoke(
        string $uuid,
        TokenAdminService $tokenAdminService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $tokenAdminService->revoke($uuid);

        return new JsonResponse(
            data: null,
            status: JsonResponse::HTTP_NO_CONTENT
        );
    }

    #[Route('/{uuid}/rotate', name: 'rotate', methods: ['POST'])]
    public function rotate(
        string $uuid,
        TokenAdminService $tokenAdminService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $tokenReadDTO = $tokenAdminService->rotate($uuid);

        $responseData = $this->serializer->serialize(
            data: $tokenReadDTO,
            format: 'json',
            context: ['groups' => ['token:secret']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }
}

?>