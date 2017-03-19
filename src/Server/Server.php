<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    ServerInterface,
    Request\Range,
    IdentityInterface,
    Identity,
    HttpResource,
    Translator\SpecificationTranslatorInterface,
    Exception\ResourceNotRangeableException,
    Exception\UnsupportedResponseException,
    Exception\InvalidArgumentException,
    Exception\NormalizationException,
    Definition\Access,
    Formats,
    Format\Format,
    Link,
    Link\ParameterInterface,
    Definition\HttpResource as Definition
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
    ProtocolVersion,
    Headers,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Range as RangeHeader,
    Header\RangeValue,
    Header\Accept,
    Header\AcceptValue,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\ParameterInterface as HeaderParameterInterface,
    Header\Parameter as HeaderParameter,
    Header\Link as LinkHeader,
    Header\LinkValue
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    SetInterface,
    Map,
    Set
};
use Innmind\Specification\SpecificationInterface;
use Symfony\Component\Serializer\Serializer;

final class Server implements ServerInterface
{
    private $url;
    private $transport;
    private $capabilities;
    private $resolver;
    private $serializer;
    private $specificationTranslator;
    private $formats;

    public function __construct(
        UrlInterface $url,
        TransportInterface $transport,
        CapabilitiesInterface $capabilities,
        ResolverInterface $resolver,
        Serializer $serializer,
        SpecificationTranslatorInterface $specificationTranslator,
        Formats $formats
    ) {
        $this->url = $url;
        $this->transport = $transport;
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
        SpecificationInterface $specification = null,
        Range $range = null
    ): SetInterface {
        $definition = $this->capabilities->get($name);

        if ($range !== null && !$definition->isRangeable()) {
            throw new ResourceNotRangeableException;
        }

        if ($specification !== null) {
            $query = '?'.$this->specificationTranslator->translate($specification);
        }

        $url = $this->resolver->resolve(
            (string) $definition->url(),
            $query ?? (string) $definition->url()
        );
        $url = Url::fromString($url);
        $headers = new Map('string', HeaderInterface::class);

        if ($range !== null) {
            $headers = $headers->put(
                'Range',
                new RangeHeader(
                    new RangeValue(
                        'resource',
                        $range->firstPosition(),
                        $range->lastPosition()
                    )
                )
            );
        }

        $response = $this->transport->fulfill(
            new Request(
                $url,
                new Method(Method::GET),
                new ProtocolVersion(1, 1),
                new Headers($headers)
            )
        );

        return $this->serializer->denormalize(
            $response,
            'rest_identities',
            null,
            ['definition' => $definition]
        );
    }

    public function read(string $name, IdentityInterface $identity): HttpResource
    {
        $definition = $this->capabilities->get($name);
        $response = $this->transport->fulfill(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                new Method(Method::GET),
                new ProtocolVersion(1, 1),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Accept',
                            $this->generateAcceptHeader()
                        )
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
            throw new UnsupportedResponseException('', 0, $e);
        }

        return $this->serializer->deserialize(
            (string) $response->body(),
            HttpResource::class,
            $format->name(),
            [
                'definition' => $definition,
                'response' => $response,
                'access' => new Access(
                    (new Set('string'))->add(Access::READ)
                )
            ]
        );
    }

    public function create(HttpResource $resource): IdentityInterface
    {
        $definition = $this->capabilities->get($resource->name());
        $response = $this->transport->fulfill(
            new Request(
                $definition->url(),
                new Method(Method::POST),
                new ProtocolVersion(1, 1),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json'
                                )
                            )
                        )
                        ->put(
                            'Accept',
                            $this->generateAcceptHeader()
                        )
                ),
                new StringStream(
                    $this->serializer->serialize(
                        $resource,
                        'json',
                        [
                            'definition' => $definition,
                            'access' => new Access(
                                (new Set('string'))->add(Access::CREATE)
                            ),
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
        IdentityInterface $identity,
        HttpResource $resource
    ): ServerInterface {
        $definition = $this->capabilities->get($resource->name());
        $this->transport->fulfill(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                new Method(Method::PUT),
                new ProtocolVersion(1, 1),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json'
                                )
                            )
                        )
                        ->put(
                            'Accept',
                            $this->generateAcceptHeader()
                        )
                ),
                new StringStream(
                    $this->serializer->serialize(
                        $resource,
                        'json',
                        [
                            'definition' => $definition,
                            'access' => new Access(
                                (new Set('string'))->add(Access::UPDATE)
                            ),
                        ]
                    )
                )
            )
        );

        return $this;
    }

    public function remove(string $name, IdentityInterface $identity): ServerInterface
    {
        $definition = $this->capabilities->get($name);
        $this->transport->fulfill(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                new Method(Method::DELETE),
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
        IdentityInterface $identity,
        SetInterface $links
    ): ServerInterface {
        if (
            (string) $links->type() !== Link::class ||
            $links->size() === 0
        ) {
            throw new InvalidArgumentException;
        }

        $definition = $this->capabilities->get($name);
        $this->validateLinks($definition, $links);
        $this->transport->fulfill(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                new Method(Method::LINK),
                new ProtocolVersion(1, 1),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put('Accept', $this->generateAcceptHeader())
                        ->put('Link', $this->generateLinkHeader($links))
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
        IdentityInterface $identity,
        SetInterface $links
    ): ServerInterface {
        if (
            (string) $links->type() !== Link::class ||
            $links->size() === 0
        ) {
            throw new InvalidArgumentException;
        }

        $definition = $this->capabilities->get($name);
        $this->validateLinks($definition, $links);
        $this->transport->fulfill(
            new Request(
                $this->resolveUrl(
                    $definition->url(),
                    $identity
                ),
                new Method(Method::UNLINK),
                new ProtocolVersion(1, 1),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put('Accept', $this->generateAcceptHeader())
                        ->put('Link', $this->generateLinkHeader($links))
                )
            )
        );

        return $this;
    }

    public function capabilities(): CapabilitiesInterface
    {
        return $this->capabilities;
    }

    public function url(): UrlInterface
    {
        return $this->url;
    }

    private function resolveUrl(
        UrlInterface $url,
        IdentityInterface $identity
    ): UrlInterface {
        $url = (string) $url;
        $url = rtrim($url, '/').'/'.$identity;

        return Url::fromString($url);
    }

    private function generateAcceptHeader(): Accept
    {
        return new Accept(
            $this
                ->formats
                ->all()
                ->values()
                ->sort(function(Format $a, Format $b): bool {
                    return $a->priority() < $b->priority();
                })
                ->reduce(
                    new Set(HeaderValueInterface::class),
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
            $links->reduce(
                new Set(HeaderValueInterface::class),
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
                                    new Map('string', HeaderParameterInterface::class),
                                    function(Map $carry, string $name, ParameterInterface $parameter): Map {
                                        return $carry->put(
                                            $name,
                                            new HeaderParameter(
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
