<?php

namespace App\DTO\Domain;

use App\DTO\Common\PaginationMetaDTO;
use Symfony\Component\Serializer\Attribute\Groups;

final class DomainListResponseDTO
{
    /**
     * @var list<DomainListItemDTO>
     */
    #[Groups(['domain:list'])]
    public array $data;

    #[Groups(['domain:list'])]
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