<?php

namespace App\Service;

use App\Http\Error\ApiException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    /**
     * @param list<string> $groups
     */
    public function validate(object $dto, array $groups): void
    {
        $violations = $this->validator->validate($dto, null, $groups);

        if (count($violations) === 0) {
            return;
        }

        $details = [
            'violations' => [],
        ];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $details['violations'][] = [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
                'code' => $violation->getCode(),
            ];
        }

        throw ApiException::validation('Validation error', $details);
    }

    /**
     * @param list<object> $dtos
     * @param list<string> $groups
     */
    public function validateEach(array $dtos, array $groups): void
    {
        foreach ($dtos as $dto) {
            if (!is_object($dto)) {
                continue;
            }
            $this->validate($dto, $groups);
        }
    }
}

?>
