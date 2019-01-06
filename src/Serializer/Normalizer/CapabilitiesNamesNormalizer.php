<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\Exception\LogicException;
use Innmind\Immutable\{
    SetInterface,
    Set,
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CapabilitiesNamesNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = []): SetInterface
    {
        if (!$this->supportsDenormalization($data, $type)) {
            throw new LogicException;
        }

        return Set::of('string', ...\array_values($data));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return \is_array($data) && $type === 'capabilities_names';
    }
}
