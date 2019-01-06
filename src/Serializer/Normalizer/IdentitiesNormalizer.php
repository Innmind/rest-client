<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Identity,
    Exception\LogicException,
    Definition\HttpResource,
    Visitor\ResolveIdentity,
};
use Innmind\Http\{
    Message\Response,
    Header\Value,
    Header\LinkValue,
};
use Innmind\Immutable\{
    SetInterface,
    Set,
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class IdentitiesNormalizer implements DenormalizerInterface
{
    private $resolveIdentity;

    public function __construct(ResolveIdentity $resolveIdentity)
    {
        $this->resolveIdentity = $resolveIdentity;
    }

    public function denormalize($data, $type, $format = null, array $context = []): SetInterface
    {
        if (
            !$this->supportsDenormalization($data, $type, $format) ||
            !isset($context['definition']) ||
            !$context['definition'] instanceof HttpResource
        ) {
            throw new LogicException;
        }

        $definition = $context['definition'];
        $headers = $data->headers();

        if (!$headers->has('Link')) {
            return new Set(Identity::class);
        }

        return $headers
            ->get('Link')
            ->values()
            ->filter(function(Value $link): bool {
                return $link instanceof LinkValue;
            })
            ->filter(function(LinkValue $link): bool {
                return $link->relationship() === 'resource';
            })
            ->reduce(
                new Set(Identity::class),
                function(Set $identities, LinkValue $link) use ($definition): Set {
                    return $identities->add(
                        new Identity\Identity(
                            ($this->resolveIdentity)(
                                $definition->url(),
                                $link->url()
                            )
                        )
                    );
                }
            );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $data instanceof Response && $type === 'rest_identities';
    }
}
