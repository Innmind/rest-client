<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\Server,
    ServerInterface,
    Server\CapabilitiesInterface,
    Serializer\Normalizer\DefinitionNormalizer,
    Serializer\Normalizer\ResourceNormalizer,
    Definition\HttpResource as HttpResourceDefinition,
    Definition\Identity as IdentityDefinition,
    Definition\Property as PropertyDefinition,
    Definition\Types,
    Request\Range,
    IdentityInterface,
    Identity,
    Formats,
    Format\Format,
    Format\MediaType,
    HttpResource,
    HttpResource\Property,
    Translator\SpecificationTranslator
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Url\{
    Url,
    UrlInterface
};
use Innmind\Http\{
    Message\RequestInterface,
    Message\ResponseInterface,
    Headers,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Link,
    Header\LinkValue,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\Location,
    Header\LocationValue,
    Header\ParameterInterface
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};
use Innmind\Specification\ComparatorInterface;
use Symfony\Component\Serializer\{
    Serializer,
    Encoder\JsonEncoder,
    Normalizer\DenormalizerInterface
};

class ServerTest extends \PHPUnit_Framework_TestCase
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
            $this->transport = $this->createMock(TransportInterface::class),
            $this->capabilities = $this->createMock(CapabilitiesInterface::class),
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
                (new Map('string', Format::class))
                    ->put(
                        'json',
                        new Format(
                            'json',
                            (new Set(MediaType::class))->add(
                                new MediaType('application/json', 0)
                            ),
                            1
                        )
                    )
                    ->put(
                        'xml',
                        new Format(
                            'xml',
                            (new Set(MediaType::class))->add(
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
     * @expectedException Innmind\Rest\Client\Exception\ResourceNotRangeableException
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
                new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    false
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 0 &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof ResponseInterface && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with($response)
            ->willReturn($expected = new Set(IdentityInterface::class));

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
                new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    true
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->has('range') &&
                    (string) $request->headers()->get('range') === 'Range : resource=10-20' &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof ResponseInterface && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with($response)
            ->willReturn($expected = new Set(IdentityInterface::class));

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
                new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    false
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo?bar=baz' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 0 &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
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
                return $data instanceof ResponseInterface && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with($response)
            ->willReturn($expected = new Set(IdentityInterface::class));

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
                new HttpResourceDefinition(
                    'foo',
                    Url::fromString('http://example.com/foo'),
                    new IdentityDefinition('uuid'),
                    new Map('string', PropertyDefinition::class),
                    new Map('scalar', 'variable'),
                    true
                )
            );
        $this
            ->transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo?bar=baz' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->has('range') &&
                    (string) $request->headers()->get('range') === 'Range : resource=10-20' &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
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
                return $data instanceof ResponseInterface && $format === 'rest_identities';
            }));
        $this
            ->identitiesNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with($response)
            ->willReturn($expected = new Set(IdentityInterface::class));

        $all = $this->server->all('foo', $specification, new Range(10, 20));

        $this->assertSame($expected, $all);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\UnsupportedResponseException
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
            ->method('fulfill')
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    new Map('string', HeaderInterface::class)
                )
            );

        $this->server->read('foo', new Identity('uuid'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\UnsupportedResponseException
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
            ->method('fulfill')
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'text',
                                    'plain',
                                    new Map('string', ParameterInterface::class)
                                )
                            )
                        )
                )
            );

        $this->server->read('foo', new Identity('uuid'));
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
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo/bar' &&
                    (string) $request->method() === 'GET' &&
                    $request->headers()->count() === 1 &&
                    (string) $request->headers()->get('Accept') === 'Accept : application/json, text/xml' &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json',
                                    new Map('string', ParameterInterface::class)
                                )
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

        $resource = $this->server->read('foo', new Identity('bar'));

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
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'POST' &&
                    $request->headers()->count() === 1 &&
                    (string) $request->headers()->get('Content-Type') === 'Content-Type : application/json' &&
                    (string) $request->body() === '{"resource":{"url":"foobar"}}';
            }))
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $this
            ->identityNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->will($this->returnCallback(function($data, $format) {
                return $data instanceof ResponseInterface && $format === 'rest_identity';
            }));
        $this
            ->identityNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->willReturn($expected = new Identity('some-uuid'));

        $identity = $this->server->create(
            new HttpResource(
                'foo',
                (new Map('string', Property::class))
                    ->put(
                        'url',
                        new Property(
                            'url',
                            'foobar'
                        )
                    )
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
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo/some-uuid' &&
                    (string) $request->method() === 'PUT' &&
                    $request->headers()->count() === 1 &&
                    (string) $request->headers()->get('Content-Type') === 'Content-Type : application/json' &&
                    (string) $request->body() === '{"resource":{"url":"foobar"}}';
            }))
            ->willReturn(
                $this->createMock(ResponseInterface::class)
            );

        $return = $this->server->update(
            new Identity('some-uuid'),
            new HttpResource(
                'foo',
                (new Map('string', Property::class))
                    ->put(
                        'url',
                        new Property(
                            'url',
                            'foobar'
                        )
                    )
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
            ->method('fulfill')
            ->with($this->callback(function(RequestInterface $request): bool {
                return (string) $request->url() === 'http://example.com/foo/some-uuid' &&
                    (string) $request->method() === 'DELETE' &&
                    $request->headers()->count() === 0 &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $this->createMock(ResponseInterface::class)
            );

        $return = $this->server->remove(
            'foo',
            new Identity('some-uuid')
        );

        $this->assertSame($this->server, $return);
    }
}
