<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Link\ParameterInterface,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\MapInterface;

final class Link
{
    private $definition;
    private $identity;
    private $relationship;
    private $parameters;

    public function __construct(
        string $definition,
        IdentityInterface $identity,
        string $relationship,
        MapInterface $parameters = null
    ) {
        $parameters = $parameters ?? new Map('string', ParameterInterface::class);

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== ParameterInterface::class
        ) {
            throw new InvalidArgumentException;
        }

        $this->definition = $definition;
        $this->identity = $identity;
        $this->relationship = $relationship;
        $this->parameters = $parameters;
    }

    public function definition(): string
    {
        return $this->definition;
    }

    public function identity(): IdentityInterface
    {
        return $this->identity;
    }

    public function relationship(): string
    {
        return $this->relationship;
    }

    /**
     * @return MapInterface<string, ParameterInterface>
     */
    public function parameters(): MapInterface
    {
        return $this->parameters;
    }
}
