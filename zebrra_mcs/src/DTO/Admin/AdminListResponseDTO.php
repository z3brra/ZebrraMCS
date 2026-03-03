<?php

namespace App\DTO\Admin;

use App\DTO\Common\PaginationMetaDTO;
use Symfony\Component\Serializer\Attribute\Groups;

final class AdminListResponseDTO
{
    /**
     * @var list<AdminListItemDTO>
     */
    #[Groups(['admin:list'])]
    public array $data;

    #[Groups(['admin:list'])]
    public PaginationMetaDTO $meta;

    public function __construct(
        array $data,
        PaginationMetaDTO $meta,
    ) {
        $this->data = $data;
        $this->meta = $meta;
    }
}

?>