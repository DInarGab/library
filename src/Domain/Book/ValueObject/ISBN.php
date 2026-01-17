<?php
declare(strict_types=1);

namespace Dinargab\LibraryBot\Domain\Book\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Stringable;

#[ORM\Embeddable]
class ISBN implements Stringable
{
    #[ORM\Column(type: 'string', length: 20, name: 'isbn', nullable: true)]
    private ?string $value = null;

    public function __construct(string $value)
    {
        $normalized = preg_replace('/[^0-9X]/i', '', $value);

        if (!$this->isValid($normalized)) {
            throw new InvalidArgumentException("Invalid ISBN: $value");
        }

        $this->value = $normalized;
    }

    private function isValid(string $isbn): bool
    {
        $length = strlen($isbn);

        if ($length === 10) {
            return $this->isValidIsbn10($isbn);
        }

        if ($length === 13) {
            return $this->isValidIsbn13($isbn);
        }

        return false;
    }

    private function isValidIsbn10(string $isbn): bool
    {
        if (strlen($isbn) !== 10) return false;
        $check = 0;
        for ($i = 0; $i < 10; $i++) {
            if ('x' === strtolower($isbn[$i])) {
                $check += 10 * (10 - $i);
            } elseif (is_numeric($isbn[$i])) {
                $check += (int)$isbn[$i] * (10 - $i);
            } else {
                return false;
            }
        }
        return (($check % 11) === 0);
    }

    private function isValidIsbn13(string $isbn): bool
    {
        if (strlen($isbn) !== 13) return false;
        $check = 0;
        for ($i = 0; $i < 13; $i += 2) {
            $check += (int)$isbn[$i];
        }
        for ($i = 1; $i < 12; $i += 2) {
            $check += 3 * $isbn[$i];
        }
        return (($check % 10) === 0);
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value ?? "";
    }
}
