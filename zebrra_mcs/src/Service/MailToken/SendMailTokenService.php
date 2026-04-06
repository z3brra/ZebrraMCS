<?php

namespace App\Service\MailToken;

use App\DTO\Mail\{
    MailAddressDTO,
    MailSendRequestDTO,
    MailSendResponseDTO,
    MailAttachmentDTO,
};

use App\Platform\Enum\Permission;

use App\Service\Domain\{
    MailDomainGatewayService,
    MailDomainLinkResolver
};

use App\Service\ValidationService;
use App\Service\Access\AccessControlService;

use App\Http\Error\ApiException;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class SendMailTokenService
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly AccessControlService $accessControl,
        private readonly MailDomainGatewayService $mailDomainGateway,
        private readonly MailDomainLinkResolver $mailDomainResolver,
        private readonly MailerInterface $mailer,
    ) {}

    public function send(MailSendRequestDTO $mailSendDTO): MailSendResponseDTO
    {
        $this->accessControl->denyUnlessPermission(Permission::MAIL_SEND);
        $this->validationService->validate($mailSendDTO, ['mail:send']);

        if (($mailSendDTO->textBody === null || trim($mailSendDTO->textBody) === '') &&
            ($mailSendDTO->htmlBody === null || trim($mailSendDTO->htmlBody) === '')) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        [
                            'property' => 'textBody/htmlBody',
                            'message' => 'At least one of textBody or htmlBody is required.',
                            'code' => null,
                        ],
                    ],
                ],
            );
        }

        $fromEmail = mb_strtolower(trim($mailSendDTO->from->email));
        $fromDomain = $this->extractDomain($fromEmail);

        if ($fromDomain === null) {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        [
                            'property' => 'from.email',
                            'message' => 'Invalid sender email.',
                            'code' => null,
                        ],
                    ],
                ],
            );
        }

        $domainRow = $this->mailDomainGateway->findByName($fromDomain);
        if (!$domainRow) {
            throw ApiException::notFound('Sender domain not found or does not exists.');
        }

        $domainUuid = $this->mailDomainResolver->resolveMailDomainUuid((int) $domainRow['id']);
        $this->accessControl->denyUnlessDomainScopeAllowed($domainUuid);

        $email = new Email();

        $email->from($this->toAddress($mailSendDTO->from));

        foreach ($mailSendDTO->to as $recipient) {
            $email->addTo($this->toAddress($recipient));
        }

        foreach ($mailSendDTO->cc as $recipient) {
            $email->addCc($this->toAddress($recipient));
        }

        foreach ($mailSendDTO->bcc as $recipient) {
            $email->addBcc($this->toAddress($recipient));
        }

        foreach ($mailSendDTO->replyTo as $recipient) {
            $email->addReplyTo($this->toAddress($recipient));
        }

        if ($mailSendDTO->returnPath !== null && trim($mailSendDTO->returnPath) !== '') {
            $email->returnPath(mb_strtolower(trim($mailSendDTO->returnPath)));
        }

        $email->subject($mailSendDTO->subject);

        if ($mailSendDTO->textBody !== null && trim($mailSendDTO->textBody) !== '') {
            $email->text($mailSendDTO->textBody);
        }

        if ($mailSendDTO->htmlBody !== null && trim($mailSendDTO->htmlBody) !== '') {
            $email->html($mailSendDTO->htmlBody);
        }

        $this->attachFiles($email, $mailSendDTO->attachments);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw ApiException::internal(
                message: 'Mail transport failed.',
                details: [
                    'transportError' => $e->getMessage(),
                ],
                previous: $e
            );
        }

        return new MailSendResponseDTO(
            status: 'sent',
            messageId: $email->getHeaders()->get('Message-ID')?->getBodyAsString()
        );
    }

    private function attachFiles(Email $email, array $attachments): void
    {
        $totalBytes = 0;
        $maxBytesPerAttachment = 5 * 1024 * 1024;
        $maxBytesTotal = 20 * 1024 * 1024;

        foreach ($attachments as $attachment) {
            $filename = '';
            $contentType = null;
            $contentBase64 = '';

            if ($attachment instanceof MailAttachmentDTO) {
                $filename = trim($attachment->filename);
                $contentType = $attachment->contentType !== null ? trim($attachment->contentType) : null;
                $contentBase64 = trim($attachment->contentBase64);
            } else {
                $filename = isset($attachment['filename']) ? trim((string) $attachment['filename']) : '';
                $contentType = isset($attachment['contentType']) ? trim((string) $attachment['contentType']) : null;
                $contentBase64 = isset($attachment['contentBase64']) ? trim((string) $attachment['contentBase64']) : '';
            }

            if ($filename === '') {
                throw ApiException::validation(
                    message: 'Validation error',
                    details: [
                        'violations' => [
                            [
                                'property' => 'attachments filename',
                                'message' => 'Attachment filename is required.',
                                'code' => null,
                            ],
                        ],
                    ],
                );
            }

            if ($contentBase64 === '') {
                throw ApiException::validation(
                    message: 'Validation error',
                    details: [
                        'violations' => [
                            [
                                'property' => 'attachments.contentBase64',
                                'message' => 'Attachment contentBase64 is required.',
                                'code' => null,
                            ],
                        ],
                    ],
                );
            }

            $decoded = base64_decode($contentBase64, true);
            if ($decoded === false) {
                throw ApiException::validation(
                    message: 'Validation error',
                    details: [
                        'violations' => [
                            [
                                'property' => 'attachments.contentBase64',
                                'message' => 'Attachment contentBase64 must be valid base64.',
                                'code' => null,
                            ],
                        ],
                    ],
                );
            }

            $size = strlen($decoded);
            if ($size > $maxBytesPerAttachment) {
                throw ApiException::validation(
                    message: 'Validation error',
                    details: [
                        'violations' => [
                            [
                                'property' => 'attachments',
                                'message' => 'Attachment exceeds maximum size of 5 MB.',
                                'code' => null,
                            ],
                        ],
                    ],
                );
            }

            $totalBytes += $size;
            if ($totalBytes > $maxBytesTotal) {
                throw ApiException::validation(
                    message: 'Validation error',
                    details: [
                        'violations' => [
                            [
                                'property' => 'attachments',
                                'message' => 'Total attachment size exceeds maximum of 20 MB.',
                                'code' => null,
                            ],
                        ],
                    ],
                );
            }

            if ($contentType !== null && $contentType !== '') {
                $email->attach($decoded, $filename, $contentType);
            } else {
                $email->attach($decoded, $filename);
            }
        }
    }

    /**
     * @param MailAddressDTO|array<string, mixed> $addressData
     */
    private function toAddress(MailAddressDTO|array $addressData): Address
    {
        if ($addressData instanceof MailAddressDTO) {
            $email = mb_strtolower(trim($addressData->email));
            $name = $addressData->name !== null ? trim($addressData->name) : '';

            if ($name === '') {
                return new Address($email);
            }
            return new Address($email, $name);
        }

        $email = isset($addressData['email']) ? mb_strtolower(trim((string) $addressData['email'])) : '';
        $name = isset($addressData['name']) ? trim((string) $addressData['name']) : '';

        if ($email === '') {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        [
                            'property' => 'email',
                            'message' => 'Email is required.',
                            'code' => null,
                        ],
                    ],
                ],
            );
        }

        if ($name === '') {
            return new Address($email);
        }
        return new Address($email, $name);
    }

    private function extractDomain(string $email): ?string
    {
        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return null;
        }

        $domain = trim(substr($email, $atPos + 1));

        return $domain !== '' ? mb_strtolower($domain) : null;
    }
}


?>