<?php

namespace App\Platform\Entity;

use App\Platform\Repository\ApiTokenScopeRepository;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiTokenScopeRepository::class)]
#[ORM\Table(name: 'api_token_scopes')]
#[ORM\Index(name: 'IDX_API_TOKEN_SCOPES_DOMAIN_UUID', columns: ['domainUuid'])]
class ApiTokenScope
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ApiToken::class, inversedBy: 'scopes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ApiToken $token = null;

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $domainUuid;

    public function __construct(ApiToken $token, string $domainUuid)
    {
        $this->token = $token;
        $this->domainUuid = $domainUuid;
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

    public function getDomainUuid(): string
    {
        return $this->domainUuid;
    }
}

?>