<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Link,
    Exception\DomainException,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Str,
};

final class HttpResource
{
    private string $name;
    private UrlInterface $url;
    private Identity $identity;
    private MapInterface $properties;
    private MapInterface $metas;
    private SetInterface $links;
    private bool $rangeable;

    public function __construct(
        string $name,
        UrlInterface $url,
        Identity $identity,
        MapInterface $properties,
        MapInterface $metas,
        SetInterface $links,
        bool $rangeable
    ) {
        if (Str::of($name)->empty()) {
            throw new DomainException;
        }

        if (
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== Property::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 4 must be of type MapInterface<string, %s>',
                Property::class
            ));
        }

        if (
            (string) $metas->keyType() !== 'scalar' ||
            (string) $metas->valueType() !== 'variable'
        ) {
            throw new \TypeError('Argument 5 must be of type MapInterface<scalar, variable>');
        }

        if ((string) $links->type() !== AllowedLink::class) {
            throw new \TypeError(\sprintf(
                'Argument 6 must be of type SetInterface<%s>',
                AllowedLink::class
            ));
        }

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

    public function url(): UrlInterface
    {
        return $this->url;
    }

    public function identity(): Identity
    {
        return $this->identity;
    }

    /**
     * @return MapInterface<string, Property>
     */
    public function properties(): MapInterface
    {
        return $this->properties;
    }

    /**
     * @return MapInterface<scalar, variable>
     */
    public function metas(): MapInterface
    {
        return $this->metas;
    }

    /**
     * @return SetInterface<AllowedLink>
     */
    public function links(): SetInterface
    {
        return $this->links;
    }

    public function allowsLink(Link $link): bool
    {
        return $this->links->reduce(
            false,
            static function(bool $allows, AllowedLink $allowed) use ($link): bool {
                return $allows || $allowed->allows($link);
            }
        );
    }

    public function isRangeable(): bool
    {
        return $this->rangeable;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
