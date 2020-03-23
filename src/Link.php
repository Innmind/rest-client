<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Link\Parameter,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};

final class Link
{
    private string $definition;
    private Identity $identity;
    private string $relationship;
    /** @var Map<string, Parameter> */
    private Map $parameters;

    /**
     * @param Map<string, Parameter>|null $parameters
     */
    public function __construct(
        string $definition,
        Identity $identity,
        string $relationship,
        Map $parameters = null
    ) {
        $parameters ??= Map::of('string', Parameter::class);

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
                'Argument 4 must be of type Map<string, %s>',
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
        /** @var Map<string, Parameter> */
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
     * @return Map<string, Parameter>
     */
    public function parameters(): Map
    {
        return $this->parameters;
    }
}
