<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Parsers;

use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use Dinargab\LibraryBot\Domain\Service\BookParserInterface;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractParser implements BookParserInterface
{
    const SUPPORTED_HOST = 'abstract';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    protected function createXPath(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        return new DOMXPath($dom);
    }

    public function parseBookContent(string $url): ParsedBookDTO
    {
        $this->validateUrl($url);

        $html  = $this->fetchHtml($url);
        $xpath = $this->createXPath($html);

        return $this->extractBookData($xpath, $url);
    }

    protected function validateUrl(string $url): void
    {
        $url = trim($url);

        if ($url === '') {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid URL format: $url");
        }

        if ( ! $this->supports($url)) {
            throw new InvalidArgumentException(
                "URL must be from " . self::SUPPORTED_HOST
            );
        }
    }

    protected function fetchHtml(string $url): string
    {
        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'User-Agent'      => 'Mozilla/5.0 (compatible; BookBot/1.0)',
                'Accept-Language' => 'ru-RU,ru;q=0.9',
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new RuntimeException(
                "Failed to fetch URL: HTTP $statusCode"
            );
        }

        return $response->getContent();
    }


    protected function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host !== null && str_contains($host, static::SUPPORTED_HOST);
    }

    abstract protected function extractBookData(DOMXPath $xpath, string $url): ParsedBookDTO;


}
