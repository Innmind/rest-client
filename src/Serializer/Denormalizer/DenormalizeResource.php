<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Rest\Client\{
    Definition\HttpResource as ResourceDefinition,
    Definition\Property as PropertyDefinition,
    Definition\Access,
    HttpResource,
    HttpResource\Property,
    Exception\MissingProperty,
};
use Innmind\Immutable\Map;

final class DenormalizeResource
{
    public function __invoke(
        array $data,
        ResourceDefinition $definition,
        Access $access
    ): HttpResource {
        $data = $data['resource'];

        $properties = $definition
            ->properties()
            ->filter(function(string $name, PropertyDefinition $property) use ($access): bool {
                return $property->access()->matches($access);
            })
            ->filter(function(string $name, PropertyDefinition $property) use ($data): bool {
                if (!$property->isOptional()) {
                    return true;
                }

                return isset($data[$name]);
            })
            ->foreach(function(string $name) use ($data) {
                if (!isset($data[$name])) {
                    throw new MissingProperty($name);
                }
            })
            ->reduce(
                new Map('string', Property::class),
                function(Map $properties, string $name, PropertyDefinition $property) use ($data): Map {
                    return $properties->put(
                        $name,
                        new Property(
                            $name,
                            $property
                                ->type()
                                ->denormalize($data[$name])
                        )
                    );
                }
            );

        return new HttpResource(
            $definition->name(),
            $properties
        );
    }
}
