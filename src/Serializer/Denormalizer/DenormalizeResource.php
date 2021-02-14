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
        /** @var array */
        $data = $data['resource'];

        $properties = $definition
            ->properties()
            ->filter(static function(string $name, PropertyDefinition $property) use ($access): bool {
                return $property->access()->matches($access);
            })
            ->filter(static function(string $name, PropertyDefinition $property) use ($data): bool {
                if (!$property->isOptional()) {
                    return true;
                }

                return isset($data[$name]);
            });

        $properties->foreach(static function(string $name) use ($data) {
            if (!isset($data[$name])) {
                throw new MissingProperty($name);
            }
        });

        /** @var Map<string, Property> */
        $properties = $properties->toMapOf(
            'string',
            Property::class,
            static function(string $name, PropertyDefinition $property) use ($data): \Generator {
                /** @psalm-suppress MixedArrayAccess */
                yield $name => new Property(
                    $name,
                    $property
                        ->type()
                        ->denormalize($data[$name]),
                );
            },
        );

        return new HttpResource(
            $definition->name(),
            $properties,
        );
    }
}
