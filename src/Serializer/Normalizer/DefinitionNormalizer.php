<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Exception\LogicException,
    Definition\HttpResource,
    Definition\Property,
    Definition\Identity,
    Definition\Access,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
};
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DefinitionNormalizer implements NormalizerInterface
{
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
}
