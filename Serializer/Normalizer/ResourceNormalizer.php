<?php

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\HttpResourceInterface;
use Innmind\Rest\Client\Server\HttpResource as ServerResource;
use Innmind\Rest\Client\Server\HttpResourceInterface as ServerResourceInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Exception\LogicException;

class ResourceNormalizer implements DenormalizerInterface, NormalizerInterface
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

        return new ServerResource(
            $context['definition'],
            $data['resource']['properties'],
            $data['resource']['subResources'],
            $context['client']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($type !== ServerResourceInterface::class) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        if (!isset($data['resource'])) {
            return false;
        }

        if (!is_array($data['resource'])) {
            return false;
        }

        if (!isset($data['resource']['properties'])) {
            return false;
        }

        if (!is_array($data['resource']['properties'])) {
            return false;
        }

        if (!isset($data['resource']['subResources'])) {
            return false;
        }

        if (!is_array($data['resource']['subResources'])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (
            isset($context['is_sub_resource']) &&
            $context['is_sub_resource'] === true &&
            is_array($object)
        ) {
            return $object;
        }

        if (!isset($context['definition']) || !isset($context['action'])) {
            throw new LogicException(
                'You must pass the definition and action in the context'
            );
        }

        $definition = $context['definition'];
        $normalized = [];

        foreach ($definition->getProperties() as $name => $prop) {
            if (!$object->has($name)) {
                $found = false;

                foreach ($prop->getVariants() as $variant) {
                    if ($object->has($variant)) {
                        $name = $variant;
                        $found = true;
                        break;
                    }
                }

                if ($found === false) {
                    continue;
                }
            }

            if (!$prop->hasAccess($context['action'])) {
                continue;
            }

            $data = $object->get($name);

            if (
                $prop->getType() === 'date' &&
                $data instanceof \DateTime
            ) {
                $data = $data->format(\DateTime::ISO8601);
            } else if ($prop->containsResource()) {
                $context['is_sub_resource'] = true;
                $context['definition'] = $prop->getResource();

                if ($prop->getType() === 'array') {
                    $collection = [];

                    foreach ($data as $subResource) {
                        $collection[] = $this->normalize(
                            $subResource,
                            $format,
                            $context
                        );
                    }

                    $data = $collection;
                } else {
                    $data = $this->normalize($data, $format, $context);
                }

                unset($context['is_sub_resource']);
                $context['definition'] = $definition;
            }

            $normalized[(string) $prop] = $data;
        }

        if (
            isset($context['is_sub_resource']) &&
            $context['is_sub_resource'] === true
        ) {
            return $normalized;
        } else {
            return ['resource' => $normalized];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof HttpResourceInterface;
    }
}
