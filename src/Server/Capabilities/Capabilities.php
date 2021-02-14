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
use Innmind\Url\Url;
use Innmind\UrlResolver\Resolver;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    Headers,
    ProtocolVersion,
    Header\Value,
    Header\LinkValue,
    Header\Accept,
    Header\AcceptValue,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;

final class Capabilities implements CapabilitiesInterface
{
    private Transport $fulfill;
    private Url $host;
    private Resolver $resolve;
    private DefinitionFactory $make;
    private Formats $formats;
    private Url $optionsUrl;
    /** @var Set<string>|null */
    private ?Set $names = null;
    /** @var Map<string, Url> */
    private Map $paths;
    /** @var Map<string, HttpResource> */
    private Map $definitions;

    public function __construct(
        Transport $fulfill,
        Url $host,
        Resolver $resolve,
        DefinitionFactory $make,
        Formats $formats
    ) {
        $this->fulfill = $fulfill;
        $this->host = $host;
        $this->resolve = $resolve;
        $this->make = $make;
        $this->formats = $formats;
        $this->optionsUrl = $resolve($host, Url::of('/*'));
        /** @var Map<string, Url> */
        $this->paths = Map::of('string', Url::class);
        /** @var Map<string, HttpResource> */
        $this->definitions = Map::of('string', HttpResource::class);
    }

    public function names(): Set
    {
        if ($this->names instanceof Set) {
            return $this->names;
        }

        $headers = ($this->fulfill)
            (
                new Request(
                    $this->optionsUrl,
                    Method::options(),
                    new ProtocolVersion(1, 1),
                ),
            )
            ->headers();

        if (!$headers->contains('Link')) {
            return $this->names = Set::strings();
        }

        /** @var Set<LinkValue> */
        $links = $headers
            ->get('Link')
            ->values()
            ->filter(function(Value $value): bool {
                return $value instanceof LinkValue;
            });

        return $this->names = $links->mapTo(
            'string',
            function(LinkValue $link): string {
                $this->paths = ($this->paths)(
                    $link->relationship(),
                    $link->url(),
                );

                return $link->relationship();
            },
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

        $url = ($this->resolve)(
            $this->host,
            $this->paths->get($name),
        );
        /** @psalm-suppress InvalidArgument */
        $response = ($this->fulfill)(
            new Request(
                $url,
                Method::options(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    new Accept(
                        ...unwrap($this
                            ->formats
                            ->all()
                            ->values()
                            ->sort(static function(Format $a, Format $b): int {
                                return (int) ($a->priority() < $b->priority());
                            })
                            ->mapTo(
                                Value::class,
                                static fn(Format $format): AcceptValue => new AcceptValue(
                                    $format->preferredMediaType()->topLevel(),
                                    $format->preferredMediaType()->subType(),
                                ),
                            )),
                    ),
                ),
            ),
        );
        $definition = ($this->make)(
            $name,
            $url,
            $response,
        );
        $this->definitions = ($this->definitions)(
            $name,
            $definition,
        );

        return $definition;
    }

    public function definitions(): Map
    {
        $this->names()->foreach(function(string $name) {
            $this->get($name);
        });

        return $this->definitions;
    }

    public function refresh(): void
    {
        $this->names = null;
        $this->paths = $this->paths->clear();
        $this->definitions = $this->definitions->clear();
    }
}
