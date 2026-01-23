<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Parsers;

use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use DOMXPath;

class LabirintParser extends AbstractParser
{

    public const SUPPORTED_HOST = 'labirint.ru';
    private const SUPPORTED_PATH = 'books';
    protected function extractBookData(DOMXPath $xpath, string $url): ParsedBookDTO
    {
        ['title' => $title, 'author' => $author] = $this->getTitleAndAuthor($xpath);

        if (empty($title)) {
            throw new \InvalidArgumentException('No parsable content on page');
        }

        return new ParsedBookDTO(
            $title,
            $this->getDescription($xpath),
            $url,
            $author,
            $this->getIsbn($xpath),
        );
    }


    private function getDescription(DOMXPath $xpath): string
    {
        $query = "//div[contains(@class, 'area-info')]//*[contains(@class, 'hidden') and contains(@class, 'md:block')]/div";

        $elements = $xpath->query($query);

        $description = "";

        if ($elements && $elements->length > 0) {
            // Получаем первый найденный элемент
            $firstElement = $elements->item(0);
            $description  = $firstElement->textContent;
        }

        return $description;
    }

    private function getTitleAndAuthor(DOMXPath $xpath): array
    {
        // Найти первый h1 на странице
        $h1     = $xpath->query('//h1');
        $h1Text = "";
        if ($h1->length > 0) {
            $h1Text = trim($h1->item(0)->textContent);
        }

        if (str_contains($h1Text, ':')) {
            $parts = explode(':', $h1Text);

            return [
                "title"  => $parts[0],
                'author' => $parts[1]
            ];
        }

        return [
            'title'  => $h1Text,
            'author' => ''
        ];
    }


    private function getIsbn(DOMXPath $xpath): string
    {
        $meta = $xpath->query('//meta[@itemprop="isbn"]');
        $isbn = "";
        if ($meta->length > 0) {
            $isbn = trim($meta->item(0)->getAttribute('content'));
            //Может быть список, берем первый
            $isbn = explode(',', $isbn)[0];
        }

        return $isbn;
    }


}
