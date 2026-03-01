<?php

namespace App\Controller;

use App\DTO\MailAlias\MailAliasCreateDTO;
use App\Http\Error\ApiException;
use App\Platform\Enum\Permission;

use App\Service\Access\AccessControlService;
use App\Service\MailAlias\Token\CreateMailAliasTokenService;
use App\Service\MailAlias\Token\DeleteMailAliasTokenService;
use App\Service\MailAlias\Token\ReadMailAliasTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/token/aliases', name: 'app_api_v1_token_aliases_')]
final class TokenMailAliasController extends AbstractController
{
    public function __construct(
        private readonly AccessControlService $accessControl,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/{uuid}', name: 'read', methods: 'GET')]
    public function read(
        string $uuid,
        ReadMailAliasTokenService $readAliasService,
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessPermission(Permission::ALIASES_READ);

        $mailAliasReadDTO = $readAliasService->read($uuid);

        $responseData = $this->serializer->serialize(
            data: ['data' => $mailAliasReadDTO],
            format: 'json',
            context: ['groups' => ['alias:read']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }

    #[Route('', name: 'create', methods: 'POST')]
    public function create(
        Request $request,
        CreateMailAliasTokenService $createAliasService
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessPermission(Permission::ALIASES_CREATE);

        try {
            /** @var MailAliasCreateDTO $createAliasDTO */
            $createAliasDTO = $this->serializer->deserialize(
                data: $request->getcontent(),
                type: MailAliasCreateDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $readAliasDTO = $createAliasService->create($createAliasDTO);

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
        DeleteMailAliasTokenService $deleteAliasService
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessPermission(Permission::ALIASES_DELETE);

        $deleteAliasService->delete($uuid);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

?>