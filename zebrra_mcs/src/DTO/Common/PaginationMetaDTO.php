<?php

namespace App\DTO\Common;

use Symfony\Component\Serializer\Annotation\Groups;

final class PaginationMetaDTO
{
    #[Groups(['token:list'])]
    public int $page;

    #[Groups(['token:list'])]
    public int $perPage;

    #[Groups(['token:list'])]
    public int $total;

    #[Groups(['token:list'])]
    public int $totalPages;

    #[Groups(['token:list'])]
    public ?string $sort;

    #[Groups(['token:list'])]
    public ?string $order;

    public function __construct(
        int $page,
        int $perPage,
        int $total,
        ?string $sort = null,
        ?string $order = null,
    )
    {
        $this->page = $page;
        $this->perPage = $perPage;
        $this->total = $total;
        $this->totalPages = (int) max(1, (int) ceil($total / max(1, $perPage)));
        $this->sort = $sort;
        $this->order = $order;
    }
}

?>