<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Rest\Client\{
    Definition\Types,
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

final class DenormalizeDefinition
{
    private Types $build;

    public function __construct(Types $build)
    {
        $this->build = $build;
    }

    public function __invoke(array $definition, string $name): HttpResource
    {
        $properties = new Map('string', Property::class);
        $metas = Map::of(
            'scalar',
            'variable',
            \array_keys($definition['metas']),
            \array_values($definition['metas'])
        );
        $links = Set::of(AllowedLink::class);

        foreach ($definition['properties'] as $property => $value) {
            $properties = $properties->put(
                $property,
                $this->buildProperty($property, $value)
            );
        }

        foreach ($definition['linkable_to'] as $value) {
            $links = $links->add($this->buildLink($value));
        }

        return new HttpResource(
            $name,
            Url::fromString($definition['url']),
            new Identity($definition['identity']),
            $properties,
            $metas,
            $links,
            $definition['rangeable']
        );
    }

    private function buildProperty(string $name, array $definition): Property
    {
        $variants = Set::of('string', ...\array_values($definition['variants']));

        return new Property(
            $name,
            ($this->build)($definition['type']),
            new Access(...$definition['access']),
            $variants,
            $definition['optional']
        );
    }

    private function buildLink(array $link): AllowedLink
    {
        return new AllowedLink(
            $link['resource_path'],
            $link['relationship'],
            Set::of('string', ...$link['parameters'])
        );
    }
}
