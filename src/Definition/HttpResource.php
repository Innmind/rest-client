<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Link,
    Exception\DomainException,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};
use function Innmind\Immutable\{
    assertMap,
    assertSet,
};

final class HttpResource
{
    private string $name;
    private Url $url;
    private Identity $identity;
    /** @var Map<string, Property> */
    private Map $properties;
    /** @var Map<scalar, scalar|array> */
    private Map $metas;
    /** @var Set<AllowedLink> */
    private Set $links;
    private bool $rangeable;

    /**
     * @param Map<string, Property> $properties
     * @param Map<scalar, scalar|array> $metas
     * @param Set<AllowedLink> $links
     */
    public function __construct(
        string $name,
        Url $url,
        Identity $identity,
        Map $properties,
        Map $metas,
        Set $links,
        bool $rangeable
    ) {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        assertMap('string', Property::class, $properties, 4);
        assertMap('scalar', 'scalar|array', $metas, 5);
        assertSet(AllowedLink::class, $links, 6);

        $this->name = $name;
        $this->url = $url;
        $this->identity = $identity;
        $this->properties = $properties;
        $this->metas = $metas;
        $this->links = $links;
        $this->rangeable = $rangeable;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    /**
     * @return Map<string, Property>
     */
    public function properties(): Map
    {
        return $this->properties;
    }

    /**
     * @return Map<scalar, scalar|array>
     */
    public function metas(): Map
    {
        return $this->metas;
    }

    /**
     * @return Set<AllowedLink>
     */
    public function links(): Set
    {
        return $this->links;
    }

    public function allowsLink(Link $link): bool
    {
        return $this->links->reduce(
            false,
            static function(bool $allows, AllowedLink $allowed) use ($link): bool {
                return $allows || $allowed->allows($link);
            },
        );
    }

    public function isRangeable(): bool
    {
        return $this->rangeable;
    }

    public function toString(): string
    {
        return $this->name;
    }
}
