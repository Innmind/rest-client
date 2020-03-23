<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\DomainException;
use Innmind\Immutable\Str;

final class Identity
{
    private string $name;

    public function __construct(string $name)
    {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        $this->name = $name;
    }

    public function toString(): string
    {
        return $this->name;
    }
}
