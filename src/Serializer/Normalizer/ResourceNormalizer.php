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
    Exception\MissingProperty,
};
use Innmind\Immutable\Map;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ResourceNormalizer implements NormalizerInterface
{
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
