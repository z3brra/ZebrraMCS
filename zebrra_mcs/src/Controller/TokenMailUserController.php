<?php

namespace App\Controller;

use App\Platform\Enum\Permission;
use App\Service\MailUser\Token\ReadMailUserTokenService;

use App\Service\Access\AccessControlService;

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
}

?>