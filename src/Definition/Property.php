<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\InvalidArgumentException;
use Innmind\Immutable\SetInterface;

final class Property
{
    private $name;
    private $type;
    private $access;
    private $variants;
    private $optional;

    public function __construct(
        string $name,
        Type $type,
        Access $access,
        SetInterface $variants,
        bool $optional
    ) {
        if (
            empty($name) ||
            (string) $variants->type() !== 'string'
        ) {
            throw new InvalidArgumentException;
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
