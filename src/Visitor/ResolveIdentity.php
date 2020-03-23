<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Visitor;

use Innmind\UrlResolver\Resolver;
use Innmind\Url\Url;

final class ResolveIdentity
{
    private Resolver $resolve;

    public function __construct(Resolver $resolver)
    {
        $this->resolve = $resolver;
    }

    public function __invoke(
        Url $source,
        Url $destination
    ): string {
        $source = Url::of(\rtrim($source->toString(), '/').'/');

        $trueDestination = ($this->resolve)(
            $source,
            $destination,
        );

        return \str_replace($source->toString(), '', $trueDestination->toString());
    }
}
