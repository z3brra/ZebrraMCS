<?php

namespace App\Controller;

use App\DTO\Mail\MailSendRequestDTO;
use App\Service\MailToken\SendMailTokenService;
use App\Http\Error\ApiException;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/token/mail', name: 'app_api_v1_token_mail_')]
final class MailTokenController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/send', name: 'send', methods: 'POST')]
    public function send(
        Request $request,
        SendMailTokenService $sendMailService
    ): JsonResponse {
        try {
            /**
             * @var MailSendRequestDTO $mailSendDTO
             */
            $mailSendDTO = $this->serializer->deserialize(
                data: $request->getContent(),
                type: MailSendRequestDTO::class,
                format: 'json',
            );
        } catch (\Throwable) {
            throw ApiException::badRequest('Invalid JSON format.');
        }

        $result = $sendMailService->send($mailSendDTO);

        $responseData = $this->serializer->serialize(
            data: ['data' => $result],
            format: 'json',
            context: ['groups' => ['mail:send:response']]
        );

        return new JsonResponse(
            data: $responseData,
            status: JsonResponse::HTTP_OK,
            json: true
        );
    }
}

?>
