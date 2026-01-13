<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\DTO;

class ReturnBookRequestDTO
{
    public function __construct(
        public readonly int $lendingId
    )
    {

    }
}