<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Link\Parameter;

use Innmind\Rest\Client\{
    Link\Parameter as ParameterInterface,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

final class Parameter implements ParameterInterface
{
    private string $key;
    private string $value;

    public function __construct(string $key, string $value)
    {
        if (Str::of($key)->empty()) {
            throw new DomainException;
        }

        $this->key = $key;
        $this->value = $value;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): string
    {
        return $this->value;
    }
}
