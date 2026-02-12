<?php

namespace App\DTO\MailAlias;

use Symfony\Component\Serializer\Attribute\Groups;

final class MailAliasCreateResponseDTO
{
    /**
     * @var list<MailAliasCreatedRowDTO>
     */
    #[Groups(['alias:create'])]
    public array $data;

    /**
     * @param list<MailAliasCreatedRowDTO> $data
     */
    public function __construct(
        array $data,
    ) {
        $this->data = $data;
    }
}

?>