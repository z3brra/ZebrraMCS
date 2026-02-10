<?php

namespace App\DTO\Token;

use App\DTO\Common\PaginationMetaDTO;
use Symfony\Component\Serializer\Attribute\Groups;

final class TokenListResponseDTO
{
    /**
     * @var list<TokenListItemDTO>
     */
    #[Groups(['token:list'])]
    public array $data;

    #[Groups(['token:list'])]
    public PaginationMetaDTO $meta;

    /**
     * @param list<TokenListItemDTO> $data
     */
    public function __construct(
        array $data,
        PaginationMetaDTO $meta
    ) {
        $this->data = $data;
        $this->meta = $meta;
    }
}

?>