<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Exception\LogicException,
    Definition\HttpResource,
    Definition\Property,
    Definition\Identity,
    Definition\Access,
    Definition\Types
};
use Innmind\Immutable\{
    Map,
    Set
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DefinitionNormalizer implements DenormalizerInterface
{
    private $types;

    public function __construct(Types $types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($definition, $class, $format = null, array $context = [])
    {
        if (
            !$this->supportsDenormalization($definition, $class) ||
            !isset($context['name'])
        ) {
            throw new LogicException;
        }

        $properties = new Map('string', Property::class);
        $metas = new Map('scalar', 'variable');

        foreach ($definition['properties'] as $name => $value) {
            $properties = $properties->put(
                $name,
                $this->buildProperty($name, $value)
            );
        }

        foreach ($definition['metas'] as $key => $value) {
            $metas = $metas->put($key, $value);
        }

        return new HttpResource(
            $context['name'],
            new Identity($definition['identity']),
            $properties,
            $metas,
            $definition['rangeable']
        );
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && $type === HttpResource::class;
    }

    private function buildProperty(string $name, array $definition): Property
    {
        $mask = new Set('string');
        $variants = new Set('string');

        foreach ($definition['access'] as $access) {
            $mask = $mask->add($access);
        }

        foreach ($definition['variants'] as $variant) {
            $variants = $variants->add($variant);
        }

        return new Property(
            $name,
            $this->types->build($definition['type']),
            new Access($mask),
            $variants,
            $definition['optional']
        );
    }
}
