<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Server\CapabilitiesInterface,
    Request\Range
};
use Innmind\Immutable\{
    SetInterface,
    MapInterface
};
use Innmind\Specification\SpecificationInterface;

interface ServerInterface
{
    /**
     * @return SetInterface<HttpResource>
     */
    public function all(
        string $name,
        SpecificationInterface $specification = null,
        Range $range = null
    ): SetInterface;
    public function read(string $name, IdentityInterface $identity): HttpResource;
    public function create(string $name, HttpResource $resource): IdentityInterface;
    public function update(
        string $name,
        IdentityInterface $identity,
        HttpResource $resource
    ): self;
    public function remove(string $name, IdentityInterface $identity): self;
    public function capabilities(): CapabilitiesInterface;
}
