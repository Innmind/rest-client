<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\Server,
    Server as ServerInterface,
    Server\Capabilities,
    Serializer\Normalizer\NormalizeResource,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Denormalizer\DenormalizeResource,
    Serializer\Encode,
    Serializer\Decode,
    Definition\HttpResource as HttpResourceDefinition,
    Definition\Identity as IdentityDefinition,
    Definition\Property as PropertyDefinition,
    Definition\Types,
    Request\Range,
    Identity,
    Formats,
    Format\Format,
    Format\MediaType,
    HttpResource,
    HttpResource\Property,
    Translator\Specification\SpecificationTranslator,
    Link,
    Link\Parameter,
    Response\ExtractIdentity,
    Response\ExtractIdentities,
    Visitor\ResolveIdentity,
};
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Url\{
    Url,
    UrlInterface,
};
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Headers\Headers,
    Header,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\Location,
    Header\LocationValue,
    Header\Link as LinkHeader,
    Header\LinkValue,
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
};
use Innmind\Specification\{
    Comparator,
    Sign,
};
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    private $server;
    private $url;
    private $transport;
    private $capabilities;
    private $definition;

    public function setUp()
    {
        $this->server = new Server(
            $this->url = Url::fromString('http://example.com/'),
            $this->transport = $this->createMock(Transport::class),
            $this->capabilities = $this->createMock(Capabilities::class),
            $resolver = new UrlResolver,
            new ExtractIdentity(new ResolveIdentity($resolver)),
            new ExtractIdentities(new ResolveIdentity($resolver)),
            new DenormalizeResource,
            new NormalizeResource,
            new Encode\Json,
            new Decode\Json,
            new SpecificationTranslator,
            Formats::of(
                new Format(
                    'json',
                    Set::of(
                        MediaType::class,
                        new MediaType('application/json', 0)
                    ),
                    1
                ),
                new Format(
                    'xml',
                    Set::of(
                        MediaType::class,
                        new MediaType('text/xml', 0)
                    ),
                    0
                )
            )
        );

        $this->definition = (new DenormalizeDefinition(new Types))(
            [
                'url' => 'http://example.com/foo',
                'identity' => 'uuid',
                'properties' => [
                    'uuid' => [
                        'type' => 'string',
                        'access' => ['READ'],
                        'variants' => ['guid'],
                        'optional' => false,
                    ],
                    'url' => [
                        'type' => 'string',
                        'access' => ['READ', 'CREATE', 'UPDATE'],
                        'variants' => [],
                        'optional' => true,
                    ]
                ],
                'metas' => [
                    'foo' => ['bar' => 'baz'],
                ],
                'linkable_to' => [
                    'canonical' => 'bar',
                ],
                'rangeable' => true,
            ],
            'foo'
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ServerInterface::class,
            $this->server
        );
        $this->assertSame(
            $this->capabilities,
            $this->server->capabilities()
        );
        $this->assertSame(
            $this->url,
            $this->server->url()
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\ResourceNotRangeable
     */
    public function testThrowWhenRangingOnNonRangeableResource()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(
                new HttpResourceDefinition(
                    'foo',
                    $this->createMock(UrlInterface::class),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    new Map('string', 'string'),
                    false
                )
            );

        $this->server->all(
            'foo',
            null,
            new Range(0, 42)
        );
    }

    public function testAll()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(
                $definition = new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    new Map('string', 'string'),
                    false
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 0 &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of(
                new LinkHeader(
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo');

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $this->assertSame('some-uuid', (string) $all->current());
        $all->next();
        $this->assertSame('some-other-uuid', (string) $all->current());
    }

    public function testAllWithRange()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(
                $definition = new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    new Map('string', 'string'),
                    true
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->has('range') &&
                    (string) $request->headers()->get('range') === 'Range: resource=10-20' &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of(
                new LinkHeader(
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo', null, new Range(10, 20));

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $this->assertSame('some-uuid', (string) $all->current());
        $all->next();
        $this->assertSame('some-other-uuid', (string) $all->current());
    }

    public function testAllWithQuery()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(
                $definition = new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    new Map('string', 'string'),
                    false
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo?bar=baz' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 0 &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $specification = $this->createMock(Comparator::class);
        $specification
            ->expects($this->once())
            ->method('property')
            ->willReturn('bar');
        $specification
            ->expects($this->once())
            ->method('sign')
            ->willReturn(Sign::equality());
        $specification
            ->expects($this->once())
            ->method('value')
            ->willReturn('baz');
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of(
                new LinkHeader(
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo', $specification);

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $this->assertSame('some-uuid', (string) $all->current());
        $all->next();
        $this->assertSame('some-other-uuid', (string) $all->current());
    }

    public function testAllWithQueryAndRange()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(
                $definition = new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    new Map('string', 'string'),
                    true
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo?bar=baz' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->has('range') &&
                    (string) $request->headers()->get('range') === 'Range: resource=10-20' &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $specification = $this->createMock(Comparator::class);
        $specification
            ->expects($this->once())
            ->method('property')
            ->willReturn('bar');
        $specification
            ->expects($this->once())
            ->method('sign')
            ->willReturn(Sign::equality());
        $specification
            ->expects($this->once())
            ->method('value')
            ->willReturn('baz');
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of(
                new LinkHeader(
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::fromString('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo', $specification, new Range(10, 20));

        $this->assertInstanceOf(SetInterface::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $this->assertSame('some-uuid', (string) $all->current());
        $all->next();
        $this->assertSame('some-other-uuid', (string) $all->current());
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\UnsupportedResponse
     */
    public function testThrowWhenReadResponseHasNoContentType()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers
            );

        $this->server->read('foo', new Identity\Identity('uuid'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\UnsupportedResponse
     */
    public function testThrowWhenReadResponseContentTypeNotSupported()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new ContentType(
                        new ContentTypeValue(
                            'text',
                            'plain'
                        )
                    )
                )
            );

        $this->server->read('foo', new Identity\Identity('uuid'));
    }

    public function testRead()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo/bar' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    (string) $request->headers()->get('Accept') === 'Accept: application/json, text/xml' &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new ContentType(
                        new ContentTypeValue(
                            'application',
                            'json'
                        )
                    )
                )
            );
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                new StringStream('{"resource":{"uuid":"bar","url":"example.com"}}')
            );

        $resource = $this->server->read('foo', new Identity\Identity('bar'));

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('foo', $resource->name());
        $this->assertCount(2, $resource->properties());
        $this->assertSame(
            'bar',
            $resource->properties()->get('uuid')->value()
        );
        $this->assertSame(
            'example.com',
            $resource->properties()->get('url')->value()
        );
    }

    public function testCreate()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'POST' &&
                    $request->headers()->count() === 2 &&
                    (string) $request->headers()->get('Content-Type') === 'Content-Type: application/json' &&
                    (string) $request->headers()->get('Accept') === 'Accept: application/json, text/xml' &&
                    (string) $request->body() === '{"resource":{"url":"foobar"}}';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(Headers::of(
                new Location(
                    new LocationValue(
                        Url::fromString('http://example.com/foo/some-uuid')
                    )
                )
            ));

        $identity = $this->server->create(
            HttpResource::of(
                'foo',
                new Property('url', 'foobar')
            )
        );

        $this->assertSame('some-uuid', (string) $identity);
    }

    public function testUpdate()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo/some-uuid' &&
                    (string) $request->method() === 'PUT' &&
                    $request->headers()->count() === 2 &&
                    (string) $request->headers()->get('Content-Type') === 'Content-Type: application/json' &&
                    (string) $request->headers()->get('Accept') === 'Accept: application/json, text/xml' &&
                    (string) $request->body() === '{"resource":{"url":"foobar"}}';
            }))
            ->willReturn(
                $this->createMock(Response::class)
            );

        $return = $this->server->update(
            new Identity\Identity('some-uuid'),
            HttpResource::of(
                'foo',
                new Property('url', 'foobar')
            )
        );

        $this->assertSame($this->server, $return);
    }

    public function testDelete()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo/some-uuid' &&
                    (string) $request->method() === 'DELETE' &&
                    $request->headers()->count() === 0 &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $this->createMock(Response::class)
            );

        $return = $this->server->remove(
            'foo',
            new Identity\Identity('some-uuid')
        );

        $this->assertSame($this->server, $return);
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 3 must be of type SetInterface<Innmind\Rest\Client\Link>
     */
    public function testThrowWhenInvalidSetOfLinks()
    {
        $this->server->link(
            'foo',
            $this->createMock(Identity::class),
            new Set('string')
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptySetOfLinks()
    {
        $this->server->link(
            'foo',
            $this->createMock(Identity::class),
            new Set(Link::class)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     */
    public function testThrowWhenLinkNotAllowedByDefinition()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->never())
            ->method('__invoke');

        $this->server->link(
            'foo',
            new Identity\Identity('some-uuid'),
            Set::of(
                Link::class,
                Link::of(
                    'baz',
                    new Identity\Identity('cano'),
                    'canonical',
                    new Parameter\Parameter('attr', 'val')
                )
            )
        );
    }

    public function testLink()
    {
        $this
            ->capabilities
            ->expects($this->at(0))
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->capabilities
            ->expects($this->at(1))
            ->method('get')
            ->with('bar')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo/some-uuid' &&
                    (string) $request->method() === 'LINK' &&
                    (string) $request->headers()->get('Accept') === 'Accept: application/json, text/xml' &&
                    (string) $request->headers()->get('Link') === 'Link: </foo/cano>; rel="canonical";attr=val' &&
                    (string) $request->body() === '';
            }));

        $this->assertSame(
            $this->server,
            $this->server->link(
                'foo',
                new Identity\Identity('some-uuid'),
                Set::of(
                    Link::class,
                    Link::of(
                        'bar',
                        new Identity\Identity('cano'),
                        'canonical',
                        new Parameter\Parameter('attr', 'val')
                    )
                )
            )
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 3 must be of type SetInterface<Innmind\Rest\Client\Link>
     */
    public function testThrowWhenInvalidSetOfLinksToUnlink()
    {
        $this->server->unlink(
            'foo',
            $this->createMock(Identity::class),
            new Set('string')
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptySetOfLinksToUnlink()
    {
        $this->server->unlink(
            'foo',
            $this->createMock(Identity::class),
            new Set(Link::class)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     */
    public function testThrowWhenUnlinkNotAllowedByDefinition()
    {
        $this
            ->capabilities
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->never())
            ->method('__invoke');

        $this->server->unlink(
            'foo',
            new Identity\Identity('some-uuid'),
            Set::of(
                Link::class,
                Link::of(
                    'baz',
                    new Identity\Identity('cano'),
                    'canonical',
                    new Parameter\Parameter('attr', 'val')
                )
            )
        );
    }

    public function testUnlink()
    {
        $this
            ->capabilities
            ->expects($this->at(0))
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->capabilities
            ->expects($this->at(1))
            ->method('get')
            ->with('bar')
            ->willReturn($this->definition);
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo/some-uuid' &&
                    (string) $request->method() === 'UNLINK' &&
                    (string) $request->headers()->get('Accept') === 'Accept: application/json, text/xml' &&
                    (string) $request->headers()->get('Link') === 'Link: </foo/cano>; rel="canonical";attr=val' &&
                    (string) $request->body() === '';
            }));

        $this->assertSame(
            $this->server,
            $this->server->unlink(
                'foo',
                new Identity\Identity('some-uuid'),
                Set::of(
                    Link::class,
                    Link::of(
                        'bar',
                        new Identity\Identity('cano'),
                        'canonical',
                        new Parameter\Parameter('attr', 'val')
                    )
                )
            )
        );
    }
}
