<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    HttpResource\Property,
    Exception\InvalidArgumentException
};
use Innmind\Immutable\MapInterface;

final class HttpResource
{
    private $name;
    private $properties;

    public function __construct(string $name, MapInterface $properties)
    {
        if (
            empty($name) ||
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== Property::class
        ) {
            throw new InvalidArgumentException;
        }

        $this->name = $name;
        $this->properties = $properties;
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
