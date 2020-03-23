<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    HttpResource\Property,
    Exception\DomainException,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str,
};

final class HttpResource
{
    private string $name;
    private MapInterface $properties;

    public function __construct(string $name, MapInterface $properties)
    {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        if (
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== Property::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type MapInterface<string, %s>',
                Property::class
            ));
        }

        $this->name = $name;
        $this->properties = $properties;
    }

    public static function of(string $name, Property ...$properties)
    {
        $map = Map::of('string', Property::class);

        foreach ($properties as $property) {
            $map = $map->put($property->name(), $property);
        }

        return new self($name, $map);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return MapInterface<string, Property>
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }
}
