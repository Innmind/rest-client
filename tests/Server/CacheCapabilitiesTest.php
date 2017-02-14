<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\CacheCapabilities,
    Server\CapabilitiesInterface,
    Definition\Types,
    Definition\HttpResource,
    Serializer\Normalizer\DefinitionNormalizer,
    Serializer\Normalizer\CapabilitiesNamesNormalizer
};
use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    Directory,
    File,
    Stream\StringStream
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    SetInterface,
    MapInterface
};
use Symfony\Component\Serializer\{
    Serializer,
    Encoder\JsonEncoder
};
use PHPUnit\Framework\TestCase;

class CacheCapabilitiesTest extends TestCase
{
    private $capabilities;
    private $inner;
    private $filesystem;
    private $serializer;
    private $directory;
    private $definition;
    private $raw;

    public function setUp()
    {
        $types = new Types;
        Types::defaults()->foreach(function(string $class) use ($types) {
            $types->register($class);
        });

        $this->capabilities = new CacheCapabilities(
            $this->inner = $this->createMock(CapabilitiesInterface::class),
            $this->filesystem = new MemoryAdapter,
            $this->serializer = new Serializer(
                [
                    new DefinitionNormalizer($types),
                    new CapabilitiesNamesNormalizer,
                ],
                [new JsonEncoder]
            ),
            Url::fromString('http://example.com/')
        );
        $this->directory = md5('http://example.com/');
        $this->raw = [
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
                ],
            ],
            'metas' => [
                'foo' => ['bar' => 'baz'],
            ],
            'rangeable' => true,
        ];
        $this->definition = $this->serializer->denormalize(
            $this->raw,
            HttpResource::class,
            null,
            ['name' => 'foo']
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            CapabilitiesInterface::class,
            $this->capabilities
        );
    }

    public function testNames()
    {
        $this
            ->inner
            ->expects($this->once())
            ->method('names')
            ->willReturn(
                (new Set('string'))
                    ->add('foo')
                    ->add('bar')
            );
        $names = $this->capabilities->names();

        $this->assertInstanceOf(SetInterface::class, $names);
        $this->assertSame('string', (string) $names->type());
        $this->assertCount(2, $names);
        $this->assertSame(['foo', 'bar'], $names->toPrimitive());
        $this->assertSame($names, $this->capabilities->names());
        $this->assertTrue($this->filesystem->has($this->directory));
        $this->assertTrue(
            $this->filesystem->get($this->directory)->has('.names.json')
        );
        $this->assertSame(
            '["foo","bar"]',
            (string) $this
                ->filesystem
                ->get($this->directory)
                ->get('.names.json')
                ->content()
        );
    }

    public function testNamesFromCache()
    {
        $this
            ->inner
            ->expects($this->never())
            ->method('names');
        $this
            ->filesystem
            ->add(
                (new Directory($this->directory))->add(
                    new File(
                        '.names.json',
                        new StringStream('["foo","bar"]')
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
            ->inner
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);

        $definition = $this->capabilities->get('foo');

        $this->assertSame($this->definition, $definition);
        $this->assertSame($definition, $this->capabilities->get('foo'));
        $this->assertTrue($this->filesystem->has($this->directory));
        $this->assertTrue(
            $this->filesystem->get($this->directory)->has('foo.json')
        );
        $this->assertSame(
            json_encode($this->raw),
            (string) $this
                ->filesystem
                ->get($this->directory)
                ->get('foo.json')
                ->content()
        );
    }

    public function testGetFromCache()
    {
        $this
            ->inner
            ->expects($this->never())
            ->method('get');
        $this
            ->filesystem
            ->add(
                (new Directory($this->directory))->add(
                    new File(
                        'foo.json',
                        new StringStream(json_encode($this->raw))
                    )
                )
            );

        $definition = $this->capabilities->get('foo');

        $this->assertInstanceOf(HttpResource::class, $definition);
        $this->assertSame(
            $this->raw,
            $this->serializer->normalize($definition)
        );
        $this->assertSame($definition, $this->capabilities->get('foo'));
    }

    public function testDefinitions()
    {
        $this
            ->inner
            ->expects($this->once())
            ->method('names')
            ->willReturn(
                (new Set('string'))->add('foo')->add('bar')
            );
        $this
            ->inner
            ->expects($this->at(1))
            ->method('get')
            ->with('foo')
            ->willReturn($this->definition);
        $this
            ->inner
            ->expects($this->at(2))
            ->method('get')
            ->with('bar')
            ->willReturn($bar = clone $this->definition);

        $definitions = $this->capabilities->definitions();

        $this->assertInstanceOf(MapInterface::class, $definitions);
        $this->assertSame('string', (string) $definitions->keyType());
        $this->assertSame(
            HttpResource::class,
            (string) $definitions->valueType()
        );
        $this->assertCount(2, $definitions);
        $this->assertSame($this->definition, $definitions->get('foo'));
        $this->assertSame($bar, $definitions->get('bar'));
        $this->assertSame($definitions, $this->capabilities->definitions());
    }

    public function testRefresh()
    {
        $this
            ->inner
            ->expects($this->once())
            ->method('refresh');
        $this
            ->inner
            ->expects($this->once())
            ->method('names')
            ->willReturn(
                (new Set('string'))
                    ->add('foo')
                    ->add('bar')
            );
        $this
            ->filesystem
            ->add(
                (new Directory($this->directory))->add(
                    new File(
                        '.names.json',
                        new StringStream('["foo","bar"]')
                    )
                )
            );

        $names = $this->capabilities->names();
        $this->assertSame($this->capabilities, $this->capabilities->refresh());
        $this->assertFalse($this->filesystem->has($this->directory));
        $this->assertNotSame($names, $this->capabilities->names());
    }
}
