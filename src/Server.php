<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Server\Capabilities,
    Request\Range,
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;
use Innmind\Specification\Specification;

interface Server
{
    /**
     * @return Set<Identity>
     */
    public function all(
        string $name,
        Specification $specification = null,
        Range $range = null
    ): Set;
    public function read(string $name, Identity $identity): HttpResource;
    public function create(HttpResource $resource): Identity;
    public function update(
        Identity $identity,
        HttpResource $resource
    ): self;
    public function remove(string $name, Identity $identity): self;

    /**
     * @param Set<Link> $links
     */
    public function link(
        string $name,
        Identity $identity,
        Set $links
    ): self;

    /**
     * @param Set<Link> $links
     */
    public function unlink(
        string $name,
        Identity $identity,
        Set $links
    ): self;
    public function capabilities(): Capabilities;
    public function url(): Url;
}
