<?php

namespace App\Controller;

use App\DTO\MailAlias\MailAliasCreateDTO;
use App\Http\Error\ApiException;
use App\Service\Access\AccessControlService;
use App\Service\MailAlias\{
    CreateMailAliasAdminService
};

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
}


?>