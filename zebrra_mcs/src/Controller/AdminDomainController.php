<?php

namespace App\Controller;

use App\DTO\Domain\DomainCreateDTO;
use App\Http\Error\ApiException;
use App\Service\Domain\CreateDomainAdminService;
use App\Service\Access\AccessControlService;

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
}


?>