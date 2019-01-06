<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Server\DefinitionFactory,
    Definition\HttpResource,
    Exception\InvalidArgumentException,
    Format\Format,
    Formats,
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\{
    UrlInterface,
    Url,
};
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method\Method,
    Headers\Headers,
    ProtocolVersion\ProtocolVersion,
    Header\Value,
    Header\LinkValue,
    Header\Accept,
    Header\AcceptValue,
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Map,
    Set,
};

final class Capabilities implements CapabilitiesInterface
{
    private $fulfill;
    private $host;
    private $resolver;
    private $factory;
    private $formats;
    private $optionsUrl;
    private $names;
    private $paths;
    private $definitions;

    public function __construct(
        Transport $fulfill,
        UrlInterface $host,
        ResolverInterface $resolver,
        DefinitionFactory $factory,
        Formats $formats
    ) {
        $this->fulfill = $fulfill;
        $this->host = $host;
        $this->resolver = $resolver;
        $this->factory = $factory;
        $this->formats = $formats;
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

        $headers = ($this->fulfill)
            (
                new Request(
                    $this->optionsUrl,
                    Method::options(),
                    new ProtocolVersion(1, 1)
                )
            )
            ->headers();

        if (!$headers->has('Link')) {
            return $this->names = new Set('string');
        }

        return $this->names = $headers
            ->get('Link')
            ->values()
            ->filter(function(Value $value): bool {
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
        $response = ($this->fulfill)(
            new Request(
                $url,
                Method::options(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    new Accept(
                        ...$this
                            ->formats
                            ->all()
                            ->values()
                            ->sort(function(Format $a, Format $b): bool {
                                return $a->priority() < $b->priority();
                            })
                            ->reduce(
                                new Set(Value::class),
                                function(Set $values, Format $format): Set {
                                    return $values->add(new AcceptValue(
                                        $format->preferredMediaType()->topLevel(),
                                        $format->preferredMediaType()->subType()
                                    ));
                                }
                            )
                    )
                )
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
