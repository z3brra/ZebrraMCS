<?php

namespace App\DTO\MailUser;

use App\DTO\Common\PaginationMetaDTO;
use Symfony\Component\Serializer\Attribute\Groups;

final class MailUserListResponseDTO
{
    /**
     * @var list<MailUserListItemDTO>
     */
    #[Groups(['user:list'])]
    public array $data;

    #[Groups(['user:list'])]
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