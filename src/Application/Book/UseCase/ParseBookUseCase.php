<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\ParseBookRequestDTO;
use Dinargab\LibraryBot\Application\Shared\DTO\ParsedBookDTO;
use Dinargab\LibraryBot\Domain\Book\Factory\BookParserFactoryInterface;

class ParseBookUseCase
{
    public function __construct(
        private BookParserFactoryInterface $parserFactory

    )
    {

    }

    public function __invoke(
        ParseBookRequestDTO $parseBookRequestDTO
    ): ?ParsedBookDTO
    {
        try {
            $parser = $this->parserFactory->createFromUrl($parseBookRequestDTO->url);
            return $parser->parseBookContent($parseBookRequestDTO->url);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }

    }
}
