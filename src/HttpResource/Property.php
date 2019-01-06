<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\HttpResource;

use Innmind\Rest\Client\Exception\DomainException;
use Innmind\Immutable\Str;

final class Property
{
    private $name;
    private $value;

    public function __construct(string $name, $value)
    {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        $this->name = $name;
        $this->value = $value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value()
    {
        return $this->value;
    }
}
