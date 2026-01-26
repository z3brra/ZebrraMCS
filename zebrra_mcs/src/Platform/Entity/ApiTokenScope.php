<?php

namespace App\Platform\Entity;

use App\Platform\Repository\ApiTokenScopeRepository;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiTokenScopeRepository::class)]
#[ORM\Table(name: 'api_token_scopes')]
class ApiTokenScope
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ApiToken::class, inversedBy: 'scopes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ApiToken $token = null;

    #[ORM\Id]
    #[ORM\Column]
    private int $domainId;

    public function __construct(ApiToken $token, int $domainId)
    {
        $this->token = $token;
        $this->domainId = $domainId;
    }

    public function getToken(): ApiToken
    {
        return $this->token;
    }

    public function setToken(?ApiToken $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getDomainId(): int
    {
        return $this->domainId;
    }
}

?>