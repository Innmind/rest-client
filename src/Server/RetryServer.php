<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server as ServerInterface,
    Identity,
    HttpResource,
    Request\Range,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use Innmind\HttpTransport\Exception\ClientError;
use Innmind\Http\Message\StatusCode\StatusCode;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\SetInterface;
use Innmind\Specification\Specification;

/**
 * This implementation will retry a failed request once after it has refreshed
 * the server capabilities in order to make sure the resource definitions are
 * up-to-date
 */
final class RetryServer implements ServerInterface
{
    private ServerInterface $server;

    public function __construct(ServerInterface $server)
    {
        $this->server = $server;
    }

    /**
     * {@iinheritdoc}
     */
    public function all(
        string $name,
        Specification $specification = null,
        Range $range = null
    ): SetInterface {
        try {
            return $this->server->all($name, $specification, $range);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();

            return $this->server->all($name, $specification, $range);
        }
    }

    public function read(string $name, Identity $identity): HttpResource
    {
        try {
            return $this->server->read($name, $identity);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();

            return $this->server->read($name, $identity);
        }
    }

    public function create(HttpResource $resource): Identity
    {
        try {
            return $this->server->create($resource);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();

            return $this->server->create($resource);
        }
    }

    public function update(
        Identity $identity,
        HttpResource $resource
    ): ServerInterface {
        try {
            $this->server->update($identity, $resource);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();
            $this->server->update($identity, $resource);
        }

        return $this;
    }

    public function remove(string $name, Identity $identity): ServerInterface
    {
        try {
            $this->server->remove($name, $identity);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();
            $this->server->remove($name, $identity);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function link(
        string $name,
        Identity $identity,
        SetInterface $links
    ): ServerInterface {
        try {
            $this->server->link($name, $identity, $links);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();
            $this->server->link($name, $identity, $links);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(
        string $name,
        Identity $identity,
        SetInterface $links
    ): ServerInterface {
        try {
            $this->server->unlink($name, $identity, $links);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();
            $this->server->unlink($name, $identity, $links);
        }

        return $this;
    }

    public function capabilities(): Capabilities
    {
        return $this->server->capabilities();
    }

    public function url(): UrlInterface
    {
        return $this->server->url();
    }

    private function shouldRetryAfter(\Throwable $e): bool
    {
        if (
            $e instanceof NormalizationException ||
            $e instanceof DenormalizationException
        ) {
            return true;
        }

        if (!$e instanceof ClientError) {
            return false;
        }

        return $e->response()->statusCode()->value() === StatusCode::codes()->get('BAD_REQUEST');
    }
}
