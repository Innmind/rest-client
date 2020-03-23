<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Definition\HttpResource as ResourceDefinition,
    Definition\Property,
    Definition\Access,
    HttpResource,
    Exception\MissingProperty,
};

final class NormalizeResource
{
    public function __invoke(
        HttpResource $resource,
        ResourceDefinition $definition,
        Access $access
    ): array {
        $properties = $definition
            ->properties()
            ->filter(function(string $name, Property $property) use ($access): bool {
                return $property->access()->matches($access);
            })
            ->filter(function(string $name, Property $property) use ($resource): bool {
                if (!$property->isOptional()) {
                    return true;
                }

                $name = $this->resolveName($property, $resource);

                return $resource->properties()->contains($name);
            });

        $properties->foreach(function(string $name, Property $property) use ($resource) {
            $name = $this->resolveName($property, $resource);

            if (!$resource->properties()->contains($name)) {
                throw new MissingProperty($name);
            }
        });

        $properties = $properties->reduce(
            [],
            function(array $properties, string $name, Property $property) use ($resource): array {
                $usedName = $this->resolveName($property, $resource);

                /** @psalm-suppress MixedAssignment */
                $properties[$name] = $property
                    ->type()
                    ->normalize(
                        $resource
                            ->properties()
                            ->get($usedName)
                            ->value()
                    );

                return $properties;
            }
        );

        return ['resource' => $properties];
    }

    private function resolveName(
        Property $property,
        HttpResource $resource
    ): string {
        return $property
            ->variants()
            ->reduce(
                $property->name(),
                function(string $usedName, string $variant) use ($resource): string {
                    if ($resource->properties()->contains($variant)) {
                        return $variant;
                    }

                    return $usedName;
                }
            );
    }
}
