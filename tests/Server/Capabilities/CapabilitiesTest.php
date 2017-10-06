<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities\Capabilities,
    Server\Capabilities as CapabilitiesInterface,
    Server\DefinitionFactory,
    Definition\Types,
    Definition\HttpResource,
    Serializer\Normalizer\DefinitionNormalizer,
    Formats,
    Format\Format,
    Format\MediaType
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Message\StatusCode\StatusCode,
    Headers\Headers,
    Header,
    Header\Value,
    Header\Link,
    Header\LinkValue,
    Header\ContentType,
    Header\ContentTypeValue
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase
{
    private $capabilities;
    private $transport;

    public function setUp()
    {
        $types = new Types;
        Types::defaults()->foreach(function(string $class) use ($types) {
            $types->register($class);
        });

        $this->capabilities = new Capabilities(
            $this->transport = $this->createMock(Transport::class),
            Url::fromString('http://example.com/'),
            new UrlResolver,
            new DefinitionFactory(
                new DefinitionNormalizer($types)
            ),
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
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            CapabilitiesInterface::class,
            $this->capabilities
        );
    }

    public function testResolveEmptyNames()
    {
        $this
            ->transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/*' &&
                    (string) $request->method() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers
            );

        $names = $this->capabilities->names();

        $this->assertInstanceOf(SetInterface::class, $names);
        $this->assertSame('string', (string) $names->type());
        $this->assertCount(0, $names);
        $this->assertSame($names, $this->capabilities->names());
    }

    public function testResolveNames()
    {
        $this
            ->transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/*' &&
                    (string) $request->method() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'Link',
                            new Link(
                                new LinkValue(
                                    Url::fromString('/foo'),
                                    'foo'
                                ),
                                new LinkValue(
                                    Url::fromString('/bar'),
                                    'bar'
                                )
                            )
                        )
                )
            );

        $names = $this->capabilities->names();

        $this->assertInstanceOf(SetInterface::class, $names);
        $this->assertSame('string', (string) $names->type());
        $this->assertCount(2, $names);
        $this->assertSame(['foo', 'bar'], $names->toPrimitive());
        $this->assertSame($names, $this->capabilities->names());
    }

    public function testGet()
    {
        $this
            ->transport
            ->expects($this->at(0))
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/*' &&
                    (string) $request->method() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'Link',
                            new Link(
                                new LinkValue(
                                    Url::fromString('/foo'),
                                    'foo'
                                ),
                                new LinkValue(
                                    Url::fromString('/bar'),
                                    'bar'
                                )
                            )
                        )
                )
            );
        $this
            ->transport
            ->expects($this->at(1))
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'OPTIONS' &&
                    $request->headers()->has('Accept') &&
                    (string) $request->headers()->get('Accept') === 'Accept : application/json, text/xml';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json'
                                )
                            )
                        )
                )
            );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('{"url":"http://example.com/foo","identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false},"url":{"type":"string","access":["READ","CREATE","UPDATE"],"variants":[],"optional":false}},"metas":[],"linkable_to":[],"rangeable":true}'));

        $definition = $this->capabilities->get('foo');

        $this->assertInstanceOf(HttpResource::class, $definition);
        $this->assertSame('foo', $definition->name());
        $this->assertCount(2, $definition->properties());
        $this->assertSame($definition, $this->capabilities->get('foo'));
    }

    public function testDefinitions()
    {
        $this
            ->transport
            ->expects($this->at(0))
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/*' &&
                    (string) $request->method() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'Link',
                            new Link(
                                new LinkValue(
                                    Url::fromString('/foo'),
                                    'foo'
                                ),
                                new LinkValue(
                                    Url::fromString('/bar'),
                                    'bar'
                                )
                            )
                        )
                )
            );
        $this
            ->transport
            ->expects($this->at(1))
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/foo' &&
                    (string) $request->method() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json'
                                )
                            )
                        )
                )
            );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('{"url":"http://example.com/foo","identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false},"url":{"type":"string","access":["READ","CREATE","UPDATE"],"variants":[],"optional":false}},"metas":[],"linkable_to":[],"rangeable":true}'));
        $this
            ->transport
            ->expects($this->at(2))
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/bar' &&
                    (string) $request->method() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', Header::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json'
                                )
                            )
                        )
                )
            );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(new StringStream('{"url":"http://example.com/foo","identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false}},"metas":[],"linkable_to":[],"rangeable":true}'));

        $definitions = $this->capabilities->definitions();

        $this->assertInstanceOf(MapInterface::class, $definitions);
        $this->assertSame('string', (string) $definitions->keyType());
        $this->assertSame(
            HttpResource::class,
            (string) $definitions->valueType()
        );
        $this->assertCount(2, $definitions);
        $this->assertSame($definitions, $this->capabilities->definitions());
        $this->assertSame(['foo', 'bar'], $definitions->keys()->toPrimitive());
    }

    public function testRefresh()
    {
        $this
            ->transport
            ->expects($this->exactly(2))
            ->method('fulfill')
            ->with($this->callback(function(Request $request): bool {
                return (string) $request->url() === 'http://example.com/*' &&
                    (string) $request->method() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->exactly(2))
            ->method('headers')
            ->willReturn(
                new Headers
            );

        $names = $this->capabilities->names();
        $this->assertSame($this->capabilities, $this->capabilities->refresh());
        $names2 = $this->capabilities->names();
        $this->assertNotSame($names, $names2);
        $this->assertInstanceOf(SetInterface::class, $names2);
        $this->assertSame('string', (string) $names2->type());
        $this->assertCount(0, $names2);
    }
}
