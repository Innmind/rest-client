<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    IdentityInterface,
    Identity,
    Exception\LogicException
};
use Innmind\Http\{
    Message\ResponseInterface,
    Header\HeaderValueInterface,
    Header\LinkValue
};
use Innmind\Immutable\{
    Set,
    SetInterface
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class IdentitiesNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = []): SetInterface
    {
        if (!$this->supportsDenormalization($data, $type, $format)) {
            throw new LogicException;
        }

        $headers = $data->headers();

        if (!$headers->has('Link')) {
            return new Set(IdentityInterface::class);
        }

        return $headers
            ->get('Link')
            ->values()
            ->filter(function(HeaderValueInterface $link): bool {
                return $link instanceof LinkValue;
            })
            ->filter(function(LinkValue $link): bool {
                return $link->relationship() === 'resource';
            })
            ->reduce(
                new Set(IdentityInterface::class),
                function(Set $identities, LinkValue $link): Set {
                    return $identities->add(
                        new Identity(
                            basename((string) $link->url())
                        )
                    );
                }
            );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $data instanceof ResponseInterface && $type === 'rest_identities';
    }
}
