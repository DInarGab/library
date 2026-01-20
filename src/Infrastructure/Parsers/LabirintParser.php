<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Infrastructure\Parsers;

use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use Dinargab\LibraryBot\Domain\Service\BookParserInterface;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LabirintParser implements BookParserInterface
{

    public function __construct(
        private HttpClientInterface $httpClient,
    )
    {

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
        [$title, $author] = explode(":", $this->getTitleAndAuthor($xpath));
        $isbn = $this->getIsbn($xpath);

        return new ParsedBookDTO(
            title: $title,
            author: $author,
            description: $description,
            isbn: $isbn,
            url: $url,
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
            $description = $firstElement->textContent;
        }

        return $description;
    }

    private function getTitleAndAuthor(DOMXPath $xpath): string
    {
        // Найти первый h1 на странице
        $h1 = $xpath->query('//h1');
        $h1Text = "";
        if ($h1->length > 0) {
            $h1Text = trim($h1->item(0)->textContent);
        }

        return $h1Text;
    }


    private function getIsbn(DOMXPath $xpath): string
    {
        $meta = $xpath->query('//meta[@itemprop="isbn"]');
        $isbn = "";
        if ($meta->length > 0) {
            $isbn = trim($meta->item(0)->getAttribute('content'));
        }
        return $isbn;
    }

}
