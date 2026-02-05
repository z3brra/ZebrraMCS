<?php

namespace App\Service\MailUser;

use App\Http\Error\ApiException;

final class MailPasswordHasherService
{
    public function __construct(
        private readonly int $bcryptCost = 12
    ) {}

    public function hashForDovecot(string $plainPassword): string
    {
        if (trim($plainPassword) === '') {
            throw ApiException::validation(
                message: 'Validation error',
                details: [
                    'violations' => [
                        'property' => 'plaiPassword',
                        'message' => 'Password may not be empty.',
                        'code' => null
                    ],
                ],
            );
        }

        $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost]);

        if (!is_string($hash) || $hash === '') {
            throw ApiException::internal('Failed to hash password');
        }

        return '{BLF-CRYPT}' . $hash;
    }
}

?>