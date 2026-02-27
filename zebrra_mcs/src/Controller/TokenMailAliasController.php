<?php

namespace App\Controller;

// use App\Http\Error\ApiException;
use App\Platform\Enum\Permission;

use App\Service\Access\AccessControlService;
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
}

?>