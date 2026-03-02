<?php

namespace App\Controller;

use App\DTO\Admin\AdminCreateDTO;
use App\DTO\Admin\AdminStatusPatchDTO;
use App\Service\SuperAdmin\CreateAdminUserService;

use App\Service\Access\AccessControlService;

use App\Http\Error\ApiException;
use App\Service\SuperAdmin\PatchAdminUserStatusService;
use App\Service\SuperAdmin\ReadAdminUserService;
use App\Service\SuperAdmin\ResetAdminUserPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/admin/super-admin', name: 'app_api_v1_admin_super-admin')]
final class SuperAdminController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly AccessControlService $accessControl,
    ) {}

    #[Route('', methods: 'POST')]
    public function create(
        Request $request,
        CreateAdminUserService $createAdminService,
    ): JsonResponse {
        $this->accessControl->denyUnlessSuperAdmin();

        try {
            /** @var AdminCreateDTO $createAdminDTO */
            $createAdminDTO = $this->serializer->deserialize(
                data: $request->getcontent(),
                type: AdminCreateDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $readAdminDTO = $createAdminService->create($createAdminDTO);

        $responseData = $this->serializer->serialize(
            data: ['data' => $readAdminDTO],
            format: 'json',
            context: ['groups' => ['admin:read']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_CREATED,
            json: true
        );
    }

    #[Route('/{uuid}/status', name: 'status', methods: 'PATCH')]
    public function patchStatus(
        string $uuid,
        Request $request,
        PatchAdminUserStatusService $patchAdminStatusService,
    ): JsonResponse {
        try {
            /**
             * @var AdminStatusPatchDTO $patchAdminStatusDTO
             */
            $patchAdminStatusDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: AdminStatusPatchDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $patchAdminStatusService->patch($uuid, $patchAdminStatusDTO);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/reset-password', name: 'reset-password', methods: 'POST')]
    public function resetPassword(
        string $uuid,
        ResetAdminUserPasswordService $resetAdminPasswordService
    ): JsonResponse {
        $readAdminDTO = $resetAdminPasswordService->reset($uuid);
        
        $responseData = $this->serializer->serialize(
            data: ['data' => $readAdminDTO],
            format: 'json',
            context: ['groups' => ['admin:secret']],
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('/{uuid}', name: 'read', methods: 'GET')]
    public function read(
        string $uuid,
        ReadAdminUserService $readAdminService,
    ): JsonResponse {
        $this->accessControl->denyUnlessSuperAdmin();

        $readAdminDTO = $readAdminService->read($uuid);

        $responseData = $this->serializer->serialize(
            data: ['data' => $readAdminDTO],
            format: 'json',
            context: ['groups' => ['admin:read']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }
}

?>