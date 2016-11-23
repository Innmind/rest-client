<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Definition\HttpResource,
    Exception\InvalidArgumentException
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Http\{
    Message\Request,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\LinkValue
};
use Innmind\Filesystem\Stream\NullStream;
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Map,
    Set
};

final class Capabilities implements CapabilitiesInterface
{
    private $transport;
    private $host;
    private $resolver;
    private $factory;
    private $optionsUrl;
    private $names;
    private $paths;
    private $definitions;

    public function __construct(
        TransportInterface $transport,
        UrlInterface $host,
        ResolverInterface $resolver,
        DefinitionFactory $factory
    ) {
        $this->transport = $transport;
        $this->host = $host;
        $this->resolver = $resolver;
        $this->factory = $factory;
        $optionsUrl = $resolver->resolve((string) $host, '/*');
        $this->optionsUrl = Url::fromString($optionsUrl);
        $this->paths = new Map('string', UrlInterface::class);
        $this->definitions = new Map('string', HttpResource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function names(): SetInterface
    {
        if ($this->names instanceof SetInterface) {
            return $this->names;
        }

        $headers = $this
            ->transport
            ->fulfill(
                new Request(
                    $this->optionsUrl,
                    new Method(Method::OPTIONS),
                    new ProtocolVersion(1, 1),
                    new Headers(
                        new Map('string', HeaderInterface::class)
                    ),
                    new NullStream
                )
            )
            ->headers();

        if (!$headers->has('Link')) {
            return $this->names = new Set('string');
        }

        return $this->names = $headers
            ->get('Link')
            ->values()
            ->filter(function(HeaderValueInterface $value): bool {
                return $value instanceof LinkValue;
            })
            ->reduce(
                new Set('string'),
                function(Set $names, LinkValue $link): Set {
                    $this->paths = $this->paths->put(
                        $link->relationship(),
                        $link->url()
                    );

                    return $names->add($link->relationship());
                }
            );
    }

    public function get(string $name): HttpResource
    {
        if (!$this->names()->contains($name)) {
            throw new InvalidArgumentException;
        }

        if ($this->definitions->contains($name)) {
            return $this->definitions->get($name);
        }

        $url = $this->resolver->resolve(
            (string) $this->host,
            (string) $this->paths->get($name)
        );
        $url = Url::fromString($url);
        $response = $this->transport->fulfill(
            new Request(
                $url,
                new Method(Method::OPTIONS),
                new ProtocolVersion(1, 1),
                new Headers(
                    new Map('string', HeaderInterface::class)
                ),
                new NullStream
            )
        );
        $definition = $this->factory->make(
            $name,
            $url,
            $response
        );
        $this->definitions = $this->definitions->put(
            $name,
            $definition
        );

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function definitions(): MapInterface
    {
        $this->names()->foreach(function(string $name) {
            $this->get($name);
        });

        return $this->definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(): CapabilitiesInterface
    {
        $this->names = null;
        $this->paths = $this->paths->clear();
        $this->definitions = $this->definitions->clear();

        return $this;
    }
}
