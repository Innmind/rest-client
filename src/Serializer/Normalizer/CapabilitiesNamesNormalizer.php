<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\Exception\LogicException;
use Innmind\Immutable\Set;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CapabilitiesNamesNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $type)) {
            throw new LogicException;
        }

        $set = new Set('string');

        foreach ($data as $value) {
            $set = $set->add($value);
        }

        return $set;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_array($data) && $type === 'capabilities_names';
    }
}
