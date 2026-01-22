<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Lending\DTO;

class NotifyBeforeDeadlineResponseDTO
{
    public function __construct(
        public readonly array $lendingsDto,
    ) {
    }
}
