<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\DTO;

class ListLendingsResponseDTO
{
    public function __construct(
        public readonly array $lendings,
        public readonly int $page,
        public readonly int $maxPage
    )
    {

    }
}
