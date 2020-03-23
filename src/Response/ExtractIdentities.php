<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Response;

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
use Innmind\Immutable\Set;

final class ExtractIdentities
{
    private ResolveIdentity $resolveIdentity;

    public function __construct(ResolveIdentity $resolveIdentity)
    {
        $this->resolveIdentity = $resolveIdentity;
    }

    public function __invoke(Response $response, HttpResource $definition): Set
    {
        $headers = $response->headers();

        if (!$headers->contains('Link')) {
            return Set::of(Identity::class);
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
                Set::of(Identity::class),
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
}
