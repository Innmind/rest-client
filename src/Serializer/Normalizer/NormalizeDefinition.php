<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Definition\HttpResource,
    Definition\Property,
    Definition\AllowedLink,
};
use function Innmind\Immutable\unwrap;

final class NormalizeDefinition
{
    public function __invoke(HttpResource $definition): array
    {
        /** @psalm-suppress InvalidScalarArgument */
        $metas = \array_combine(
            unwrap($definition->metas()->keys()),
            unwrap($definition->metas()->values()),
        );

        return [
            'url' => $definition->url()->toString(),
            'identity' => $definition->identity()->toString(),
            'properties' => $definition
                ->properties()
                ->reduce(
                    [],
                    static function(array $properties, string $name, Property $property): array {
                        $properties[$name] = [
                            'type' => $property->type()->toString(),
                            'access' => unwrap($property->access()->mask()),
                            'variants' => unwrap($property->variants()),
                            'optional' => $property->isOptional(),
                        ];

                        return $properties;
                    }
                ),
            'metas' => $metas,
            'linkable_to' => $definition
                ->links()
                ->reduce(
                    [],
                    static function(array $links, AllowedLink $link): array {
                        $links[] = [
                            'relationship' => $link->relationship(),
                            'resource_path' => $link->resourcePath(),
                            'parameters' => unwrap($link->parameters()),
                        ];

                        return $links;
                    }
                ),
            'rangeable' => $definition->isRangeable(),
        ];
    }
}
