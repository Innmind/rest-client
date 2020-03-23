<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    HttpResource\Property,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\assertMap;

final class HttpResource
{
    private string $name;
    /** @var Map<string, Property> */
    private Map $properties;

    /**
     * @param Map<string, Property> $properties
     */
    public function __construct(string $name, Map $properties)
    {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        assertMap('string', Property::class, $properties, 2);

        $this->name = $name;
        $this->properties = $properties;
    }

    public static function of(string $name, Property ...$properties): self
    {
        /** @var Map<string, Property> */
        $map = Map::of('string', Property::class);

        foreach ($properties as $property) {
            $map = ($map)($property->name(), $property);
        }

        return new self($name, $map);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Map<string, Property>
     */
    public function properties(): Map
    {
        return $this->properties;
    }
}
