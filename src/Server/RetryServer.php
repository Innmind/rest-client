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
use Innmind\Http\Message\StatusCode;
use Innmind\Url\Url;
use Innmind\Immutable\Set;
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
    ): Set {
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

    public function update(Identity $identity, HttpResource $resource): void
    {
        try {
            $this->server->update($identity, $resource);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();
            $this->server->update($identity, $resource);
        }
    }

    public function remove(string $name, Identity $identity): void
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
    }

    /**
     * {@inheritdoc}
     */
    public function link(string $name, Identity $identity, Set $links): void
    {
        try {
            $this->server->link($name, $identity, $links);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();
            $this->server->link($name, $identity, $links);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $name, Identity $identity, Set $links): void
    {
        try {
            $this->server->unlink($name, $identity, $links);
        } catch (\Throwable $e) {
            if (!$this->shouldRetryAfter($e)) {
                throw $e;
            }

            $this->server->capabilities()->refresh();
            $this->server->unlink($name, $identity, $links);
        }
    }

    public function capabilities(): Capabilities
    {
        return $this->server->capabilities();
    }

    public function url(): Url
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
