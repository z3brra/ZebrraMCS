<?php

namespace App\Controller;

use App\DTO\MailAlias\MailAliasCreateDTO;
use App\DTO\MailAlias\MailAliasSearchQueryDTO;
use App\Http\Error\ApiException;
use App\Service\Access\AccessControlService;
use App\Service\MailAlias\{
    CreateMailAliasAdminService,
    DeleteMailAliasAdminService,
    ListMailAliasAdminService,
    SearchMailAliasAdminService
};
use App\Service\RequestHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/admin/aliases', name: 'app_api_v1_admin_aliases_')]
final class AdminMailAliasController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly AccessControlService $accessControl,
    ) {}

    #[Route('', name: 'create', methods: 'POST')]
    public function create(
        Request $request,
        CreateMailAliasAdminService $createMailAliasService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        try {
            /** @var MailAliasCreateDTO $createAliasDTO */
            $createAliasDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailAliasCreateDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $readAliasDTO = $createMailAliasService->create($createAliasDTO);

        $responseData = $this->serializer->serialize(
            data: $readAliasDTO,
            format: 'json',
            context: ['groups' => ['alias:create']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_CREATED,
            json: true
        );
    }

    #[Route('/{uuid}', name: 'delete', methods: 'DELETE')]
    public function delete(
        string $uuid,
        DeleteMailAliasAdminService $deleteMailAliasService
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $deleteMailAliasService->delete($uuid);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('', name: 'list', methods: 'GET')]
    public function list(
        Request $request,
        ListMailAliasAdminService $listMailAliasService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $page = RequestHelper::readPage($request);
        $limit = RequestHelper::readLimit($request);

        $mailAliasListDTO = $listMailAliasService->list($page, $limit);

        $responseData = $this->serializer->serialize(
            data: $mailAliasListDTO,
            format: 'json',
            context: ['groups' => ['alias:list']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('/search', name: 'search', methods: 'POST')]
    public function search(
        Request $request,
        SearchMailAliasAdminService $searchMailAliasService,
    ): JsonResponse {
        $this->accessControl->denyUnlessAdmin();

        $page = RequestHelper::readPage($request);
        $limit = RequestHelper::readLimit($request);

        try {
            /** @var MailAliasSearchQueryDTO $searchQueryAliasDTO */
            $searchQueryAliasDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailAliasSearchQueryDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $searchQueryAliasDTO->page = $page;
        $searchQueryAliasDTO->limit = $limit;

        $mailAliasListDTO = $searchMailAliasService->search($searchQueryAliasDTO);

        $responseData = $this->serializer->serialize(
            data: $mailAliasListDTO,
            format: 'json',
            context: ['groups' => ['alias:list']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }
}


?>