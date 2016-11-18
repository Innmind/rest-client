<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    IdentityInterface,
    Identity,
    Exception\LogicException,
    Exception\IdentityNotFoundException
};
use Innmind\Http\{
    Message\ResponseInterface,
    Header\Location
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class IdentityNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = []): IdentityInterface
    {
        if (!$this->supportsDenormalization($data, $type, $format)) {
            throw new LogicException;
        }

        $headers = $data->headers();

        if (
            !$headers->has('Location') ||
            !$headers->get('Location') instanceof Location
        ) {
            throw new IdentityNotFoundException;
        }

        return new Identity(
            basename(
                (string) $headers
                    ->get('Location')
                    ->values()
                    ->current()
            )
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $data instanceof ResponseInterface && $type === 'rest_identity';
    }
}
