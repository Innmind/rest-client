<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Response;

use Innmind\Rest\Client\{
    Identity,
    Exception\IdentityNotFound,
    Visitor\ResolveIdentity,
    Definition\HttpResource,
};
use Innmind\Http\{
    Message\Response,
    Header\Location,
};
use Innmind\Url\Url;
use function Innmind\Immutable\first;

final class ExtractIdentity
{
    private ResolveIdentity $resolveIdentity;

    public function __construct(ResolveIdentity $resolveIdentity)
    {
        $this->resolveIdentity = $resolveIdentity;
    }

    public function __invoke(Response $response, HttpResource $definition): Identity
    {
        $headers = $response->headers();

        if (!$headers->contains('Location')) {
            throw new IdentityNotFound;
        }

        $header = $headers->get('Location');

        if (!$headers->get('Location') instanceof Location) {
            throw new IdentityNotFound;
        }

        $header = first($header->values());
        $header = Url::of($header->toString());

        return new Identity\Identity(
            ($this->resolveIdentity)(
                $definition->url(),
                $header
            )
        );
    }
}
