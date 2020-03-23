<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Identity;

use Innmind\Rest\Client\{
    Identity as IdentityInterface,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

class Identity implements IdentityInterface
{
    private string $value;

    public function __construct(string $value)
    {
        if (Str::of($value)->empty()) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
