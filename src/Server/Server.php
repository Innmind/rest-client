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
    ProtocolVersion\ProtocolVersion,
    Headers\Headers,
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
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    SetInterface,
    Map,
    Set,
};
use Innmind\Specification\Specification;
use Symfony\Component\Serializer\Serializer;

final class Server implements ServerInterface
{
    private $url;
    private $fulfill;
    private $capabilities;
    private $resolver;
    private $serializer;
    private $specificationTranslator;
    private $formats;

    public function __construct(
        UrlInterface $url,
        Transport $fulfill,
        Capabilities $capabilities,
        ResolverInterface $resolver,
        Serializer $serializer,
        SpecificationTranslator $specificationTranslator,
        Formats $formats
    ) {
        $this->url = $url;
        $this->fulfill = $fulfill;
        $this->capabilities = $capabilities;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
        $this->specificationTranslator = $specificationTranslator;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function all(
        string $name,
        Specification $specification = null,
        Range $range = null
    ): SetInterface {
        $definition = $this->capabilities->get($name);

        if ($range !== null && !$definition->isRangeable()) {
            throw new ResourceNotRangeable;
        }

        if ($specification !== null) {
            $query = '?'.$this->specificationTranslator->translate($specification);
        }

        $url = $this->resolver->resolve(
            (string) $definition->url(),
            $query ?? (string) $definition->url()
        );
        $url = Url::fromString($url);
        $headers = Headers::of();

        if ($range !== null) {
            $headers = Headers::of(
                new RangeHeader(
                    new RangeValue(
                        'resource',
                        $range->firstPosition(),
                        $range->lastPosition()
                    )
                )
            );
        }

        $response = ($this->fulfill)(
            new Request(
                $url,
                Method::get(),
                new ProtocolVersion(1, 1),
                $headers
            )
        );

        return $this->serializer->denormalize(
            $response,
            'rest_identities',
            null,
            ['definition' => $definition]
        );
    }

    public function read(string $name, Identity $identity): HttpResource
    {
        $definition = $this->capabilities->get($name);
        $response = ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                Method::get(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    $this->generateAcceptHeader()
                )
            )
        );

        try {
            $format = $this->formats->matching(
                (string) $response
                    ->headers()
                    ->get('Content-Type')
                    ->values()
                    ->join(', ')
            );
        } catch (\Exception $e) {
            throw new UnsupportedResponse('', 0, $e);
        }

        return $this->serializer->deserialize(
            (string) $response->body(),
            HttpResource::class,
            $format->name(),
            [
                'definition' => $definition,
                'response' => $response,
                'access' => new Access(Access::READ),
            ]
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
                    new ContentType(
                        new ContentTypeValue(
                            'application',
                            'json'
                        )
                    ),
                    $this->generateAcceptHeader()
                ),
                new StringStream(
                    $this->serializer->serialize(
                        $resource,
                        'json',
                        [
                            'definition' => $definition,
                            'access' => new Access(Access::CREATE),
                        ]
                    )
                )
            )
        );

        return $this->serializer->denormalize(
            $response,
            'rest_identity',
            null,
            ['definition' => $definition]
        );
    }

    public function update(
        Identity $identity,
        HttpResource $resource
    ): ServerInterface {
        $definition = $this->capabilities->get($resource->name());
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                Method::put(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    new ContentType(
                        new ContentTypeValue(
                            'application',
                            'json'
                        )
                    ),
                    $this->generateAcceptHeader()
                ),
                new StringStream(
                    $this->serializer->serialize(
                        $resource,
                        'json',
                        [
                            'definition' => $definition,
                            'access' => new Access(Access::UPDATE),
                        ]
                    )
                )
            )
        );

        return $this;
    }

    public function remove(string $name, Identity $identity): ServerInterface
    {
        $definition = $this->capabilities->get($name);
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                Method::delete(),
                new ProtocolVersion(1, 1)
            )
        );

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
        if ((string) $links->type() !== Link::class) {
            throw new \TypeError(sprintf(
                'Argument 3 must be of type SetInterface<%s>',
                Link::class
            ));
        }

        if ($links->size() === 0) {
            throw new DomainException;
        }

        $definition = $this->capabilities->get($name);
        $this->validateLinks($definition, $links);
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                Method::link(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    $this->generateAcceptHeader(),
                    $this->generateLinkHeader($links)
                )
            )
        );

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
        if ((string) $links->type() !== Link::class) {
            throw new \TypeError(sprintf(
                'Argument 3 must be of type SetInterface<%s>',
                Link::class
            ));
        }

        if ($links->size() === 0) {
            throw new DomainException;
        }

        $definition = $this->capabilities->get($name);
        $this->validateLinks($definition, $links);
        ($this->fulfill)(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                Method::unlink(),
                new ProtocolVersion(1, 1),
                Headers::of(
                    $this->generateAcceptHeader(),
                    $this->generateLinkHeader($links)
                )
            )
        );

        return $this;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }

    public function url(): UrlInterface
    {
        return $this->url;
    }

    private function resolveUrl(
        UrlInterface $url,
        Identity $identity
    ): UrlInterface {
        $url = (string) $url;
        $url = \rtrim($url, '/').'/'.$identity;

        return Url::fromString($url);
    }

    private function generateAcceptHeader(): Accept
    {
        return new Accept(
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
        );
    }

    private function generateLinkHeader(SetInterface $links): LinkHeader
    {
        return new LinkHeader(
            ...$links->reduce(
                new Set(Value::class),
                function(Set $carry, Link $link): Set {
                    $url = $this->resolveUrl(
                        $this
                            ->capabilities
                            ->get($link->definition())
                            ->url(),
                        $link->identity()
                    );

                    return $carry->add(
                        new LinkValue(
                            Url::fromString((string) $url->path()),
                            $link->relationship(),
                            $link
                                ->parameters()
                                ->reduce(
                                    new Map('string', HeaderParameter::class),
                                    function(Map $carry, string $name, Parameter $parameter): Map {
                                        return $carry->put(
                                            $name,
                                            new HeaderParameter\Parameter(
                                                $parameter->key(),
                                                $parameter->value()
                                            )
                                        );
                                    }
                                )
                        )
                    );
                }
            )
        );
    }

    /**
     * @param SetInterface<link> $links
     *
     * @throws NormalizationException
     */
    private function validateLinks(Definition $definition, SetInterface $links): void
    {
        $links->foreach(function(Link $link) use ($definition): void {
            if (!$definition->allowsLink($link)) {
                throw new NormalizationException;
            }
        });
    }
}
