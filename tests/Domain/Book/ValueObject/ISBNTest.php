<?php

declare(strict_types=1);

namespace Dinargab\LibraryBot\Tests\Domain\Book\ValueObject;

use Dinargab\LibraryBot\Domain\Book\ValueObject\ISBN;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ISBNTest extends TestCase
{
    #[DataProvider('validIsbn10Provider')]
    public function testValidIsbn10(string $input, string $expected): void
    {
        $isbn = new ISBN($input);

        $this->assertSame($expected, $isbn->getValue());
    }

    public static function validIsbn10Provider(): array
    {
        return [
            'Простой isbn10'          => ['0306406152', '0306406152'],
            'С дефисами'              => ['0-306-40615-2', '0306406152'],
            'С пробелами'             => ['0 306 40615 2', '0306406152'],
            'С X на конце'            => ['080442957X', '080442957X'],
            'С дефисами и X на конце' => ['0-8044-2957-X', '080442957X'],
            'Разные разделители'      => ['0 306-40615 2', '0306406152'],
        ];
    }


    #[DataProvider('validIsbn13Provider')]
    public function testValidIsbn13(string $input, string $expected): void
    {
        $isbn = new ISBN($input);

        $this->assertSame($expected, $isbn->getValue());
    }

    public static function validIsbn13Provider(): array
    {
        return [
            'isbn13'          => ['9780306406157', '9780306406157'],
            'С дефисами'      => ['978-0-306-40615-7', '9780306406157'],
            'С пробелами'     => ['978 0 306 40615 7', '9780306406157'],
            'Смешанный'       => ['978-0 306-40615 7', '9780306406157'],
            'Еще один isbn13' => ['9781234567897', '9781234567897'],
        ];
    }

    // ==================== Invalid ISBN Tests ====================

    #[DataProvider('invalidIsbnProvider')]
    public function testInvalidIsbns(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid ISBN: $input");

        new ISBN($input);
    }

    public static function invalidIsbnProvider(): array
    {
        return [
            'Короткий'                                  => ['123456789'],
            'Длинный'                                   => ['12345678901234'],
            '11 символов'                               => ['12345678901'],
            '12 символов'                               => ['123456789012'],
            'Контрольная сумма неправильная для isbn10' => ['0306406151'],
            'Неправильная контрольная сумма для isbn13' => ['9780306406158'],
            'Пустая строка'                             => [''],
            'Только дефиса'                             => ['---'],
            'Буква в isbn10'                            => ['030640A152'],
            'Буква isbn13'                              => ['978030640A157'],
            'X где то не в конце isbn10'                => ['0X06406152'],
            'X есть в isbn13 isbn13'                    => ['978030640615X'],
            'Неверный'                                  => ['1234567890'],
        ];
    }


    public function removesNonNumericsFromISBN(): void
    {
        $isbn = new ISBN('ISBN: 978-0-306-40615-7');

        $this->assertSame('9780306406157', $isbn->getValue());
    }


    #[DataProvider('realWorldIsbnProvider')]
    public function testRealIsbns(string $input): void
    {
        $isbn = new ISBN($input);

        $this->assertNotEmpty($isbn->getValue());
    }

    public static function realWorldIsbnProvider(): array
    {
        return [
            'Clean Code (ISBN-13)'               => ['978-0132350884'],
            'The Pragmatic Programmer (ISBN-10)' => ['020161622X'],
            'Design Patterns (ISBN-13)'          => ['978-0201633610'],
            'Refactoring (ISBN-13)'              => ['978-0134757599'],
        ];
    }
}
