<?php

namespace App\Service\Token;

use App\DTO\Common\PaginationMetaDTO;
use App\DTO\Token\{
    TokenListItemDTO,
    TokenListQueryDTO,
    TokenListResponseDTO
};

use App\Platform\Entity\ApiToken;
use App\Platform\Repository\ApiTokenRepository;

use App\Service\ValidationService;

final class TokenListService
{
    public function __construct(
        private readonly ApiTokenRepository $tokenRepository,
        private readonly ValidationService $validationService,
    ) {}

    public function list(TokenListQueryDTO $queryDTO): TokenListResponseDTO
    {
        $this->validationService->validate($queryDTO, ['token:list']);
        $result = $this->tokenRepository->paginateByQuery($queryDTO);

        /**
         * @var list<ApiToken> $tokens
         */
        $tokens = $result['data'];

        $data = array_map(
            static fn (ApiToken $token) => TokenListItemDTO::fromEntity($token),
            $tokens
        );

        $meta = new PaginationMetaDTO(
            page: $result['page'],
            perPage: $result['perPage'],
            total: $result['total'],
            sort: $result['sort'],
            order: $result['order'],
        );

        return new TokenListResponseDTO($data, $meta);
    }
}

?>