<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Parsers;

use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use DOMXPath;

class ChitayGorodParser extends AbstractParser
{

    public const SUPPORTED_HOST = 'chitai-gorod.ru';
    private const SUPPORTED_PATH = 'product';

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

    protected function supports(string $url): bool
    {
        $urlPath = parse_url($url, PHP_URL_PATH);

        return parent::supports($url) && str_contains($urlPath, self::SUPPORTED_PATH);
    }


    protected function extractBookData(DOMXPath $xpath, string $url): ParsedBookDTO
    {
        $title = $this->getTitle($xpath);
        if (empty($title)) {
            throw new \InvalidArgumentException('No parsable content on page');
        }
        return new ParsedBookDTO(
            $this->getTitle($xpath),
            $this->getDescription($xpath),
            $url,
            $this->getAuthors($xpath),
            $this->getIsbn($xpath)
        );
    }
}
