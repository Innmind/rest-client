<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Identity,
    Exception\LogicException,
    Exception\IdentityNotFound,
    Visitor\ResolveIdentity,
    Definition\HttpResource
};
use Innmind\Http\{
    Message\Response,
    Header\Location
};
use Innmind\Url\Url;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class IdentityNormalizer implements DenormalizerInterface
{
    private $resolveIdentity;

    public function __construct(ResolveIdentity $resolveIdentity)
    {
        $this->resolveIdentity = $resolveIdentity;
    }

    public function denormalize($data, $type, $format = null, array $context = []): Identity
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

        if (
            !$headers->has('Location') ||
            !$headers->get('Location') instanceof Location
        ) {
            throw new IdentityNotFound;
        }

        $header = $headers
            ->get('Location')
            ->values()
            ->current();
        $header = Url::fromString((string) $header);

        return new Identity\Identity(
            call_user_func(
                $this->resolveIdentity,
                $definition->url(),
                $header
            )
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $data instanceof Response && $type === 'rest_identity';
    }
}
