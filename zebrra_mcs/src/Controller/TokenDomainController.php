<?php

namespace App\Controller;

use App\DTO\Domain\DomainStatusPatchDTO;
use App\Http\Error\ApiException;
use App\Platform\Enum\Permission;
use App\Service\Access\AccessControlService;
use App\Service\Domain\ReadDomainAdminService;
use App\Service\Domain\Token\PatchDomainStatusTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/token/domains', name: 'app_api_v1_token_domains_')]
final class TokenDomainController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly AccessControlService $accessControl,
    ) {}

    #[Route('/{uuid}', name: 'read', methods: 'GET')]
    public function read(
        string $uuid,
        ReadDomainAdminService $readService,
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessPermission(Permission::DOMAINS_READ);
        $this->accessControl->denyUnlessDomainScopeAllowed($uuid);

        $domainReadDTO = $readService->read($uuid);

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
        PatchDomainStatusTokenService $domainPatchService,
    ): JsonResponse {
        $this->accessControl->denyUnlessToken();
        $this->accessControl->denyUnlessDomainScopeAllowed($uuid);

        try {
            /**
             * @var DomainStatusPatchDTO $domainPatchDTO
             */
            $domainPatchDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: DomainStatusPatchDTO::class,
                format: 'json'
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $domainPatchService->patch($uuid, $domainPatchDTO);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

?>