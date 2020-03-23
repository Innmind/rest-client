<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities\Capabilities,
    Server\Capabilities as CapabilitiesInterface,
    Server\DefinitionFactory,
    Definition\Types,
    Definition\HttpResource,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Decode\Json,
    Formats,
    Format\Format,
    Format\MediaType,
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Http\{
    Message\Request,
    Message\Response,
    Message\StatusCode,
    Headers,
    Header,
    Header\Value,
    Header\Link,
    Header\LinkValue,
    Header\ContentType,
    Header\ContentTypeValue,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class CapabilitiesTest extends TestCase
{
    private $capabilities;
    private $transport;

    public function setUp(): void
    {
        $this->capabilities = new Capabilities(
            $this->transport = $this->createMock(Transport::class),
            Url::of('http://example.com/'),
            new UrlResolver,
            new DefinitionFactory(
                new DenormalizeDefinition(new Types),
                new Json
            ),
            Formats::of(
                new Format(
                    'json',
                    Set::of(MediaType::class, new MediaType('application/json', 0)),
                    1
                ),
                new Format(
                    'xml',
                    Set::of(MediaType::class, new MediaType('text/xml', 0)),
                    0
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
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/*' &&
                    $request->method()->toString() === 'OPTIONS';
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

        $this->assertInstanceOf(Set::class, $names);
        $this->assertSame('string', (string) $names->type());
        $this->assertCount(0, $names);
        $this->assertSame($names, $this->capabilities->names());
    }

    public function testResolveNames()
    {
        $this
            ->transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/*' &&
                    $request->method()->toString() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::of('/foo'),
                            'foo'
                        ),
                        new LinkValue(
                            Url::of('/bar'),
                            'bar'
                        )
                    )
                )
            );

        $names = $this->capabilities->names();

        $this->assertInstanceOf(Set::class, $names);
        $this->assertSame('string', (string) $names->type());
        $this->assertCount(2, $names);
        $this->assertSame(['foo', 'bar'], unwrap($names));
        $this->assertSame($names, $this->capabilities->names());
    }

    public function testGet()
    {
        $this
            ->transport
            ->expects($this->at(0))
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/*' &&
                    $request->method()->toString() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::of('/foo'),
                            'foo'
                        ),
                        new LinkValue(
                            Url::of('/bar'),
                            'bar'
                        )
                    )
                )
            );
        $this
            ->transport
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/foo' &&
                    $request->method()->toString() === 'OPTIONS' &&
                    $request->headers()->contains('Accept') &&
                    $request->headers()->get('Accept')->toString() === 'Accept: application/json, text/xml';
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
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent('{"url":"http://example.com/foo","identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false},"url":{"type":"string","access":["READ","CREATE","UPDATE"],"variants":[],"optional":false}},"metas":[],"linkable_to":[],"rangeable":true}'));

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
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/*' &&
                    $request->method()->toString() === 'OPTIONS';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::of('/foo'),
                            'foo'
                        ),
                        new LinkValue(
                            Url::of('/bar'),
                            'bar'
                        )
                    )
                )
            );
        $this
            ->transport
            ->expects($this->at(1))
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/foo' &&
                    $request->method()->toString() === 'OPTIONS';
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
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent('{"url":"http://example.com/foo","identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false},"url":{"type":"string","access":["READ","CREATE","UPDATE"],"variants":[],"optional":false}},"metas":[],"linkable_to":[],"rangeable":true}'));
        $this
            ->transport
            ->expects($this->at(2))
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/bar' &&
                    $request->method()->toString() === 'OPTIONS';
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
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(Stream::ofContent('{"url":"http://example.com/foo","identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false}},"metas":[],"linkable_to":[],"rangeable":true}'));

        $definitions = $this->capabilities->definitions();

        $this->assertInstanceOf(Map::class, $definitions);
        $this->assertSame('string', (string) $definitions->keyType());
        $this->assertSame(
            HttpResource::class,
            (string) $definitions->valueType()
        );
        $this->assertCount(2, $definitions);
        $this->assertSame($definitions, $this->capabilities->definitions());
        $this->assertSame(['foo', 'bar'], unwrap($definitions->keys()));
    }

    public function testRefresh()
    {
        $this
            ->transport
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->with($this->callback(function(Request $request): bool {
                return $request->url()->toString() === 'http://example.com/*' &&
                    $request->method()->toString() === 'OPTIONS';
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
        $this->assertInstanceOf(Set::class, $names2);
        $this->assertSame('string', (string) $names2->type());
        $this->assertCount(0, $names2);
    }
}
