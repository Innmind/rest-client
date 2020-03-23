<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities\CacheFactory,
    Server\Capabilities\Factory,
    Server\Capabilities,
    Server\Capabilities\CacheCapabilities,
    Serializer\Decode,
    Serializer\Encode,
    Serializer\Denormalizer\DenormalizeCapabilitiesNames,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Normalizer\NormalizeDefinition,
    Definition\Types,
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\Adapter;
use PHPUnit\Framework\TestCase;

class CacheFactoryTest extends TestCase
{
    private $make;
    private $inner;

    public function setUp(): void
    {
        $types = new Types(...Types::defaults());

        $this->make = new CacheFactory(
            $this->createMock(Adapter::class),
            $this->createMock(Decode::class),
            $this->createMock(Encode::class),
            new DenormalizeCapabilitiesNames,
            new DenormalizeDefinition($types),
            new NormalizeDefinition,
            $this->inner = $this->createMock(Factory::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Factory::class,
            $this->make
        );
    }

    public function testMake()
    {
        $url = $this->createMock(UrlInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($url)
            ->willReturn($this->createMock(Capabilities::class));

        $this->assertInstanceOf(
            CacheCapabilities::class,
            ($this->make)($url)
        );
    }
}
