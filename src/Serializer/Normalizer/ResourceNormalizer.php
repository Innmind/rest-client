<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Definition\HttpResource as ResourceDefinition,
    Definition\Property as PropertyDefinition,
    Definition\Access,
    HttpResource,
    HttpResource\Property,
    Exception\LogicException,
    Exception\MissingPropertyException
};
use Innmind\Immutable\Map;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ResourceNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = []): HttpResource
    {
        if (
            !$this->supportsDenormalization($data, $type, $format) ||
            !isset($context['definition']) ||
            !$context['definition'] instanceof ResourceDefinition ||
            !isset($context['access']) ||
            !$context['access'] instanceof Access
        ) {
            throw new LogicException;
        }

        $definition = $context['definition'];
        $access = $context['access'];
        $data = $data['resource'];

        $properties = $definition
            ->properties()
            ->reduce(
                new Map('string', Property::class),
                function(Map $properties, string $name, PropertyDefinition $property) use ($data, $access): Map {
                    if (!$property->access()->matches($access)) {
                        return $properties;
                    }

                    if ($property->isOptional() && !isset($data[$name])) {
                        return $properties;
                    }

                    if (!isset($data[$name])) {
                        throw new MissingPropertyException($name);
                    }

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

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return is_array($data) &&
            isset($data['resource']) &&
            is_array($data['resource']) &&
            $type === HttpResource::class;
    }
}
