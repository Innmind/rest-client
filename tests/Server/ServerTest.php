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
    Definition\AllowedLink,
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
    Exception\ResourceNotRangeable,
    Exception\NormalizationException,
    Exception\DomainException,
    Exception\UnsupportedResponse,
};
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Url\Url;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Headers,
    Header,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\Location,
    Header\LocationValue,
    Header\Link as LinkHeader,
    Header\LinkValue,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
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

    public function setUp(): void
    {
        $this->server = new Server(
            $this->url = Url::of('http://example.com/'),
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
                    [
                        'relationship' => 'canonical',
                        'resource_path' => 'bar',
                        'parameters' => [],
                    ],
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
                    Url::of('http://example.com'),
                    new IdentityDefinition('uuid'),
                    Map::of('string', PropertyDefinition::class),
                    Map::of('scalar', 'scalar|array'),
                    Set::of(AllowedLink::class),
                    false
                )
            );

        $this->expectException(ResourceNotRangeable::class);

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
                    Url::of('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    Map::of('string', PropertyDefinition::class),
                    Map::of('scalar', 'scalar|array'),
                    Set::of(AllowedLink::class),
                    false
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->count() === 0 &&
                    $request->body()->toString() === '';
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
                        Url::of('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::of('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo');

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $all = unwrap($all);
        $this->assertSame('some-uuid', \current($all)->toString());
        \next($all);
        $this->assertSame('some-other-uuid', \current($all)->toString());
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
                    Url::of('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    Map::of('string', PropertyDefinition::class),
                    Map::of('scalar', 'scalar|array'),
                    Set::of(AllowedLink::class),
                    true
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/foo' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->contains('range') &&
                    $request->headers()->get('range')->toString() === 'Range: resource=10-20' &&
                    $request->body()->toString() === '';
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
                        Url::of('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::of('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo', null, new Range(10, 20));

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $all = unwrap($all);
        $this->assertSame('some-uuid', \current($all)->toString());
        \next($all);
        $this->assertSame('some-other-uuid', \current($all)->toString());
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
                    Url::of('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    Map::of('string', PropertyDefinition::class),
                    Map::of('scalar', 'scalar|array'),
                    Set::of(AllowedLink::class),
                    false
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/foo?bar=baz' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->count() === 0 &&
                    $request->body()->toString() === '';
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
                        Url::of('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::of('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo', $specification);

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $all = unwrap($all);
        $this->assertSame('some-uuid', \current($all)->toString());
        \next($all);
        $this->assertSame('some-other-uuid', \current($all)->toString());
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
                    Url::of('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    Map::of('string', PropertyDefinition::class),
                    Map::of('scalar', 'scalar|array'),
                    Set::of(AllowedLink::class),
                    true
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/foo?bar=baz' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->contains('range') &&
                    $request->headers()->get('range')->toString() === 'Range: resource=10-20' &&
                    $request->body()->toString() === '';
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
                        Url::of('http://example.com/foo/some-uuid'),
                        'resource'
                    ),
                    new LinkValue(
                        Url::of('http://example.com/foo/some-other-uuid'),
                        'resource'
                    )
                )
            ));

        $all = $this->server->all('foo', $specification, new Range(10, 20));

        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(Identity::class, (string) $all->type());
        $this->assertCount(2, $all);
        $all = unwrap($all);
        $this->assertSame('some-uuid', \current($all)->toString());
        \next($all);
        $this->assertSame('some-other-uuid', \current($all)->toString());
    }

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

        $this->expectException(UnsupportedResponse::class);

        $this->server->read('foo', new Identity\Identity('uuid'));
    }

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

        $this->expectException(UnsupportedResponse::class);

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
                return $request->url()->toString() === 'http://example.com/foo/bar' &&
                    $request->method()->toString() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->get('Accept')->toString() === 'Accept: application/json, text/xml' &&
                    $request->body()->toString() === '';
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
                Stream::ofContent('{"resource":{"uuid":"bar","url":"example.com"}}')
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
                return $request->url()->toString() === 'http://example.com/foo' &&
                    $request->method()->toString() === 'POST' &&
                    $request->headers()->count() === 2 &&
                    $request->headers()->get('Content-Type')->toString() === 'Content-Type: application/json' &&
                    $request->headers()->get('Accept')->toString() === 'Accept: application/json, text/xml' &&
                    $request->body()->toString() === '{"resource":{"url":"foobar"}}';
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
                        Url::of('http://example.com/foo/some-uuid')
                    )
                )
            ));

        $identity = $this->server->create(
            HttpResource::of(
                'foo',
                new Property('url', 'foobar')
            )
        );

        $this->assertSame('some-uuid', $identity->toString());
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
                return $request->url()->toString() === 'http://example.com/foo/some-uuid' &&
                    $request->method()->toString() === 'PUT' &&
                    $request->headers()->count() === 2 &&
                    $request->headers()->get('Content-Type')->toString() === 'Content-Type: application/json' &&
                    $request->headers()->get('Accept')->toString() === 'Accept: application/json, text/xml' &&
                    $request->body()->toString() === '{"resource":{"url":"foobar"}}';
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
                return $request->url()->toString() === 'http://example.com/foo/some-uuid' &&
                    $request->method()->toString() === 'DELETE' &&
                    $request->headers()->count() === 0 &&
                    $request->body()->toString() === '';
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

    public function testThrowWhenInvalidSetOfLinks()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type Set<Innmind\Rest\Client\Link>');

        $this->server->link(
            'foo',
            $this->createMock(Identity::class),
            Set::of('string')
        );
    }

    public function testThrowWhenEmptySetOfLinks()
    {
        $this->expectException(DomainException::class);

        $this->server->link(
            'foo',
            $this->createMock(Identity::class),
            Set::of(Link::class)
        );
    }

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

        $this->expectException(NormalizationException::class);

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
                return $request->url()->toString() === 'http://example.com/foo/some-uuid' &&
                    $request->method()->toString() === 'LINK' &&
                    $request->headers()->get('Accept')->toString() === 'Accept: application/json, text/xml' &&
                    $request->headers()->get('Link')->toString() === 'Link: </foo/cano>; rel="canonical";attr=val' &&
                    $request->body()->toString() === '';
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

    public function testThrowWhenInvalidSetOfLinksToUnlink()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type Set<Innmind\Rest\Client\Link>');

        $this->server->unlink(
            'foo',
            $this->createMock(Identity::class),
            Set::of('string')
        );
    }

    public function testThrowWhenEmptySetOfLinksToUnlink()
    {
        $this->expectException(DomainException::class);

        $this->server->unlink(
            'foo',
            $this->createMock(Identity::class),
            Set::of(Link::class)
        );
    }

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

        $this->expectException(NormalizationException::class);

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
                return $request->url()->toString() === 'http://example.com/foo/some-uuid' &&
                    $request->method()->toString() === 'UNLINK' &&
                    $request->headers()->get('Accept')->toString() === 'Accept: application/json, text/xml' &&
                    $request->headers()->get('Link')->toString() === 'Link: </foo/cano>; rel="canonical";attr=val' &&
                    $request->body()->toString() === '';
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
