<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\Factory;

use Dinargab\LibraryBot\Domain\Book\Factory\BookParserFactoryInterface;
use Dinargab\LibraryBot\Domain\Service\BookParserInterface;
use Dinargab\LibraryBot\Infrastructure\Parsers\LabirynthParser\LabirintParser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BookParserFactory implements BookParserFactoryInterface
{
    public function __construct(
        private HttpClientInterface $client,
    )
    {

    }

    public function createFromUrl(string $url): BookParserInterface
    {
        $hostname = parse_url($url, PHP_URL_HOST);

        return match ($hostname) {
            "www.labirint.ru", "labirint.ru" => new LabirintParser($this->client),
            default => throw new \InvalidArgumentException('Can not parse supplied book url')
        };
    }
}
