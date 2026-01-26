<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class VersionController extends AbstractController
{
    public function __construct(
        private string $apiVersion,
        private string $apiCommit,
        private string $apiBuildDate,
    ) {}

    #[Route('/api/v1/version', name: 'app_api_v1_version', methods: 'GET')]
    public function __invoke(): JsonResponse
    {
        $responseData = [
            "data" => [
                "apiVersion" => $this->apiVersion,
                "commit" => $this->apiCommit,
                "buildDate" => $this->apiBuildDate,
            ]
        ];

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK
        );
    }
}

?>