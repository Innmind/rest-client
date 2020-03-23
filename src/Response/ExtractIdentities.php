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

    /**
     * @return Set<Identity>
     */
    public function __invoke(Response $response, HttpResource $definition): Set
    {
        $headers = $response->headers();

        if (!$headers->contains('Link')) {
            return Set::of(Identity::class);
        }

        /** @var Set<LinkValue> */
        $links = $headers
            ->get('Link')
            ->values()
            ->filter(function(Value $link): bool {
                return $link instanceof LinkValue;
            });

        /** @var Set<Identity> */
        return $links
            ->filter(function(LinkValue $link): bool {
                return $link->relationship() === 'resource';
            })
            ->mapTo(
                Identity::class,
                fn(LinkValue $link): Identity => new Identity\Identity(
                    ($this->resolveIdentity)(
                        $definition->url(),
                        $link->url(),
                    ),
                ),
            );
    }
}
