<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities\CacheCapabilities,
    Server\Capabilities,
    Definition\Types,
    Definition\HttpResource,
    Serializer\Decode,
    Serializer\Encode,
    Serializer\Denormalizer\DenormalizeCapabilitiesNames,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Normalizer\NormalizeDefinition,
};
use Innmind\Filesystem\{
    Adapter\MemoryAdapter,
    Directory\Directory,
    File\File,
    Stream\StringStream,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
};
use PHPUnit\Framework\TestCase;

class CacheCapabilitiesTest extends TestCase
{
    private $capabilities;
    private $inner;
    private $filesystem;
    private $normalizeDefinition;
    private $directory;
    private $definition;
    private $raw;

    public function setUp()
    {
        $denormalizeDefinition = new DenormalizeDefinition(new Types);

        $this->capabilities = new CacheCapabilities(
            $this->inner = $this->createMock(Capabilities::class),
            $this->filesystem = new MemoryAdapter,
            new Decode\Json,
            new Encode\Json,
            new DenormalizeCapabilitiesNames,
            $denormalizeDefinition,
            $this->normalizeDefinition = new NormalizeDefinition,
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
            'linkable_to' => [],
            'rangeable' => true,
        ];
        $this->definition = $denormalizeDefinition($this->raw, 'foo');
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Capabilities::class,
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
                Set::of('string', 'foo', 'bar')
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
                        new StringStream(\json_encode($this->raw))
                    )
                )
            );

        $definition = $this->capabilities->get('foo');

        $this->assertInstanceOf(HttpResource::class, $definition);
        $this->assertSame(
            $this->raw,
            ($this->normalizeDefinition)($definition)
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
                Set::of('string', 'foo', 'bar')
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
                Set::of('string', 'foo', 'bar')
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
