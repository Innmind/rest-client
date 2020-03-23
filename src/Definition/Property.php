<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\DomainException;
use Innmind\Immutable\{
    Set,
    Str,
};
use function Innmind\Immutable\assertSet;

final class Property
{
    private string $name;
    private Type $type;
    private Access $access;
    private Set $variants;
    private bool $optional;

    public function __construct(
        string $name,
        Type $type,
        Access $access,
        Set $variants,
        bool $optional
    ) {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        assertSet('string', $variants, 4);

        $this->name = $name;
        $this->type = $type;
        $this->access = $access;
        $this->variants = $variants;
        $this->optional = $optional;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function access(): Access
    {
        return $this->access;
    }

    public function variants(): Set
    {
        return $this->variants;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }
}
