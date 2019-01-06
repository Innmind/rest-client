<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Link\Parameter,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class Link
{
    private $definition;
    private $identity;
    private $relationship;
    private $parameters;

    public function __construct(
        string $definition,
        Identity $identity,
        string $relationship,
        MapInterface $parameters = null
    ) {
        $parameters = $parameters ?? new Map('string', Parameter::class);

        if (
            Str::of($definition)->empty() ||
            Str::of($relationship)->empty()
        ) {
            throw new DomainException;
        }

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== Parameter::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 4 must be of type MapInterface<string, %s>',
                Parameter::class
            ));
        }

        $this->definition = $definition;
        $this->identity = $identity;
        $this->relationship = $relationship;
        $this->parameters = $parameters;
    }

    public static function of(
        string $definition,
        Identity $identity,
        string $relationship,
        Parameter ...$parameters
    ): self {
        $map = Map::of('string', Parameter::class);

        foreach ($parameters as $parameter) {
            $map = $map->put($parameter->key(), $parameter);
        }

        return new self($definition, $identity, $relationship, $map);
    }

    public function definition(): string
    {
        return $this->definition;
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    public function relationship(): string
    {
        return $this->relationship;
    }

    /**
     * @return MapInterface<string, Parameter>
     */
    public function parameters(): MapInterface
    {
        return $this->parameters;
    }
}
