<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\{
    Capabilities\CacheFactory,
    Capabilities\FactoryInterface,
    CapabilitiesInterface,
    CacheCapabilities
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\Adapter;
use Symfony\Component\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;

class CacheFactoryTest extends TestCase
{
    private $factory;
    private $inner;

    public function setUp()
    {
        $this->factory = new CacheFactory(
            $this->createMock(Adapter::class),
            $this->createMock(SerializerInterface::class),
            $this->inner = $this->createMock(FactoryInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            FactoryInterface::class,
            $this->factory
        );
    }

    public function testMake()
    {
        $url = $this->createMock(UrlInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('make')
            ->with($url)
            ->willReturn($this->createMock(CapabilitiesInterface::class));

        $this->assertInstanceOf(
            CacheCapabilities::class,
            $this->factory->make($url)
        );
    }
}
