<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\InvalidArgumentException;
use Innmind\Immutable\MapInterface;

final class HttpResource
{
    private $name;
    private $identity;
    private $properties;
    private $metas;
    private $rangeable;

    public function __construct(
        string $name,
        Identity $identity,
        MapInterface $properties,
        MapInterface $metas,
        bool $rangeable
    ) {
        if (
            empty($name) ||
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== Property::class ||
            (string) $metas->keyType() !== 'scalar' ||
            (string) $metas->valueType() !== 'variable'
        ) {
            throw new InvalidArgumentException;
        }

        $this->name = $name;
        $this->identity = $identity;
        $this->properties = $properties;
        $this->metas = $metas;
        $this->rangeable = $rangeable;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    /**
     * @return MapInterface<string, Property>
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }

    /**
     * @return MapInterface<string, variable>
     */
    public function metas(): MapInterface
    {
        return $this->metas;
    }

    public function isRangeable(): bool
    {
        return $this->rangeable;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
