<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Exception\LogicException,
    Definition\HttpResource,
    Definition\Property,
    Definition\Identity,
    Definition\Access,
    Definition\Types,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
};
use Symfony\Component\Serializer\Normalizer\{
    DenormalizerInterface,
    NormalizerInterface,
};

final class DefinitionNormalizer implements DenormalizerInterface, NormalizerInterface
{
    private $types;

    public function __construct(Types $types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($definition, $class, $format = null, array $context = []): HttpResource
    {
        if (
            !$this->supportsDenormalization($definition, $class) ||
            !isset($context['name'])
        ) {
            throw new LogicException;
        }

        $properties = new Map('string', Property::class);
        $metas = Map::of(
            'scalar',
            'variable',
            \array_keys($definition['metas']),
            \array_values($definition['metas'])
        );
        $links = Map::of(
            'string',
            'string',
            \array_keys($definition['linkable_to']),
            \array_values($definition['linkable_to'])
        );

        foreach ($definition['properties'] as $name => $value) {
            $properties = $properties->put(
                $name,
                $this->buildProperty($name, $value)
            );
        }

        return new HttpResource(
            $context['name'],
            Url::fromString($definition['url']),
            new Identity($definition['identity']),
            $properties,
            $metas,
            $links,
            $definition['rangeable']
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return \is_array($data) && $type === HttpResource::class;
    }

    public function normalize($data, $format = null, array $context = []): array
    {
        if (!$this->supportsNormalization($data, $format)) {
            throw new LogicException;
        }

        return [
            'url' => (string) $data->url(),
            'identity' => (string) $data->identity(),
            'properties' => $data
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
                $data->metas()->keys()->toPrimitive(),
                $data->metas()->values()->toPrimitive()
            ),
            'linkable_to' => array_combine(
                $data->links()->keys()->toPrimitive(),
                $data->links()->values()->toPrimitive()
            ),
            'rangeable' => $data->isRangeable(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof HttpResource;
    }

    private function buildProperty(string $name, array $definition): Property
    {
        $variants = Set::of('string', ...\array_values($definition['variants']));

        return new Property(
            $name,
            $this->types->build($definition['type']),
            new Access(...$definition['access']),
            $variants,
            $definition['optional']
        );
    }
}
