<?php

namespace App\Controller;

use App\DTO\Domain\DomainCreateDTO;
use App\DTO\Domain\DomainRenameDTO;
use App\DTO\Domain\DomainSearchQueryDTO;
use App\DTO\Domain\DomainStatusPatchDTO;
use App\Http\Error\ApiException;
use App\Service\Domain\CreateDomainAdminService;
use App\Service\Access\AccessControlService;
use App\Service\Domain\DeleteDomainAdminService;
use App\Service\Domain\ListDomainAdminService;
use App\Service\Domain\PatchDomainStatusAdminService;
use App\Service\Domain\ReadDomainAdminService;
use App\Service\Domain\RenameDomainAdminService;
use App\Service\Domain\SearchDomainAdminService;
use App\Service\RequestHelper;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/admin/domains', name: 'app_api_v1_admin_domains_')]
final class AdminDomainController extends AbstractController
{
    public function __construct(
        private readonly AccessControlService $accessControl,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('', name: 'create', methods: 'POST')]
    public function create(
        Request $request,
        CreateDomainAdminService $domainAdminService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $domainCreateDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: DomainCreateDTO::class,
                format: 'json',
                context: ['groups' => ['domain:create']]
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format');
        }

        $domainReadDTO = $domainAdminService->create($domainCreateDTO);

        $responseData = $this->serializer->serialize(
            data: $domainReadDTO,
            format: 'json',
            context: ['groups' => ['domain:read']],
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_CREATED,
            json: true
        );
    }

    #[Route('', name: 'list', methods: 'GET')]
    public function list(
        Request $request,
        ListDomainAdminService $listDomainService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $page = RequestHelper::readPage($request);
        $limit = RequestHelper::readLimit($request);

        $responseDTO = $listDomainService->list($page, $limit);

        $responseData = $this->serializer->serialize(
            data: $responseDTO,
            format: 'json',
            context: ['groups' => ['domain:list']]
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
        ReadDomainAdminService $readDomainService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $result = $readDomainService->read($uuid);

        $responseData = $this->serializer->serialize(
            data: $result,
            format: 'json',
            context: ['groups' => ['domain:read']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('/{uuid}', name: 'rename', methods: 'PATCH')]
    public function rename(
        string $uuid,
        Request $request,
        RenameDomainAdminService $renameDomainService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $renameDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: DomainRenameDTO::class,
                format: 'json',
            );
        } catch (\Throwable) {
            throw ApiException::badRequest("Invalid JSON format.");
        }

        $domainReadDTO = $renameDomainService->rename($uuid, $renameDTO);

        $responseData = $this->serializer->serialize(
            data: $domainReadDTO,
            format: 'json',
            context: ['groups' => ['domain:read']]
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
        PatchDomainStatusAdminService $patchDomainStatusAdminService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $statusPatchDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: DomainStatusPatchDTO::class,
                format: 'json',
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $patchDomainStatusAdminService->patch($uuid, $statusPatchDTO);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/search', name: 'search', methods: 'POST')]
    public function search(
        Request $request,
        SearchDomainAdminService $searchDomainService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            $queryDTO = $this->serializer->deserialize(
                data: $request->getcontent(),
                type: DomainSearchQueryDTO::class,
                format: 'json',
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON body.');
        }

        $queryDTO->page = RequestHelper::readPage($request);
        $queryDTO->limit = RequestHelper::readLimit($request);

        $result = $searchDomainService->search($queryDTO);

        $responseData = $this->serializer->serialize(
            data: $result,
            format: 'json',
            context: ['groups' => ['domain:list']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('/{uuid}', name: 'delete', methods: 'DELETE')]
    public function delete(
        string $uuid,
        DeleteDomainAdminService $deleteDomainService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();
        $deleteDomainService->hardDelete($uuid);
        return new JsonResponse(
            data: null,
            status: JsonResponse::HTTP_NO_CONTENT
        );
    }
}


?>