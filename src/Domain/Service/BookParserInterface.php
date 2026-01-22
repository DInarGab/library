<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Service;

use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;

interface BookParserInterface
{
    public function parseBookContent(string $url): ParsedBookDTO;
}
