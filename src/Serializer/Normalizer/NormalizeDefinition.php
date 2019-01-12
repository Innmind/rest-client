<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Definition\HttpResource,
    Definition\Property,
    Definition\Identity,
    Definition\Access,
    Definition\AllowedLink,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
};

final class NormalizeDefinition
{
    public function __invoke(HttpResource $definition): array
    {
        return [
            'url' => (string) $definition->url(),
            'identity' => (string) $definition->identity(),
            'properties' => $definition
                ->properties()
                ->reduce(
                    [],
                    function(array $properties, string $name, Property $property): array {
                        $properties[$name] = [
                            'type' => (string) $property->type(),
                            'access' => $property->access()->mask()->toPrimitive(),
                            'variants' => $property->variants()->toPrimitive(),
                            'optional' => $property->isOptional(),
                        ];

                        return $properties;
                    }
                ),
            'metas' => array_combine(
                $definition->metas()->keys()->toPrimitive(),
                $definition->metas()->values()->toPrimitive()
            ),
            'linkable_to' => $definition
                ->links()
                ->reduce(
                    [],
                    static function(array $links, AllowedLink $link): array {
                        $links[] = [
                            'relationship' => $link->relationship(),
                            'resource_path' => $link->resourcePath(),
                            'parameters' => $link->parameters()->toPrimitive(),
                        ];

                        return $links;
                    }
                ),
            'rangeable' => $definition->isRangeable(),
        ];
    }
}
