<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\Exception\InvalidArgumentException;

class Identity implements IdentityInterface
{
    private $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException;
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
