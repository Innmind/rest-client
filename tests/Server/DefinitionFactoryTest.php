<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\DefinitionFactory,
    Definition\Types,
    Definition\HttpResource,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Decode\Json,
    Exception\DomainException,
};
use Innmind\Http\{
    Message\Response,
    Message\StatusCode,
    Headers,
    Header,
    Header\ContentType,
    Header\ContentTypeValue,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Url\Url;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class DefinitionFactoryTest extends TestCase
{
    private $make;

    public function setUp(): void
    {
        $this->make = new DefinitionFactory(
            new DenormalizeDefinition(new Types),
            new Json
        );
    }

    public function testThrowWhenResponseNotSuccessful()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(404));
        $response
            ->expects($this->any())
            ->method('headers')
            ->willReturn(new Headers);

        $this->expectException(DomainException::class);

        ($this->make)('foo', Url::of('/'), $response);
    }

    public function testThrowWhenResponseHasNoContentType()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers
            );

        $this->expectException(DomainException::class);

        ($this->make)('foo', Url::of('/'), $response);
    }

    public function testThrowWhenResponseHasNotJson()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
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

        $this->expectException(DomainException::class);

        ($this->make)('foo', Url::of('/'), $response);
    }

    public function testMake()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
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
            ->willReturn(Stream::ofContent('{"identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false},"url":{"type":"string","access":["READ","CREATE","UPDATE"],"variants":[],"optional":false}},"metas":[],"linkable_to":[],"rangeable":true}'));

        $definition = ($this->make)(
            'foo',
            Url::of('http://example.com/foo'),
            $response
        );

        $this->assertInstanceOf(HttpResource::class, $definition);
        $this->assertSame('foo', $definition->name());
        $this->assertSame('http://example.com/foo', $definition->url()->toString());
        $this->assertSame('uuid', $definition->identity()->toString());
        $this->assertSame(
            'uuid',
            $definition->properties()->get('uuid')->name()
        );
        $this->assertSame(
            'string',
            $definition->properties()->get('uuid')->type()->toString()
        );
        $this->assertSame(
            ['READ'],
            unwrap($definition->properties()->get('uuid')->access()->mask())
        );
        $this->assertSame(
            [],
            unwrap($definition->properties()->get('uuid')->variants())
        );
        $this->assertFalse(
            $definition->properties()->get('uuid')->isOptional()
        );
        $this->assertSame(
            'url',
            $definition->properties()->get('url')->name()
        );
        $this->assertSame(
            'string',
            $definition->properties()->get('url')->type()->toString()
        );
        $this->assertSame(
            ['READ', 'CREATE', 'UPDATE'],
            unwrap($definition->properties()->get('url')->access()->mask())
        );
        $this->assertSame(
            [],
            unwrap($definition->properties()->get('url')->variants())
        );
        $this->assertFalse(
            $definition->properties()->get('url')->isOptional()
        );
        $this->assertCount(0, $definition->metas());
        $this->assertTrue($definition->isRangeable());
    }
}
