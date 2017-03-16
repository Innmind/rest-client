<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Link,
    Exception\InvalidArgumentException
};
use Innmind\url\UrlInterface;
use Innmind\Immutable\MapInterface;

final class HttpResource
{
    private $name;
    private $url;
    private $identity;
    private $properties;
    private $metas;
    private $links;
    private $rangeable;

    public function __construct(
        string $name,
        UrlInterface $url,
        Identity $identity,
        MapInterface $properties,
        MapInterface $metas,
        MapInterface $links,
        bool $rangeable
    ) {
        if (
            empty($name) ||
            (string) $properties->keyType() !== 'string' ||
            (string) $properties->valueType() !== Property::class ||
            (string) $metas->keyType() !== 'scalar' ||
            (string) $metas->valueType() !== 'variable' ||
            (string) $links->keyType() !== 'string' ||
            (string) $links->valueType() !== 'string'
        ) {
            throw new InvalidArgumentException;
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
     * @return MapInterface<string, string>
     */
    public function links(): MapInterface
    {
        return $this->links;
    }

    public function allowsLink(Link $link): bool
    {
        if (!$this->links->contains($link->relationship())) {
            return false;
        }

        return $this->links->get($link->relationship()) === $link->definition();
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
