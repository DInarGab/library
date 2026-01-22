<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\DTO;

class GetLendingRequestDTO
{
    public function __construct(
        public int $lendingId,
    ) {
    }
}
