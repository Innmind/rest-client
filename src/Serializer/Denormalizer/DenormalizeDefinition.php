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

    /**
     * @param array{metas: array<scalar, scalar|array>, properties: array<string, array{variants: list<string>, type: string, access: list<string>, optional: bool}>, linkable_to: list<array{resource_path: string, relationship: string, parameters: list<string>}>, url: string, identity: string, rangeable: bool} $definition
     */
    public function __invoke(array $definition, string $name): HttpResource
    {
        /** @var Map<string, Property> */
        $properties = Map::of('string', Property::class);
        /** @var Map<scalar, scalar|array> */
        $metas = Map::of('scalar', 'scalar|array');
        /** @var Set<AllowedLink> */
        $links = Set::of(AllowedLink::class);

        foreach ($definition['metas'] as $key => $value) {
            $metas = ($metas)($key, $value);
        }

        foreach ($definition['properties'] as $property => $value) {
            $properties = ($properties)(
                $property,
                $this->buildProperty($property, $value),
            );
        }

        foreach ($definition['linkable_to'] as $value) {
            $links = ($links)($this->buildLink($value));
        }

        return new HttpResource(
            $name,
            Url::of($definition['url']),
            new Identity($definition['identity']),
            $properties,
            $metas,
            $links,
            $definition['rangeable'],
        );
    }

    /**
     * @param array{variants: list<string>, type: string, access: list<string>, optional: bool} $definition
     */
    private function buildProperty(string $name, array $definition): Property
    {
        $variants = Set::strings(...\array_values($definition['variants']));

        return new Property(
            $name,
            ($this->build)($definition['type']),
            new Access(...$definition['access']),
            $variants,
            $definition['optional'],
        );
    }

    /**
     * @param array{resource_path: string, relationship: string, parameters: list<string>} $link
     */
    private function buildLink(array $link): AllowedLink
    {
        return new AllowedLink(
            $link['resource_path'],
            $link['relationship'],
            Set::strings(...$link['parameters']),
        );
    }
}
