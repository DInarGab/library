<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Infrastructure\Parsers;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AbstractParserTestCase extends TestCase
{
    public function createMockHttpClient(string $content): HttpClientInterface
    {
        return new MockHttpClient(
            new MockResponse($content, ['http_code' => 200])
        );
    }
}
