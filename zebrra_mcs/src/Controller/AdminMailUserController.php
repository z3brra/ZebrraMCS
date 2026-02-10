<?php

namespace App\Controller;

use App\DTO\MailUser\MailUserCreateDTO;
use App\DTO\MailUser\MailUserPasswordChangeDTO;
use App\DTO\MailUser\MailUserSearchQueryDTO;
use App\DTO\MailUser\MailUserStatusDTO;
use App\Http\Error\ApiException;
use App\Service\Access\AccessControlService;
use App\Service\MailUser\ChangeMailUserPasswordAdminService;
use App\Service\MailUser\CreateMailUserAdminService;
use App\Service\MailUser\ListMailUserAdminService;
use App\Service\MailUser\ReadMailUserAdminService;
use App\Service\MailUser\SearchMailUserAdminService;
use App\Service\MailUser\UpdateMailUserStatusAdminService;
use App\Service\RequestHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Json;

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

    #[Route('/{uuid}/status', name: 'status', methods: 'PATCH')]
    public function status(
        string $uuid,
        Request $request,
        UpdateMailUserStatusAdminService $updateMailUserStatusService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $updateMailUserStatusDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailUserStatusDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $updateMailUserStatusService->update($uuid, $updateMailUserStatusDTO);

        return new JsonResponse(
            data: null,
            status: JsonResponse::HTTP_NO_CONTENT,
        );
    }

    #[Route('/{uuid}/password', name: 'change_password', methods: 'PATCH')]
    public function changePassword(
        string $uuid,
        Request $request,
        ChangeMailUserPasswordAdminService $changePasswordService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $changePasswordDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailUserPasswordChangeDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format');
        }

        $changePasswordService->change($uuid, $changePasswordDTO);

        return new JsonResponse(
            data: null,
            status: JsonResponse::HTTP_NO_CONTENT
        );
    }

    #[Route('/search', name: 'search', methods: 'POST')]
    public function search(
        Request $request,
        SearchMailUserAdminService $searchMailUserService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            /** @var MailUserSearchQueryDTO $searchQueryDTO */
            $searchQueryDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailUserSearchQueryDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $searchQueryDTO->page = RequestHelper::readPage($request);
        $searchQueryDTO->limit = RequestHelper::readLimit($request);

        $result = $searchMailUserService->search($searchQueryDTO);

        $responseData = $this->serializer->serialize(
            data: $result,
            format: 'json',
            context: ['groups' => ['user:list']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('', name: 'list', methods: 'GET')]
    public function list(
        Request $request,
        ListMailUserAdminService $listMailUserService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $page = RequestHelper::readPage($request);
        $limit = RequestHelper::readLimit($request);

        $readMailUserDTO = $listMailUserService->list($page, $limit);

        $responseData = $this->serializer->serialize(
            data: $readMailUserDTO,
            format: 'json',
            context: ['groups' => ['user:list']],
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

}


?>