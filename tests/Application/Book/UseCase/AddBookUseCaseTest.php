<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Application\Book\UseCase;

use Dinargab\LibraryBot\Application\Book\DTO\AddBookRequestDTO;
use Dinargab\LibraryBot\Application\Book\UseCase\AddBookUseCase;
use Dinargab\LibraryBot\Domain\Book\Factory\BookFactoryInterface;
use Dinargab\LibraryBot\Domain\Book\Repository\BookCopyRepositoryInterface;
use Dinargab\LibraryBot\Domain\Book\Repository\BookRepositoryInterface;
use Dinargab\LibraryBot\Domain\Event\EventDispatcherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddBookUseCaseTest extends TestCase
{

    private BookRepositoryInterface&MockObject $bookRepository;
    private BookFactoryInterface&MockObject $bookFactory;
    private BookCopyRepositoryInterface&MockObject $bookCopyRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->bookRepository     = $this->createMock(BookRepositoryInterface::class);
        $this->bookCopyRepository = $this->createMock(BookCopyRepositoryInterface::class);
        $this->bookFactory        = $this->createMock(BookFactoryInterface::class);
        $this->eventDispatcher    = $this->createMock(EventDispatcherInterface::class);

        $this->useCase = new AddBookUseCase(
            $this->bookRepository,
            $this->bookCopyRepository,
            $this->bookFactory,
            $this->eventDispatcher,
        );
    }


    public function addBookSuccessfullyTest(): void
    {
        $requestDto = new AddBookRequestDTO(
            title: "Книга",
            author: "Автор Авторович",
            isbn: '978-0-306-40615-7',
            description: 'Описание книги',
            copies: 3,
        );
        $this->bookRepository->expects($this->once())
            ->method('save');

    }


}
