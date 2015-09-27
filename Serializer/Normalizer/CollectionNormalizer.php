<?php

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\Server\Collection;
use Innmind\Rest\Client\Server\CollectionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\LogicException;

class CollectionNormalizer implements DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!isset($context['client']) || !isset($context['definition'])) {
            throw new LogicException(
                'You must pass the client and definition in the context'
            );
        }

        return new Collection(
            $context['definition'],
            $data['resources'],
            $data['next'],
            $context['client']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if (
            $type !== Collection::class &&
            $type !== CollectionInterface::class
        ) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        if (!array_key_exists('next', $data)) {
            return false;
        }

        if (!isset($data['resources'])) {
            return false;
        }

        return is_array($data['resources']);
    }
}
