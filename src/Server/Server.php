<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server as ServerInterface,
    Request\Range,
    Identity,
    HttpResource,
    Translator\SpecificationTranslator,
    Exception\ResourceNotRangeable,
    Exception\UnsupportedResponse,
    Exception\DomainException,
    Exception\NormalizationException,
    Definition\Access,
    Formats,
    Format\Format,
    Link,
    Link\Parameter,
    Definition\HttpResource as Definition,
    Response\ExtractIdentity,
    Response\ExtractIdentities,
    Serializer\Denormalizer\DenormalizeResource,
    Serializer\Normalizer\NormalizeResource,
    Serializer\Encode,
    Serializer\Decode,
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;
use Innmind\UrlResolver\Resolver;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header,
    Header\Value,
    Header\Range as RangeHeader,
    Header\RangeValue,
    Header\Accept,
    Header\AcceptValue,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\Parameter as HeaderParameter,
    Header\Link as LinkHeader,
    Header\LinkValue,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\{
    unwrap,
    join,
    assertSet,
};
use Innmind\Specification\Specification;

final class Server implements ServerInterface
{
    private Url $url;
    private Transport $fulfill;
    private Capabilities $capabilities;
    private Resolver $resolve;
    private ExtractIdentity $extractIdentity;
    private ExtractIdentities $extractIdentities;
    private DenormalizeResource $denormalizeResource;
    private NormalizeResource $normalizeResource;
    private Encode $encode;
    private Decode $decode;
    private SpecificationTranslator $translate;
    private Formats $formats;

