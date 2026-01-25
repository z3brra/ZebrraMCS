<?php

namespace App\Platform\Entity;

use App\Platform\Repository\ApiTokenPermissionRepository;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiTokenPermissionRepository::class)]
#[ORM\Table(name: 'api_token_permissions')]
#[ORM\Index(name: 'IDX_API_TOKEN_PERMISSIONS_PERMISSION', columns: ['permission'])]
class ApiTokenPermission
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: ApiToken::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ApiToken $token;

    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $permission;

    public function __construct(
        ApiToken $token,
        string $permission
    ) {
        $this->token = $token;
        $this->permission = $permission;
    }

    public function getToken(): ApiToken
    {
        return $this->token;
    }

    public function getPermission(): string
    {
        return $this->permission;
    }
}

?>