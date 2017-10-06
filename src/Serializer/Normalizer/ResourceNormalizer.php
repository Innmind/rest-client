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
    Exception\MissingProperty
};
use Innmind\Immutable\Map;
use Symfony\Component\Serializer\Normalizer\{
    DenormalizerInterface,
    NormalizerInterface
};

final class ResourceNormalizer implements DenormalizerInterface, NormalizerInterface
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

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return is_array($data) &&
            isset($data['resource']) &&
            is_array($data['resource']) &&
            $type === HttpResource::class;
    }

    public function normalize($data, $format = null, array $context = []): array
    {
        if (
            !$this->supportsNormalization($data, $format) ||
            !isset($context['definition']) ||
            !$context['definition'] instanceof ResourceDefinition ||
            !isset($context['access']) ||
            !$context['access'] instanceof Access
        ) {
            throw new LogicException;
        }

        $definition = $context['definition'];
        $access = $context['access'];

        $properties = $definition
            ->properties()
            ->filter(function(string $name, PropertyDefinition $property) use ($access): bool {
                return $property->access()->matches($access);
            })
            ->filter(function(string $name, PropertyDefinition $property) use ($data): bool {
                if (!$property->isOptional()) {
                    return true;
                }

                $name = $this->resolveName($property, $data);

                return $data->properties()->contains($name);
            })
            ->foreach(function(string $name, PropertyDefinition $property) use ($data) {
                $name = $this->resolveName($property, $data);

                if (!$data->properties()->contains($name)) {
                    throw new MissingProperty($name);
                }
            })
            ->reduce(
                [],
                function(array $properties, string $name, PropertyDefinition $property) use ($data): array {
                    $usedName = $this->resolveName($property, $data);

                    $properties[$name] = $property
                        ->type()
                        ->normalize(
                            $data
                                ->properties()
                                ->get($usedName)
                                ->value()
                        );

                    return $properties;
                }
            );

        return ['resource' => $properties];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof HttpResource;
    }

    private function resolveName(
        PropertyDefinition $property,
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
