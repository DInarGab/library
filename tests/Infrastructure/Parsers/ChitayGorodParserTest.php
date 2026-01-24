<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Parsers;

use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use Dinargab\LibraryBot\Infrastructure\Parsers\LabirintParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ChitayGorodParserTest extends AbstractParserTestCase
{

    #[DataProvider('invalidUrlProvider')]
    public function testInvalidUrl(string $url): void
    {

        $parser = new LabirintParser($this->createMockHttpClient($this->getValidBookHtml()));

        $this->expectException(InvalidArgumentException::class);
        $parser->parseBookContent($url);
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'empty url'   => [''],
            'invalid url' => ['google'],
            'unsupported url' => ['https://google.com'],
            'unsupported url 2' => ['https://www.chitai-gorod.ru/product/medved-3139400'],
        ];
    }

    public function testParseBookContent(): void
    {
        $url = 'https://www.labirint.ru/books/740146/';
        $httpClient = new MockHttpClient(
            new MockResponse($this->getValidBookHtml(), ['http_code' => 200])
        );
        $parser = new LabirintParser($httpClient);


        $result = $parser->parseBookContent($url);
        $this->assertInstanceOf(ParsedBookDTO::class, $result);
        $this->assertEquals($url, $result->url);
        $this->assertEquals('Мастер и Маргарита', $result->title);
        $this->assertEquals('Михаил Булгаков', $result->author);
        $this->assertEquals('Великий роман о любви и дьяволе', $result->description);
        $this->assertEquals('978-5-389-12345-6', $result->isbn);
    }

    public function testParseBookH1WithoutColons(): void
    {
        $client = $this->createMockHttpClient($this->getHtmlWithH1WithoutColons());
        $parser = new LabirintParser($client);

        $dto = $parser->parseBookContent('https://www.labirint.ru/books/123');

        $this->assertSame('Только название', $dto->title);
        $this->assertSame('', $dto->author);
    }

    public function testParseBookWithoutExpectedElements(): void
    {
        $client = $this->createMockHttpClient($this->getHtmlWithoutAllExpectedDomElements());
        $parser = new LabirintParser($client);

        $this->expectException(\InvalidArgumentException::class);
        $parser->parseBookContent('https://www.labirint.ru/books/123');

    }

    private function getValidBookHtml()
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf8">
    <title>Мастер и Маргарита:Михаил Булгаков купить за дешево и быстро</title>
</head>
<body>
    <h1>Мастер и Маргарита:Михаил Булгаков</h1>
    <meta itemprop="isbn" content="978-5-389-12345-6">
    <div class="area-info">
        <div class="hidden md:block">
            <div>Великий роман о любви и дьяволе</div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getHtmlWithoutAllExpectedDomElements()
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf8">
    <title>Мастер и Маргарита:Михаил Булгаков купить за дешево и быстро</title>
</head>
<body>
    <h2>Мастер и Маргарита:Михаил Булгаков</h2>
    <div class="area-info">
        <div class="md:block">
            <div>Великий роман о любви и дьяволе</div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getHtmlWithH1WithoutColons()
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf8">
    <title>Мастер и Маргарита:Михаил Булгаков купить за дешево и быстро</title>
</head>
<body>
    <h1>Только название</h1>
    <meta itemprop="isbn" content="978-5-389-12345-6">
    <div class="area-info">
        <div class="hidden md:block">
            <div>Великий роман о любви и дьяволе</div>
        </div>
    </div>
</body>
</html>
HTML;
    }

}
