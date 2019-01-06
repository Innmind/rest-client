<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Server\Capabilities,
    Request\Range,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use Innmind\Specification\Specification;

interface Server
{
    /**
     * @return SetInterface<Identity>
     */
    public function all(
        string $name,
        Specification $specification = null,
        Range $range = null
    ): SetInterface;
    public function read(string $name, Identity $identity): HttpResource;
    public function create(HttpResource $resource): Identity;
    public function update(
        Identity $identity,
        HttpResource $resource
    ): self;
    public function remove(string $name, Identity $identity): self;

    /**
     * @param SetInterface<Link> $links
     */
    public function link(
        string $name,
        Identity $identity,
        SetInterface $links
    ): self;

    /**
     * @param SetInterface<Link> $links
     */
    public function unlink(
        string $name,
        Identity $identity,
        SetInterface $links
    ): self;
    public function capabilities(): Capabilities;
    public function url(): UrlInterface;
}
