<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\DefinitionFactory,
    Definition\Types,
    Definition\HttpResource,
    Serializer\Normalizer\DefinitionNormalizer
};
use Innmind\Http\{
    Message\ResponseInterface,
    Message\StatusCode,
    Headers,
    Header\HeaderInterface,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\ParameterInterface
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Url\Url;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class DefinitionFactoryTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $types = new Types;
        Types::defaults()->foreach(function(string $class) use ($types) {
            $types->register($class);
        });

        $this->factory = new DefinitionFactory(
            new DefinitionNormalizer($types)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenResponseNotSuccessful()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(404));

        $this->factory->make('foo', Url::fromString('/'), $response);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenResponseHasNoContentType()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    new Map('string', HeaderInterface::class)
                )
            );

        $this->factory->make('foo', Url::fromString('/'), $response);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenResponseHasNotJson()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'content-type',
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

        $this->factory->make('foo', Url::fromString('/'), $response);
    }

    public function testMake()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'content-type',
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
            ->willReturn(new StringStream('{"identity":"uuid","properties":{"uuid":{"type":"string","access":["READ"],"variants":[],"optional":false},"url":{"type":"string","access":["READ","CREATE","UPDATE"],"variants":[],"optional":false}},"metas":[],"rangeable":true}'));

        $definition = $this->factory->make(
            'foo',
            Url::fromString('http://example.com/foo'),
            $response
        );

        $this->assertInstanceOf(HttpResource::class, $definition);
        $this->assertSame('foo', $definition->name());
        $this->assertSame('http://example.com/foo', (string) $definition->url());
        $this->assertSame('uuid', (string) $definition->identity());
        $this->assertSame(
            'uuid',
            $definition->properties()->get('uuid')->name()
        );
        $this->assertSame(
            'string',
            (string) $definition->properties()->get('uuid')->type()
        );
        $this->assertSame(
            ['READ'],
            $definition->properties()->get('uuid')->access()->mask()->toPrimitive()
        );
        $this->assertSame(
            [],
            $definition->properties()->get('uuid')->variants()->toPrimitive()
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
            (string) $definition->properties()->get('url')->type()
        );
        $this->assertSame(
            ['READ', 'CREATE', 'UPDATE'],
            $definition->properties()->get('url')->access()->mask()->toPrimitive()
        );
        $this->assertSame(
            [],
            $definition->properties()->get('url')->variants()->toPrimitive()
        );
        $this->assertFalse(
            $definition->properties()->get('url')->isOptional()
        );
        $this->assertCount(0, $definition->metas());
        $this->assertTrue($definition->isRangeable());
    }
}
