<?php

namespace App\Controller;

use App\DTO\MailUser\MailUserCreateDTO;
use App\Http\Error\ApiException;
use App\Service\Access\AccessControlService;
use App\Service\MailUser\CreateMailUserAdminService;
use App\Service\MailUser\ReadMailUserAdminService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/admin/users', name: 'app_api_v1_admin_users_')]
final class AdminMailUserController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly AccessControlService $accessControl,
    ) {}

    #[Route('', name: 'create', methods: 'POST')]
    public function create(
        Request $request,
        CreateMailUserAdminService $createMailUserService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $mailUserCreateDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailUserCreateDTO::class,
                format: 'json',
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $result = $createMailUserService->create($mailUserCreateDTO);

        $responseData = $this->serializer->serialize(
            data: ['data' => $result],
            format: 'json',
            context: ['groups' => ['user:read', 'user:create']]
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
        ReadMailUserAdminService $readMailUserService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $result = $readMailUserService->read($uuid);

        $responseData = $this->serializer->serialize(
            data: ['data' => $result],
            format: 'json',
            context: ['groups' => ['user:read']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

}


?>