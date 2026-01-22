<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Parsers;

use Dinargab\LibraryBot\Domain\Book\Factory\BookParserFactoryInterface;
use Dinargab\LibraryBot\Domain\Service\BookParserInterface;
use InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookParserFactory implements BookParserFactoryInterface
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    public function createFromUrl(string $url): BookParserInterface
    {
        $hostname = parse_url($url, PHP_URL_HOST);

        return match ($hostname) {
            "www.labirint.ru", "labirint.ru" => new LabirintParser($this->client),
            "www.chitai-gorod.ru", "chitai-gorod.ru" => new ChitayGorodParser($this->client),
            default => throw new InvalidArgumentException('Can not parse supplied book url')
        };
    }

}
