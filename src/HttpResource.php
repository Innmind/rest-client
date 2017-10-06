<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    HttpResource\Property,
    Exception\DomainException
};
use Innmind\Immutable\MapInterface;

final class HttpResource
{
    private $name;
    private $properties;

    public function __construct(string $name, MapInterface $properties)
    {
        if (empty($name)) {
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
