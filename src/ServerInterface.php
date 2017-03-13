<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Server\CapabilitiesInterface,
    Request\Range
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    MapInterface
};
use Innmind\Specification\SpecificationInterface;

interface ServerInterface
{
    /**
     * @return SetInterface<IdentityInterface>
     */
    public function all(
        string $name,
        SpecificationInterface $specification = null,
        Range $range = null
    ): SetInterface;
    public function read(string $name, IdentityInterface $identity): HttpResource;
    public function create(HttpResource $resource): IdentityInterface;
    public function update(
        IdentityInterface $identity,
        HttpResource $resource
    ): self;
    public function remove(string $name, IdentityInterface $identity): self;

    /**
     * @param SetInterface<Link> $links
     */
    public function link(
        string $name,
        IdentityInterface $identity,
        SetInterface $links
    ): self;

    /**
     * @param SetInterface<Link> $links
     */
    public function unlink(
        string $name,
        IdentityInterface $identity,
        SetInterface $links
    ): self;
    public function capabilities(): CapabilitiesInterface;
    public function url(): UrlInterface;
}
