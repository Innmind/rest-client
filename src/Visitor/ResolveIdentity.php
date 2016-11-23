<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Visitor;

use Innmind\UrlResolver\ResolverInterface;
use Innmind\Url\UrlInterface;

final class ResolveIdentity
{
    private $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    public function __invoke(
        UrlInterface $source,
        UrlInterface $destination
    ): string {
        $source = (string) $source;
        $source = rtrim($source, '/').'/';

        $trueDestination = $this->resolver->resolve(
            $source,
            (string) $destination
        );

        return str_replace($source, '', $trueDestination);
    }
}
