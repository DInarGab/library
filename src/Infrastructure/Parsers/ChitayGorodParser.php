<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Parsers;

use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use Dinargab\LibraryBot\Domain\Service\BookParserInterface;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChitayGorodParser implements BookParserInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function parseBookContent(string $url): ParsedBookDTO
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid URL");
        }
        $response = $this->httpClient->request('GET', $url);

        $dom = new DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new DOMXPath($dom);
        // Получаем все данные
        $description = $this->getDescription($xpath);
        $title       = $this->getTitle($xpath);
        $author      = $this->getAuthors($xpath);
        $isbn        = $this->getIsbn($xpath);

        return new ParsedBookDTO(
            title: $title,
            author: $author,
            description: $description,
            isbn: $isbn,
            url: $url,
        );
    }


    private function getIsbn(DOMXPath $xpath)
    {
        $elements = $xpath->query('//span[@itemprop="isbn"]');
        $isbn     = "";
        if ($elements && $elements->length > 0) {
            $isbn = $elements->item(0)->textContent;
        }

        return $isbn;
    }

    private function getTitle(DOMXPath $xpath): string
    {
        $elements = $xpath->query('//title');

        $title = "";
        if ($elements && $elements->length > 0) {
            $title = $elements->item(0)->textContent;
        }

        return $title;
    }

    private function getDescription(DOMXPath $xpath): string
    {
        $elements    = $xpath->query("//div[contains(@class, 'product-description-short__text')]");
        $description = "";
        if ($elements && $elements->length > 0) {
            $firstElement = $elements->item(0);
            $description  = $firstElement->textContent;
        }

        return $description;
    }

    private function getAuthors(DOMXPath $xpath): string
    {
        $elements = $xpath->query("//li[contains(@class, 'product-authors__link')]");
        $authors  = [];
        foreach ($elements as $element) {
            $authors[] = $element->textContent;
        }

        return implode(", ", $authors);
    }
}