    public function __construct(
        Url $url,
        Transport $fulfill,
        Capabilities $capabilities,
        Resolver $resolver,
        ExtractIdentity $extractIdentity,
        ExtractIdentities $extractIdentities,
        DenormalizeResource $denormalizeResource,
        NormalizeResource $normalizeResource,
        Encode $encode,
        Decode $decode,
        SpecificationTranslator $translate,
        Formats $formats
    ) {
        $this->url = $url;
        $this->fulfill = $fulfill;
        $this->capabilities = $capabilities;
        $this->resolve = $resolver;
        $this->extractIdentity = $extractIdentity;
        $this->extractIdentities = $extractIdentities;
        $this->denormalizeResource = $denormalizeResource;
        $this->normalizeResource = $normalizeResource;
        $this->encode = $encode;
        $this->decode = $decode;
        $this->translate = $translate;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function all(
        string $name,
        Specification $specification = null,
        Range $range = null
    ): Set {
        $definition = $this->capabilities->get($name);

        if ($range !== null && !$definition->isRangeable()) {
            throw new ResourceNotRangeable;
        }

        $url = $definition->url();

        if ($specification !== null) {
            $query = Url::of('?'.($this->translate)($specification));
            $url = ($this->resolve)(
                $definition->url(),
                $query,
            );
        }

        $headers = Headers::of();

        if ($range !== null) {
            $headers = Headers::of(
                new RangeHeader(
                    new RangeValue(
                        'resource',
                        $range->firstPosition(),
                        $range->lastPosition(),
                    ),
                ),
            );
        }

        $response = ($this->fulfill)(
            new Request(
                $url,
                Method::get(),
                new ProtocolVersion(1, 1),
                $headers,
            ),
        );

        return ($this->extractIdentities)($response, $definition);
    }

    public function read(string $name, Identity $identity): HttpResource
    {
        $definition = $this->capabilities->get($name);
        $response = ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity,
                ),
                Method::get(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    $this->generateAcceptHeader(),
                ),
            ),
        );

        try {
            $format = $this->formats->matching(
                join(
                    ', ',
                    $response
                        ->headers()
                        ->get('Content-Type')
                        ->values()
                        ->mapTo(
                            'string',
                            static fn(Value $value): string => $value->toString(),
                        ),
                )->toString(),
            );
        } catch (\Exception $e) {
            throw new UnsupportedResponse('', 0, $e);
        }

        $data = ($this->decode)(
            $format->name(),
            $response->body(),
        );

        return ($this->denormalizeResource)(
            $data,
            $definition,
            new Access(Access::READ),
        );
    }

    public function create(HttpResource $resource): Identity
    {
        $definition = $this->capabilities->get($resource->name());
        $response = ($this->fulfill)(
            new Request(
                $definition->url(),
                Method::post(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    ContentType::of('application', 'json'),
                    $this->generateAcceptHeader(),
                ),
                ($this->encode)(
                    ($this->normalizeResource)(
                        $resource,
                        $definition,
                        new Access(Access::CREATE),
                    ),
                ),
            ),
        );

        return ($this->extractIdentity)($response, $definition);
    }

    public function update(Identity $identity, HttpResource $resource): void
    {
        $definition = $this->capabilities->get($resource->name());
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity,
                ),
                Method::put(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    ContentType::of('application', 'json'),
                    $this->generateAcceptHeader(),
                ),
                ($this->encode)(
                    ($this->normalizeResource)(
                        $resource,
                        $definition,
                        new Access(Access::UPDATE),
                    ),
                ),
            ),
        );
    }

    public function remove(string $name, Identity $identity): void
    {
        $definition = $this->capabilities->get($name);
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity,
                ),
                Method::delete(),
                new ProtocolVersion(1, 1),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function link(string $name, Identity $identity, Set $links): void
    {
        assertSet(Link::class, $links, 3);

        if ($links->empty()) {
            throw new DomainException;
        }

        $definition = $this->capabilities->get($name);
        $this->validateLinks($definition, $links);
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity,
                ),
                Method::link(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    $this->generateAcceptHeader(),
                    $this->generateLinkHeader($links),
                ),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unlink(string $name, Identity $identity, Set $links): void
    {
        assertSet(Link::class, $links, 3);

        if ($links->empty()) {
            throw new DomainException;
        }

        $definition = $this->capabilities->get($name);
        $this->validateLinks($definition, $links);
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity,
                ),
                Method::unlink(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    $this->generateAcceptHeader(),
                    $this->generateLinkHeader($links),
                ),
            ),
        );
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }

    public function url(): Url
    {
        return $this->url;
    }

    private function resolveUrl(Url $url, Identity $identity): Url
    {
        $url = \rtrim($url->toString(), '/').'/'.$identity->toString();

        return Url::of($url);
    }

    private function generateAcceptHeader(): Accept
    {
        return new Accept(
            ...unwrap($this
                ->formats
                ->all()
                ->values()
                ->sort(function(Format $a, Format $b): int {
                    return (int) ($a->priority() < $b->priority());
                })
                ->mapTo(
                    Value::class,
                    static fn(Format $format): AcceptValue => new AcceptValue(
                        $format->preferredMediaType()->topLevel(),
                        $format->preferredMediaType()->subType()
                    ),
                )),
        );
    }

    private function generateLinkHeader(Set $links): LinkHeader
    {
        return new LinkHeader(
            ...unwrap($links->mapTo(
                Value::class,
                function(Link $link): LinkValue {
                    $url = $this->resolveUrl(
                        $this
                            ->capabilities
                            ->get($link->definition())
                            ->url(),
                        $link->identity()
                    );
                    /** @var Set<HeaderParameter> */
                    $parameters = $link
                        ->parameters()
                        ->toSetOf(
                            HeaderParameter::class,
                            static fn(string $name, Parameter $parameter): \Generator => yield new HeaderParameter\Parameter(
                                $parameter->key(),
                                $parameter->value(),
                            ),
                        );

                    return new LinkValue(
                        Url::of($url->path()->toString()),
                        $link->relationship(),
                        ...unwrap($parameters)
                    );
                },
            )),
        );
    }

    /**
     * @param Set<link> $links
     *
     * @throws NormalizationException
     */
    private function validateLinks(Definition $definition, Set $links): void
    {
        $links->foreach(function(Link $link) use ($definition): void {
            if (!$definition->allowsLink($link)) {
                throw new NormalizationException;
            }
        });
    }
}
