<?php

namespace App\Controller;

use App\DTO\MailUser\MailUserCreateDTO;
use App\DTO\MailUser\MailUserPasswordChangeDTO;
use App\DTO\MailUser\MailUserStatusDTO;
use App\Http\Error\ApiException;
use App\Platform\Enum\Permission;
use App\Service\MailUser\Token\ReadMailUserTokenService;

use App\Service\Access\AccessControlService;
use App\Service\MailUser\Token\ChangeMailUserPasswordTokenService;
use App\Service\MailUser\Token\CreateMailUserTokenService;
use App\Service\MailUser\Token\UpdateMailUserStatusTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/token/users', name: 'app_api_v1_token_users_')]
final class TokenMailUserController extends AbstractController
{
    public function __construct(
        private readonly AccessControlService $accessControl,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('', name: 'create', methods: 'POST')]
    public function create(
        Request $request,
        CreateMailUserTokenService $createMailUserService,
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessPermission(Permission::USERS_CREATE);

        try {
            /** @var MailUserCreateDTO $createUserDTO */
            $createUserDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailUserCreateDTO::class,
                format: 'json',
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $readUserDTO = $createMailUserService->create($createUserDTO);

        $responseData = $this->serializer->serialize(
            data: ['data' => $readUserDTO],
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
        ReadMailUserTokenService $readMailUserService,
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessPermission(Permission::USERS_READ);

        $mailUserReadDTO = $readMailUserService->read($uuid);

        $responseData = $this->serializer->serialize(
            data: ['data' => $mailUserReadDTO],
            format: 'json',
            context: ['groups' => ['user:read']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('/{uuid}/status', name: 'status', methods: 'PATCH')]
    public function patchStatus(
        string $uuid,
        Request $request,
        UpdateMailUserStatusTokenService $updateStatusService,
    ): JsonResponse {
        try {
            /** @var MailUserStatusDTO $statusUserDTO */
            $statusUserDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailUserStatusDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $updateStatusService->update($uuid, $statusUserDTO);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/password', name: 'change_password', methods: 'PATCH')]
    public function patchPassword(
        string $uuid,
        Request $request,
        ChangeMailUserPasswordTokenService $changePasswordService,
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessPermission(Permission::USERS_UPDATE_PASSWORD);

        try {
            /** @var MailUserPasswordChangeDTO $changePasswordDTO */
            $changePasswordDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailUserPasswordChangeDTO::class,
                format: 'json',
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format');
        }

        $changePasswordService->change($uuid, $changePasswordDTO);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

?>