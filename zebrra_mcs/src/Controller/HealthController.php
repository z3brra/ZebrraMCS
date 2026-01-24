<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    #[Route('/api/v1/health', name: 'app_api_v1_health', methods: 'GET')]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: [
                "data" => [
                    "status" => "ok"
                ]
            ],
            status: JsonResponse::HTTP_OK
        );
    }
}

?>