<?php

namespace App\DTO\Token;

use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class TokenCreateDTO
{
    #[Groups(['token:create'])]
    #[Assert\NotBlank(message: "Name is required.", groups: ['token:create'])]
    #[Assert\Length(
        max: 128,
        maxMessage: "Name may not exceed 128 chars.",
        groups: ['token:create']
    )]
    public ?string $name = null;

    /**
     * @var list<string>
     */
    #[Groups(['token:create'])]
    #[Assert\Valid(groups: ['token:create'])]
    public ?array $permissions = [];

    /**
     * @var list<int>
     */
    #[Groups(['token:create'])]
    #[Assert\Valid(groups: ['token:create'])]
    public ?array $scopedDomainUuids = [];

    #[Groups(['token:create'])]
    public ?DateTimeImmutable $expiresAt = null;

    public function isEmpty(): bool
    {
        return $this->name === null &&
               empty($this->permissions) &&
               empty($this->scopedDomainUuids) &&
               $this->expiresAt === null;
    }
}

?>