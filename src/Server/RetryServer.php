<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    ServerInterface,
    IdentityInterface,
    HttpResource,
    Server\CapabilitiesInterface,
    Request\Range
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    MapInterface
};
use Innmind\Specification\SpecificationInterface;

/**
 * This implementation will retry a failed request once after it has refreshed
 * the server capabilities in order to make sure the resource definitions are
 * up-to-date
 */
final class RetryServer implements ServerInterface
{
    private $server;

    public function __construct(ServerInterface $server)
    {
        $this->server = $server;
    }

    /**
     * {@iinheritdoc}
     */
    public function all(
        string $name,
        SpecificationInterface $specification = null,
        Range $range = null
    ): SetInterface {
        try {
            return $this->server->all($name, $specification, $range);
        } catch (\Throwable $e) {
            $this->server->capabilities()->refresh();

            return $this->server->all($name, $specification, $range);
        }
    }

    public function read(string $name, IdentityInterface $identity): HttpResource
    {
        try {
            return $this->server->read($name, $identity);
        } catch (\Throwable $e) {
            $this->server->capabilities()->refresh();

            return $this->server->read($name, $identity);
        }
    }

    public function create(HttpResource $resource): IdentityInterface
    {
        try {
            return $this->server->create($resource);
        } catch (\Throwable $e) {
            $this->server->capabilities()->refresh();

            return $this->server->create($resource);
        }
    }

    public function update(
        IdentityInterface $identity,
        HttpResource $resource
    ): ServerInterface {
        try {
            $this->server->update($identity, $resource);
        } catch (\Throwable $e) {
            $this->server->capabilities()->refresh();
            $this->server->update($identity, $resource);
        } finally {
            return $this;
        }
    }

    public function remove(string $name, IdentityInterface $identity): ServerInterface
    {
        try {
            $this->server->remove($name, $identity);
        } catch (\Throwable $e) {
            $this->server->capabilities()->refresh();
            $this->server->remove($name, $identity);
        } finally {
            return $this;
        }
    }

    public function capabilities(): CapabilitiesInterface
    {
        return $this->server->capabilities();
    }

    public function url(): UrlInterface
    {
        return $this->server->url();
    }
}
