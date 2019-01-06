<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\Server,
    Server as ServerInterface,
    Server\Capabilities,
    Serializer\Normalizer\DefinitionNormalizer,
    Serializer\Normalizer\ResourceNormalizer,
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
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
};
use Innmind\Specification\ComparatorInterface;
use Symfony\Component\Serializer\{
    Serializer,
    Encoder\JsonEncoder,
    Normalizer\DenormalizerInterface,
};
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    private $server;
    private $url;
    private $transport;
    private $capabilities;
    private $identitiesNormalizer;
    private $identityNormalizer;
    private $definition;

    public function setUp()
    {
        $this->server = new Server(
            $this->url = Url::fromString('http://example.com/'),
            $this->transport = $this->createMock(Transport::class),
            $this->capabilities = $this->createMock(Capabilities::class),
            new UrlResolver,
            new Serializer(
                [
                    $this->identitiesNormalizer = $this->createMock(DenormalizerInterface::class),
                    $this->identityNormalizer = $this->createMock(DenormalizerInterface::class),
                    new ResourceNormalizer,
                ],
                [new JsonEncoder]
            ),
            new SpecificationTranslator,
            new Formats(
                Map::of('string', Format::class)
                    (
                        'json',
                        new Format(
                            'json',
                            Set::of(
                                MediaType::class,
                                new MediaType('application/json', 0)
                            ),
                            1
                        )
                    )
                    (
                        'xml',
                        new Format(
                            'xml',
                            Set::of(
                                MediaType::class,
                                new MediaType('text/xml', 0)
                            ),
                            0
                        )
                    )
            )
        );

        $types = new Types;
        Types::defaults()->foreach(function(string $class) use ($types) {
            $types->register($class);
        });
        $this->definition = (new DefinitionNormalizer($types))->denormalize(
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
            HttpResourceDefinition::class,
            null,
            ['name' => 'foo']
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
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof Response && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $response,
                'rest_identities',
                null,
                ['definition' => $definition]
            )
            ->willReturn($expected = new Set(Identity::class));

        $all = $this->server->all('foo');

        $this->assertSame($expected, $all);
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
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof Response && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $response,
                'rest_identities',
                null,
                ['definition' => $definition]
            )
            ->willReturn($expected = new Set(Identity::class));

        $all = $this->server->all('foo', null, new Range(10, 20));

        $this->assertSame($expected, $all);
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
        $specification = $this->createMock(ComparatorInterface::class);
        $specification
            ->expects($this->once())
            ->method('property')
            ->willReturn('bar');
        $specification
            ->expects($this->once())
            ->method('sign')
            ->willReturn('==');
        $specification
            ->expects($this->once())
            ->method('value')
            ->willReturn('baz');
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof Response && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $response,
                'rest_identities',
                null,
                ['definition' => $definition]
            )
            ->willReturn($expected = new Set(Identity::class));

        $all = $this->server->all('foo', $specification);

        $this->assertSame($expected, $all);
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
        $specification = $this->createMock(ComparatorInterface::class);
        $specification
            ->expects($this->once())
            ->method('property')
            ->willReturn('bar');
        $specification
            ->expects($this->once())
            ->method('sign')
            ->willReturn('==');
        $specification
            ->expects($this->once())
            ->method('value')
            ->willReturn('baz');
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof Response && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $response,
                'rest_identities',
                null,
                ['definition' => $definition]
            )
            ->willReturn($expected = new Set(Identity::class));

        $all = $this->server->all('foo', $specification, new Range(10, 20));

        $this->assertSame($expected, $all);
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
        $this
            ->identityNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof Response && $format === 'rest_identity';
            }));
        $this
            ->identityNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $response,
                'rest_identity',
                null,
                ['definition' => $this->definition]
            )
            ->willReturn($expected = new Identity\Identity('some-uuid'));

        $identity = $this->server->create(
            HttpResource::of(
                'foo',
                new Property('url', 'foobar')
            )
        );

        $this->assertSame($expected, $identity);
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
                new Link(
                    'baz',
                    new Identity\Identity('cano'),
                    'canonical',
                    (new Map('string', Parameter::class))
                        ->put('attr', new Parameter\Parameter('attr', 'val'))
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
                    new Link(
                        'bar',
                        new Identity\Identity('cano'),
                        'canonical',
                        (new Map('string', Parameter::class))
                            ->put('attr', new Parameter\Parameter('attr', 'val'))
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
                new Link(
                    'baz',
                    new Identity\Identity('cano'),
                    'canonical',
                    (new Map('string', Parameter::class))
                        ->put('attr', new Parameter\Parameter('attr', 'val'))
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
                    new Link(
                        'bar',
                        new Identity\Identity('cano'),
                        'canonical',
                        (new Map('string', Parameter::class))
                            ->put('attr', new Parameter\Parameter('attr', 'val'))
                    )
                )
            )
        );
    }
}
