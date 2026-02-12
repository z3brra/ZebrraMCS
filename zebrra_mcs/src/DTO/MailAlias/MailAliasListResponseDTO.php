<?php

namespace App\DTO\MailAlias;

use App\DTO\Common\PaginationMetaDTO;
use Symfony\Component\Serializer\Attribute\Groups;

final class MailAliasListResponseDTO
{
    /**
     * @var list<MailAliasListItemDTO>
     */
    #[Groups(['alias:list'])]
    public array $data;

    #[Groups(['alias:list'])]
    public PaginationMetaDTO $meta;

    public function __construct(
        array $data,
        PaginationMetaDTO $meta
    ) {
        $this->data = $data;
        $this->meta = $meta;
    }
}


?>