<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\DomainException;
use Innmind\Immutable\{
    SetInterface,
    Str,
};

final class Property
{
    private string $name;
    private Type $type;
    private Access $access;
    private SetInterface $variants;
    private bool $optional;

    public function __construct(
        string $name,
        Type $type,
        Access $access,
        SetInterface $variants,
        bool $optional
    ) {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        if ((string) $variants->type() !== 'string') {
            throw new \TypeError(sprintf('Argument 4 must be of type SetInterface<string>'));
        }

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

    public function variants(): SetInterface
    {
        return $this->variants;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }
}
