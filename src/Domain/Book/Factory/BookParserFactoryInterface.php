<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\Factory;

use Dinargab\LibraryBot\Domain\Service\BookParserInterface;

interface BookParserFactoryInterface
{
    public function createFromUrl(string $url): BookParserInterface;
}
