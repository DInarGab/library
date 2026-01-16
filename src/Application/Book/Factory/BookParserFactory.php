<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\Factory;

use Dinargab\LibraryBot\Domain\Book\Factory\BookParserFactoryInterface;
use Dinargab\LibraryBot\Domain\Service\BookParserInterface;
use Dinargab\LibraryBot\Infrastructure\Parsers\LabirynthParser\LabirynthParser;

class BookParserFactory implements BookParserFactoryInterface
{

    public function createFromUrl(string $url): BookParserInterface
    {
        $hostname = parse_url($url, PHP_URL_HOST);

        return match ($hostname) {
            "labyrinth" => new LabirynthParser()
        };
    }
}
